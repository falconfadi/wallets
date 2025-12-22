<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar'],
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'GBP', 'name' => 'British Pound'],

            ['code' => 'SYR', 'name' => 'Syrian Pound'],
        ];

        foreach ($currencies as $currency) {
            // Using firstOrCreate to avoid duplicates
            Currency::firstOrCreate(
                ['code' => $currency['code']], // Search by unique code
                ['name' => $currency['name']]   // Create with this data if not found
            );
        }

        $this->command->info('✅ Currencies seeded successfully!');
        $this->command->info('Total currencies: ' . Currency::count());
    }
}
