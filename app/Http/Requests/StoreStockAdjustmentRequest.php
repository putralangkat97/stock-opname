<?php

namespace App\Http\Requests;

use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'type' => ['required', new Enum(StockAdjustmentType::class)],
            'reason' => ['required', new Enum(StockAdjustmentReason::class)],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            // No unit_price here — an adjustment corrects quantity, not value.
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
