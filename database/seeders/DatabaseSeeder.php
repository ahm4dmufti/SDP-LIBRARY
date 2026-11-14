<?php

namespace Database\Seeders;

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
    }
}
