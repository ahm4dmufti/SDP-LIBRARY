<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Borrow request body for days counted from today.
 *
 * @return array{book_id: int, borrowing_date: string, return_date: string}
 */
function borrowBody(Book $book, int $fromDay, int $toDay): array
{
    return [
        'book_id' => $book->id,
        'borrowing_date' => now()->addDays($fromDay)->toDateString(),
        'return_date' => now()->addDays($toDay)->toDateString(),
    ];
}

it('lets a customer borrow a book and list their borrowings', function () {
    $customer = Customer::factory()->create();
    $book = Book::factory()->create();
    $token = $customer->createToken('t')->plainTextToken;

    $this->withToken($token)->postJson('/api/borrowings', borrowBody($book, 1, 5))->assertOk();

    $this->withToken($token)->getJson('/api/borrowings')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $book->id)
        ->assertJsonPath('0.pivot.customer_id', $customer->id)
        ->assertJsonPath('0.pivot.is_returned', 0);
});

it('rejects borrowing a book that does not exist', function () {
    $token = Customer::factory()->create()->createToken('t')->plainTextToken;

    $this->withToken($token)->postJson('/api/borrowings', [...borrowBody(Book::factory()->create(), 1, 5), 'book_id' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['book_id']);
});

it('rejects a borrowing date in the past and a return date before the borrowing date', function () {
    $token = Customer::factory()->create()->createToken('t')->plainTextToken;
    $book = Book::factory()->create();

    $this->withToken($token)->postJson('/api/borrowings', borrowBody($book, -1, 5))
        ->assertJsonValidationErrors(['borrowing_date']);

    $this->withToken($token)->postJson('/api/borrowings', borrowBody($book, 5, 2))
        ->assertJsonValidationErrors(['return_date']);
});

it('blocks overlapping dates for the same book until it is returned', function () {
    $book = Book::factory()->create();
    $customerA = Customer::factory()->create();
    $tokenB = Customer::factory()->create()->createToken('t')->plainTextToken;

    $this->withToken($customerA->createToken('t')->plainTextToken)
        ->postJson('/api/borrowings', borrowBody($book, 1, 5))->assertOk();

    $this->withToken($tokenB)->postJson('/api/borrowings', borrowBody($book, 3, 8))
        ->assertUnprocessable()
        ->assertJson(['message' => 'This book is already borrowed on these dates']);

    $this->withToken($tokenB)->postJson('/api/borrowings', borrowBody($book, 6, 10))->assertOk();

    $this->withToken(staffToken())->putJson('/api/borrowings/return', [
        'customer_id' => $customerA->id,
        'book_id' => $book->id,
    ])->assertOk();

    $this->withToken($tokenB)->postJson('/api/borrowings', borrowBody($book, 2, 4))->assertOk();
});

it('allows at most 3 open borrowings per customer', function () {
    $customer = Customer::factory()->create();
    Borrowing::factory()->count(3)->for($customer)->create();
    $token = $customer->createToken('t')->plainTextToken;

    $this->withToken($token)->postJson('/api/borrowings', borrowBody(Book::factory()->create(), 1, 5))
        ->assertUnprocessable();

    Borrowing::where('customer_id', $customer->id)->first()->update(['is_returned' => true]);

    $this->withToken($token)->postJson('/api/borrowings', borrowBody(Book::factory()->create(), 1, 5))
        ->assertOk();
});

it('removes a borrowing of the logged-in customer only', function () {
    $book = Book::factory()->create();
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    Borrowing::factory()->for($customer)->for($book)->create();
    Borrowing::factory()->for($otherCustomer)->for($book)->create();

    $this->withToken($customer->createToken('t')->plainTextToken)
        ->deleteJson("/api/borrowings/{$book->id}")
        ->assertOk();

    $this->assertDatabaseMissing('borrowings', ['customer_id' => $customer->id]);
    $this->assertDatabaseHas('borrowings', ['customer_id' => $otherCustomer->id]);
});

it('lets staff mark a book as returned only once', function () {
    $borrowing = Borrowing::factory()->create();
    $body = ['customer_id' => $borrowing->customer_id, 'book_id' => $borrowing->book_id];
    $staffToken = staffToken();

    $this->withToken($staffToken)->putJson('/api/borrowings/return', $body)
        ->assertOk()
        ->assertJson(['message' => 'Book marked as returned']);

    expect($borrowing->fresh()->is_returned)->toBeTrue();

    $this->withToken($staffToken)->putJson('/api/borrowings/return', $body)->assertNotFound();
});

it('lets staff list all borrowings and filter the open ones', function () {
    Borrowing::factory()->create();
    Borrowing::factory()->returned()->create();
    $staffToken = staffToken();

    $this->withToken($staffToken)->getJson('/api/borrowings/all')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonStructure([['customer' => ['name'], 'book' => ['name']]]);

    $this->withToken($staffToken)->getJson('/api/borrowings/all?is_returned=0')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.is_returned', false);
});

it('lists only open borrowings that are past their return date as overdue', function () {
    $late = Borrowing::factory()->overdue()->create();
    Borrowing::factory()->overdue()->returned()->create();
    Borrowing::factory()->create();

    $this->withToken(staffToken())->getJson('/api/borrowings/overdue')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $late->id);
});

it('keeps the borrowings staff routes away from customers and the customer routes away from staff', function () {
    $customerToken = Customer::factory()->create()->createToken('t')->plainTextToken;

    $this->withToken($customerToken)->putJson('/api/borrowings/return', [])->assertUnauthorized();
    $this->withToken($customerToken)->getJson('/api/borrowings/all')->assertUnauthorized();
    $this->withToken($customerToken)->getJson('/api/borrowings/overdue')->assertUnauthorized();
    $this->withToken(staffToken())->getJson('/api/borrowings')->assertUnauthorized();
});
