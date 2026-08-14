<?php

namespace App\Actions\StockOpname;

use App\Enums\StockOpnameStatus;
use App\Models\Product;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateStockOpname
{
    /**
     * Create a stock opname and its item lines.
     *
     * The action owns the use case orchestration; the controller remains
     * responsible only for HTTP concerns and authorization.
     *
     * @param array{
     *     warehouse_id: int,
     *     assigned_to: int,
     *     title: string,
     *     start_date: string,
     *     notes?: string|null,
     *     items: array<int, array{product_id: int}>
     * } $data
     */
    public function execute(array $data): mixed
    {
        return DB::transaction(function () use ($data): StockOpname {
            $stockOpname = StockOpname::query()->create([
                'warehouse_id' => $data['warehouse_id'],
                'assigned_to' => $data['assigned_to'],
                'opname_number' => $this->generateOpnameNumber(),
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'status' => StockOpnameStatus::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);

                $stockOpname->items()->create([
                    'product_id' => $product->id,
                ]);
            }

            return $stockOpname;
        });
    }

    private function generateOpnameNumber(): string
    {
        return 'OPN-'
            .now()->format('Ymd')
            .'-'
            .Str::upper(Str::random(5));
    }
}
