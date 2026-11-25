<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['item_name' => 'Tent', 'category' => 'Equipment'],
            ['item_name' => 'Chairs', 'category' => 'Equipment'],
            ['item_name' => 'Table', 'category' => 'Equipment'],
            ['item_name' => 'Plaza', 'category' => 'Facility'],
            ['item_name' => 'Function Hall', 'category' => 'Facility'],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(
                ['item_name' => $item['item_name']],
                [
                    'category' => $item['category'],
                    'availability_status' => 'Available',
                    'is_archived' => false,
                ]
            );
        }
    }
}