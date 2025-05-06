<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'name' => 'Laptop Dell XPS 15',
                'description' => 'Wydajny laptop do profesjonalnych zastosowań.',
                'price' => 1899.99,
                'qty' => 15
            ],
            [
                'name' => 'Smartfon Samsung Galaxy S23',
                'description' => 'Najnowszy flagowy smartfon z doskonałym aparatem.',
                'price' => 999.00,
                'qty' => 30
            ],
            [
                'name' => 'Słuchawki bezprzewodowe Sony WH-1000XM5',
                'description' => 'Najlepsze słuchawki z redukcją szumów na rynku.',
                'price' => 349.50,
                'qty' => 22
            ],
            [
                'name' => 'Monitor 27 cali LG UltraFine',
                'description' => 'Monitor o wysokiej rozdzielczości do pracy i rozrywki.',
                'price' => 399.00,
                'qty' => 18
            ],
            [
                'name' => 'Tablet Apple iPad Pro 12.9',
                'description' => 'Profesjonalny tablet z ekranem Liquid Retina XDR.',
                'price' => 1099.00,
                'qty' => 10
            ],
            [
                'name' => 'Klawiatura mechaniczna Logitech G915',
                'description' => 'Bezprzewodowa klawiatura mechaniczna z podświetleniem RGB.',
                'price' => 249.99,
                'qty' => 12
            ],
            [
                'name' => 'Mysz bezprzewodowa Razer Basilisk V3 Pro',
                'description' => 'Ergonomiczna mysz dla graczy z szybkim połączeniem.',
                'price' => 159.00,
                'qty' => 25
            ],
            [
                'name' => 'Dysk SSD Samsung 990 Pro 1TB',
                'description' => 'Szybki dysk SSD NVMe PCIe 4.0.',
                'price' => 179.99,
                'qty' => 20
            ],
            [
                'name' => 'Głośniki Bluetooth JBL Flip 6',
                'description' => 'Przenośny głośnik Bluetooth o mocnym brzmieniu.',
                'price' => 129.00,
                'qty' => 35
            ],
            [
                'name' => 'Smartwatch Garmin Fenix 7',
                'description' => 'Zaawansowany smartwatch dla sportowców i aktywnych.',
                'price' => 699.00,
                'qty' => 8
            ]
        ];
        for ($i = 0; $i < count($products); ++$i) {
            $this->db->table('products')->insert($products[$i]);
        }
    }
}
