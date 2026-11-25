<?php

namespace Database\Seeders;

use App\Models\TouristSpot;
use Illuminate\Database\Seeder;

class TouristSpotSeeder extends Seeder
{
    public function run(): void
    {
        $spots = [
            'Sabang Park' => 'A seaside park and picnic spot in Bulan.',
            'Boulevard' => 'A coastal boulevard and gathering place in Bulan.',
            'Ilawod Beach' => 'A beach destination in Purok 1, Bulan.',
            'Kapehan Sa Bulod' => 'A hillside coffee and snack stop.',
            'Bulan Freedom Park' => 'A public park and community gathering space.',
            'Bariis Lake' => 'A lakeside destination beside a historic tunnel.',
            'Camp Hiraya' => 'An outdoor camping destination in Butag.',
            'Biton Beach' => 'A relaxing beach in Barangay Gerona.',
            'Biton Boulevard' => 'A local boulevard in the Biton area.',
            'Barbo' => 'An ocean-view picnic spot in Butag.',
            'Grace Farm' => 'A farm destination in Bulan.',
            'Sagrada Stone Beach' => 'A coastal spot with a stony shoreline.',
        ];

        foreach ($spots as $name => $description) {
            TouristSpot::updateOrCreate(
                ['spot_name' => $name],
                ['description' => $description]
            );
        }
    }
}