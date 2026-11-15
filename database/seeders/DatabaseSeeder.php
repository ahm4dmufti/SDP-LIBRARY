<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Customer;
use App\Models\staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The "hashed" cast on the model hashes this password automatically.
        staff::firstOrCreate(
            ['email' => 'ali2@gmail.com'],
            [
                'name' => 'ali',
                'password' => '12345678',
                'phone' => '0000000000',
                'position' => 'Librarian',
            ]
        );

        Customer::firstOrCreate(
            ['phone_number' => '0791234567'],
            [
                'name' => 'omar',
                'email' => 'omar@gmail.com',
                'password' => '12345678',
            ]
        );

        $fiction = Category::firstOrCreate(['name' => 'Fiction'], ['description' => 'Novels and stories']);
        $science = Category::firstOrCreate(['name' => 'Science'], ['description' => 'Science and technology']);

        Book::firstOrCreate(
            ['ISBN' => '9780000000001'],
            ['name' => 'The First Story', 'author' => 'Sami Haddad', 'category_id' => $fiction->id]
        );
        Book::firstOrCreate(
            ['ISBN' => '9780000000002'],
            ['name' => 'Physics Basics', 'author' => 'Lina Khoury', 'category_id' => $science->id]
        );
    }
}
