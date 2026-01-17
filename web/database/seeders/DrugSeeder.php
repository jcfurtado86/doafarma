<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Drug;
use Illuminate\Database\Seeder;

class DrugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $drugs = [
            [
                'substance'           => 'Losartana Potássica',
                'laboratory'          => 'Medley',
                'registration_number' => '1093303470034',
                'product_name'        => 'Losartana Potássica 50mg',
                'presentation'        => 'Comprimido revestido - 30 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Metformina',
                'laboratory'          => 'EMS',
                'registration_number' => '1058400830047',
                'product_name'        => 'Metformina 850mg',
                'presentation'        => 'Comprimido - 30 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Omeprazol',
                'laboratory'          => 'Eurofarma',
                'registration_number' => '1057304700018',
                'product_name'        => 'Omeprazol 20mg',
                'presentation'        => 'Cápsula - 28 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Atenolol',
                'laboratory'          => 'Sandoz',
                'registration_number' => '1004701860013',
                'product_name'        => 'Atenolol 50mg',
                'presentation'        => 'Comprimido - 30 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Sinvastatina',
                'laboratory'          => 'Germed',
                'registration_number' => '1058400760012',
                'product_name'        => 'Sinvastatina 20mg',
                'presentation'        => 'Comprimido revestido - 30 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Dipirona Sódica',
                'laboratory'          => 'Sanofi',
                'registration_number' => '1130001150019',
                'product_name'        => 'Novalgina 1g',
                'presentation'        => 'Comprimido - 10 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Paracetamol',
                'laboratory'          => 'EMS',
                'registration_number' => '1058400630028',
                'product_name'        => 'Paracetamol 750mg',
                'presentation'        => 'Comprimido - 20 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Ibuprofeno',
                'laboratory'          => 'Medley',
                'registration_number' => '1093303760041',
                'product_name'        => 'Ibuprofeno 600mg',
                'presentation'        => 'Comprimido revestido - 20 unidades',
                'stripe_color'        => 'white',
            ],
            [
                'substance'           => 'Amoxicilina',
                'laboratory'          => 'EMS',
                'registration_number' => '1058400350097',
                'product_name'        => 'Amoxicilina 500mg',
                'presentation'        => 'Cápsula - 21 unidades',
                'stripe_color'        => 'red',
            ],
            [
                'substance'           => 'Azitromicina',
                'laboratory'          => 'Eurofarma',
                'registration_number' => '1057304830055',
                'product_name'        => 'Azitromicina 500mg',
                'presentation'        => 'Comprimido revestido - 3 unidades',
                'stripe_color'        => 'red',
            ],
        ];

        foreach ($drugs as $drug) {
            Drug::create($drug);
        }
    }
}
