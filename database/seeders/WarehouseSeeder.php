<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            ['code' => 'WH-JKT', 'name' => 'Jakarta Main Warehouse', 'location' => 'Jakarta', 'manager' => 'Budi Santoso', 'phone' => '021-5551234', 'total_capacity' => 10000],
            ['code' => 'WH-SBY', 'name' => 'Surabaya Warehouse', 'location' => 'Surabaya', 'manager' => 'Siti Rahayu', 'phone' => '031-5555678', 'total_capacity' => 6000],
        ];

        foreach ($warehouses as $data) {
            $warehouse = Warehouse::query()
                ->firstOrCreate(['code' => $data['code']], $data);

            foreach (['A', 'B'] as $zoneCode) {
                $rack = $warehouse->racks()->firstOrCreate([
                    'code' => "RACK-{$zoneCode}1",
                ], [
                    'zone' => "Zone {$zoneCode}",
                ]);

                foreach (range(1, 3) as $binNumber) {
                    $rack->binLocations()->firstOrCreate([
                        'code' => "BIN-{$zoneCode}1-{$binNumber}",
                    ], [
                        'warehouse_id' => $warehouse->id,
                        'capacity' => 100,
                    ]);
                }
            }
        }
    }
}
