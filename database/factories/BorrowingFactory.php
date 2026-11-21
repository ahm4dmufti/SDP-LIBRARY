<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'book_id' => Book::factory(),
            'borrowing_date' => now()->toDateString(),
            'return_date' => now()->addWeek()->toDateString(),
            'is_returned' => false,
        ];
    }

    /**
     * A borrowing whose return date has already passed.
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'borrowing_date' => now()->subWeeks(2)->toDateString(),
            'return_date' => now()->subDay()->toDateString(),
        ]);
    }

    /**
     * A borrowing whose book has come back.
     */
    public function returned(): static
    {
        return $this->state(fn (): array => ['is_returned' => true]);
    }
}
