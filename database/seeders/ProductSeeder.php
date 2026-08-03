<?php

namespace Database\Seeders;

use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $electronics = Category::query()->where('code', 'CAT-ELC')->firstOrFail();
        $stationery = Category::query()->where('code', 'CAT-STA')->firstOrFail();
        $generic = Brand::query()->where('code', 'BRD-GEN')->firstOrFail();
        $acme = Brand::query()->where('code', 'BRD-ACM')->firstOrFail();
        $pcs = Unit::query()->where('code', 'UNT-PCS')->firstOrFail();
        $box = Unit::query()->where('code', 'UNT-BOX')->firstOrFail();

        $jakarta = Warehouse::query()->where('code', 'WH-JKT')->firstOrFail();
        $surabaya = Warehouse::query()->where('code', 'WH-SBY')->firstOrFail();

        $products = [
            [
                'warehouse' => $jakarta,
                'bin_code' => 'BIN-A1-1',
                'category_id' => $electronics->id,
                'brand_id' => $acme->id,
                'unit_id' => $pcs->id,
                'sku' => 'ELC-0001',
                'barcode' => '8991234560011',
                'name' => 'USB-C Cable 1m',
                'stock' => 150,
                'min_stock' => 30,
                'max_stock' => 500,
                'cost_price' => 15000,
                'selling_price' => 25000,
            ],
            [
                'warehouse' => $jakarta,
                'bin_code' => 'BIN-A1-2',
                'category_id' => $electronics->id,
                'brand_id' => $generic->id,
                'unit_id' => $pcs->id,
                'sku' => 'ELC-0002',
                'barcode' => '8991234560028',
                'name' => 'Wireless Mouse',
                'stock' => 8, // intentionally below min_stock, to demo Low Stock status
                'min_stock' => 20,
                'max_stock' => 200,
                'cost_price' => 45000,
                'selling_price' => 75000,
            ],
            [
                'warehouse' => $jakarta,
                'bin_code' => 'BIN-B1-1',
                'category_id' => $stationery->id,
                'brand_id' => $generic->id,
                'unit_id' => $box->id,
                'sku' => 'STA-0001',
                'barcode' => '8991234560035',
                'name' => 'A4 Paper (500 sheets)',
                'stock' => 60,
                'min_stock' => 15,
                'max_stock' => 300,
                'cost_price' => 42000,
                'selling_price' => 55000,
            ],
            [
                'warehouse' => $surabaya,
                'bin_code' => 'BIN-A1-1',
                'category_id' => $electronics->id,
                'brand_id' => $acme->id,
                'unit_id' => $pcs->id,
                'sku' => 'ELC-0001', // same SKU as Jakarta — valid, since unique is per-warehouse now
                'barcode' => '8991234560042',
                'name' => 'USB-C Cable 1m',
                'stock' => 40,
                'min_stock' => 10,
                'max_stock' => 150,
                'cost_price' => 15000,
                'selling_price' => 25000,
            ],
        ];

        foreach ($products as $data) {
            $warehouse = $data['warehouse'];
            $bin = BinLocation::query()->where('warehouse_id', $warehouse->id)
                ->where('code', $data['bin_code'])
                ->firstOrFail();

            Product::query()->firstOrCreate(
                ['sku' => $data['sku'], 'warehouse_id' => $warehouse->id],
                [
                    'category_id' => $data['category_id'],
                    'brand_id' => $data['brand_id'],
                    'unit_id' => $data['unit_id'],
                    'bin_location_id' => $bin->id,
                    'barcode' => $data['barcode'],
                    'name' => $data['name'],
                    'stock' => $data['stock'],
                    'min_stock' => $data['min_stock'],
                    'max_stock' => $data['max_stock'],
                    'cost_price' => $data['cost_price'],
                    'selling_price' => $data['selling_price'],
                ]
            );
        }
    }
}
