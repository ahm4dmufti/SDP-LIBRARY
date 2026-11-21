<?php

use App\Models\Borrowing;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a customer, hashes the password and returns a working token', function () {
    $token = $this->postJson('/api/customer', [
        'name' => 'omar',
        'email' => 'omar@example.com',
        'phone_number' => '0791234567',
        'password' => 'secret123',
    ])
        ->assertCreated()
        ->assertJsonPath('customer.phone_number', '0791234567')
        ->assertJsonMissingPath('customer.password')
        ->json('token');

    $customer = Customer::where('phone_number', '0791234567')->sole();
    expect($customer->password)->not->toBe('secret123');

    $this->withToken($token)->getJson('/api/customer-me')
        ->assertOk()
        ->assertJsonPath('customer.id', $customer->id);
});

it('rejects a customer registration with missing fields', function () {
    $this->postJson('/api/customer', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'phone_number', 'password']);
});

it('rejects a duplicate phone number on registration', function () {
    Customer::factory()->create(['phone_number' => '0791234567']);

    $this->postJson('/api/customer', [
        'name' => 'omar',
        'email' => 'other@example.com',
        'phone_number' => '0791234567',
        'password' => 'secret123',
    ])->assertJsonValidationErrors(['phone_number']);
});

it('logs a customer in by phone number', function () {
    Customer::factory()->create(['phone_number' => '0791234567', 'password' => 'secret123']);

    $this->postJson('/api/customer/login', ['phone_number' => '0791234567', 'password' => 'secret123'])
        ->assertOk()
        ->assertJsonStructure(['message', 'token']);
});

it('rejects a customer login with a wrong password', function () {
    Customer::factory()->create(['phone_number' => '0791234567', 'password' => 'secret123']);

    $this->postJson('/api/customer/login', ['phone_number' => '0791234567', 'password' => 'wrong-pass'])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid credentials']);
});

it('returns the logged-in customer and logs out', function () {
    $customer = Customer::factory()->create();
    $token = $customer->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/api/customer-me')
        ->assertOk()
        ->assertJsonPath('customer.id', $customer->id);

    $this->withToken($token)->postJson('/api/customer-logout')->assertOk();

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/customer-me')->assertUnauthorized();
});

it('does not let a customer token open staff routes', function (string $method, string $uri) {
    $token = Customer::factory()->create()->createToken('t')->plainTextToken;

    $this->withToken($token)->json($method, $uri)->assertUnauthorized();
})->with([
    'staff list' => ['GET', '/api/staff'],
    'staff me' => ['GET', '/api/staff-me'],
    'create book' => ['POST', '/api/book'],
    'create category' => ['POST', '/api/category'],
]);

it('does not let a staff token open customer routes', function () {
    $this->withToken(staffToken())->getJson('/api/customer-me')->assertUnauthorized();
});

it('lets staff list customers and view one with their borrowed books', function () {
    $borrowing = Borrowing::factory()->create();
    Customer::factory()->create();
    $token = staffToken();

    $this->withToken($token)->getJson('/api/customer')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonMissingPath('0.password');

    $this->withToken($token)->getJson("/api/customer/{$borrowing->customer_id}")
        ->assertOk()
        ->assertJsonPath('borrowings.0.id', $borrowing->book_id)
        ->assertJsonMissingPath('password');
});

it('does not let a customer list customers', function () {
    $token = Customer::factory()->create()->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/api/customer')->assertUnauthorized();
});
