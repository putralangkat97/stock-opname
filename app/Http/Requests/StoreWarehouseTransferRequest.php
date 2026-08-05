<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseTransferRequest extends FormRequest
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
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            // No unit_price here — a transfer moves stock, it doesn't
            // re-price it. Value stays whatever the destination row's
            // cost_price/selling_price already is (or gets cloned to on
            // first arrival — see WarehouseTransfer::complete()).
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
