<?php

use App\Models\staff;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStaff(array $overrides = []): staff
{
    return staff::create(array_merge([
        'name' => 'ali',
        'email' => 'ali@example.com',
        'password' => '12345678',
        'phone' => '0790000000',
        'position' => 'Librarian',
    ], $overrides));
}

it('hashes the password and never returns it', function () {
    $staff = makeStaff();

    expect($staff->password)->not->toBe('12345678');

    $token = $staff->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/api/staff')
        ->assertOk()
        ->assertJsonMissingPath('data.0.password')
        ->assertJsonPath('data.0.email', 'ali@example.com');
});

it('logs in with correct credentials and returns a token', function () {
    makeStaff();

    $this->postJson('/api/staff/login', ['email' => 'ali@example.com', 'password' => '12345678'])
        ->assertOk()
        ->assertJsonStructure(['message', 'token']);
});

it('rejects a wrong password', function () {
    makeStaff();

    $this->postJson('/api/staff/login', ['email' => 'ali@example.com', 'password' => 'wrong-pass'])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid credentials']);
});

it('blocks staff routes without a token', function () {
    $this->getJson('/api/staff')->assertUnauthorized();
    $this->getJson('/api/staff-me')->assertUnauthorized();
});

it('returns the logged-in staff member from staff-me', function () {
    makeStaff();
    $token = $this->postJson('/api/staff/login', ['email' => 'ali@example.com', 'password' => '12345678'])->json('token');

    $this->withToken($token)->getJson('/api/staff-me')
        ->assertOk()
        ->assertJsonPath('staff.email', 'ali@example.com');
});

it('logs out and the token stops working', function () {
    makeStaff();
    $token = $this->postJson('/api/staff/login', ['email' => 'ali@example.com', 'password' => '12345678'])->json('token');

    $this->withToken($token)->postJson('/api/staff-logout')->assertOk();

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/staff-me')->assertUnauthorized();
});

it('creates staff with a password and validates input', function () {
    $token = makeStaff()->createToken('t')->plainTextToken;

    $this->withToken($token)->postJson('/api/staff', [
        'name' => 'sara',
        'email' => 'sara@example.com',
        'password' => 'secret123',
        'phone' => '0791111111',
        'position' => 'Assistant',
    ])->assertCreated();

    $this->withToken($token)->postJson('/api/staff', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password', 'phone', 'position']);
});
