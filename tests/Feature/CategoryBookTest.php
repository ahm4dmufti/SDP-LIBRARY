<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets staff create a category', function () {
    $this->withToken(staffToken())->postJson('/api/category', [
        'name' => 'Fiction',
        'description' => 'Novels',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Fiction');

    $this->assertDatabaseHas('categories', ['name' => 'Fiction']);
});

it('lists categories with their books for a customer token', function () {
    $book = Book::factory()->create();
    $token = Customer::factory()->create()->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/api/category')
        ->assertOk()
        ->assertJsonPath('data.0.books.0.ISBN', $book->ISBN);
});

it('blocks reading books without a token', function () {
    $this->getJson('/api/book')->assertUnauthorized();
});

it('lets staff create a book', function () {
    $category = Category::factory()->create();

    $this->withToken(staffToken())->postJson('/api/book', [
        'name' => 'Dune',
        'author' => 'Frank Herbert',
        'ISBN' => '9780441013593',
        'category_id' => $category->id,
    ])->assertCreated();

    $this->assertDatabaseHas('books', ['ISBN' => '9780441013593', 'category_id' => $category->id]);
});

it('rejects a book with a category that does not exist', function () {
    $this->withToken(staffToken())->postJson('/api/book', [
        'name' => 'Dune',
        'author' => 'Frank Herbert',
        'ISBN' => '9780441013593',
        'category_id' => 999,
    ])->assertJsonValidationErrors(['category_id']);
});

it('rejects a duplicate ISBN on create', function () {
    Book::factory()->create(['ISBN' => '9780441013593']);

    $this->withToken(staffToken())->postJson('/api/book', [
        'name' => 'Dune',
        'author' => 'Frank Herbert',
        'ISBN' => '9780441013593',
        'category_id' => Category::factory()->create()->id,
    ])->assertJsonValidationErrors(['ISBN']);
});

it('updates a book while re-sending its own ISBN', function () {
    $book = Book::factory()->create();

    $this->withToken(staffToken())->putJson("/api/book/{$book->id}", [
        'name' => 'New name',
        'ISBN' => $book->ISBN,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New name');
});

it('deletes a book', function () {
    $book = Book::factory()->create();

    $this->withToken(staffToken())->deleteJson("/api/book/{$book->id}")->assertNoContent();

    $this->assertModelMissing($book);
});

it('returns 404 for a missing book', function () {
    $this->withToken(staffToken())->getJson('/api/book/999')->assertNotFound();
});

it('refuses to delete a category that still has books', function () {
    $category = Book::factory()->create()->category;

    $this->withToken(staffToken())->deleteJson("/api/category/{$category->id}")->assertConflict();

    $this->assertModelExists($category);
});

it('deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->withToken(staffToken())->deleteJson("/api/category/{$category->id}")->assertNoContent();

    $this->assertModelMissing($category);
});

it('searches books by name and filters them by category', function () {
    $fiction = Category::factory()->create();
    Book::factory()->for($fiction)->create(['name' => 'Harry Stories']);
    Book::factory()->create(['name' => 'Charlie and Friends']);
    Book::factory()->create(['name' => 'Physics Basics']);
    $token = staffToken();

    $this->withToken($token)->getJson('/api/book?search=har')->assertOk()->assertJsonCount(2, 'data');
    $this->withToken($token)->getJson("/api/book?category_id={$fiction->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Harry Stories');
    $this->withToken($token)->getJson('/api/book')->assertOk()->assertJsonCount(3, 'data');
});

it('shows a book as unavailable only while it has an open borrowing', function () {
    $borrowing = Borrowing::factory()->create();
    $token = staffToken();

    $this->withToken($token)->getJson("/api/book/{$borrowing->book_id}")
        ->assertJsonPath('data.is_available', false);

    $borrowing->update(['is_returned' => true]);

    $this->withToken($token)->getJson("/api/book/{$borrowing->book_id}")
        ->assertJsonPath('data.is_available', true);
});
