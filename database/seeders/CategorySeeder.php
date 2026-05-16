<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'code' => 'food-drink',
                'name' => 'Food & drink',
                'description' => <<<'TXT'
Coffee, tea, smoothies
Breakfast / lunch / dinner out
Snacks, vending machines, convenience store runs
Groceries (produce, dairy, meat, pantry staples)
TXT,
            ],
            [
                'code' => 'transportation',
                'name' => 'Transportation',
                'description' => <<<'TXT'
Gas / diesel
Parking, tolls
Public transit (bus, train, subway)
Rideshare / taxi
Bike/scooter rentals
TXT,
            ],
            [
                'code' => 'household-essentials',
                'name' => 'Household & essentials',
                'description' => <<<'TXT'
Toiletries (soap, toothpaste, shampoo)
Cleaning supplies, trash bags, paper goods
Small repairs / hardware store items
Laundry (coin machines, detergent)
TXT,
            ],
            [
                'code' => 'health-personal-care',
                'name' => 'Health & personal care',
                'description' => <<<'TXT'
Pharmacy items (OTC meds, vitamins)
Copays / prescriptions (when not prepaid)
Haircuts, nails, grooming products
TXT,
            ],
            [
                'code' => 'work-productivity',
                'name' => 'Work & productivity',
                'description' => <<<'TXT'
Office supplies
Coworking / printing / shipping
Software subscriptions (often monthly, but daily in impact)
TXT,
            ],
            [
                'code' => 'kids-family',
                'name' => 'Kids & family',
                'description' => <<<'TXT'
School lunch money, field trip fees
Babysitting / daycare extras
Diapers, formula, kids' activities
TXT,
            ],
            [
                'code' => 'pets',
                'name' => 'Pets',
                'description' => <<<'TXT'
Food, treats
Vet visit copays, grooming, litter/bags
TXT,
            ],
            [
                'code' => 'digital-entertainment',
                'name' => 'Digital & entertainment',
                'description' => <<<'TXT'
Streaming, games, apps
Books, magazines
Events, movies, hobbies
TXT,
            ],
            [
                'code' => 'miscellaneous',
                'name' => 'Miscellaneous',
                'description' => <<<'TXT'
ATM fees, bank charges
Tips, donations, gifts
Postage, notary, government fees
TXT,
            ],
        ];

        foreach ($rows as $row) {
            Category::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                ],
            );
        }
    }
}
