<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['code' => 'CAT-ELC', 'name' => 'Electronics', 'description' => 'Electronic components and devices'],
            ['code' => 'CAT-STA', 'name' => 'Stationery', 'description' => 'Office and stationery supplies'],
            ['code' => 'CAT-TLS', 'name' => 'Tools', 'description' => 'Hand tools and hardware'],
        ])->each(fn (array $data) => Category::query()->firstOrCreate(['code' => $data['code']], $data));

        collect([
            ['code' => 'BRD-GEN', 'name' => 'Generic', 'description' => 'Unbranded / generic goods'],
            ['code' => 'BRD-ACM', 'name' => 'Acme Corp', 'description' => 'Acme Corporation products'],
        ])->each(fn (array $data) => Brand::query()->firstOrCreate(['code' => $data['code']], $data));

        collect([
            ['code' => 'UNT-PCS', 'name' => 'Pieces', 'symbol' => 'pcs'],
            ['code' => 'UNT-BOX', 'name' => 'Box', 'symbol' => 'box'],
            ['code' => 'UNT-KG', 'name' => 'Kilogram', 'symbol' => 'kg'],
        ])->each(fn (array $data) => Unit::query()->firstOrCreate(['code' => $data['code']], $data));

        collect([
            ['code' => 'SUP-001', 'name' => 'PT Sumber Makmur', 'contact_person' => 'Andi Wijaya', 'email' => 'andi@sumbermakmur.co.id', 'phone' => '021-7778888', 'city' => 'Jakarta'],
            ['code' => 'SUP-002', 'name' => 'CV Teknologi Jaya', 'contact_person' => 'Rina Kusuma', 'email' => 'rina@teknologijaya.co.id', 'phone' => '031-9990000', 'city' => 'Surabaya'],
        ])->each(fn (array $data) => Supplier::query()->firstOrCreate(['code' => $data['code']], $data));

        collect([
            ['code' => 'CUS-001', 'name' => 'Toko Bahagia', 'contact_person' => 'Dewi Lestari', 'email' => 'dewi@tokobahagia.co.id', 'phone' => '021-1112222', 'city' => 'Jakarta'],
            ['code' => 'CUS-002', 'name' => 'UD Sejahtera', 'contact_person' => 'Hendra Gunawan', 'email' => 'hendra@sejahtera.co.id', 'phone' => '031-3334444', 'city' => 'Surabaya'],
        ])->each(fn (array $data) => Customer::query()->firstOrCreate(['code' => $data['code']], $data));
    }
}
