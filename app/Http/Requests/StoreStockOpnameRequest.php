<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
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
            'assigned_to' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            // Only product_id — system_qty is snapshotted automatically from
            // the product's current stock by StockOpnameItem's saving() hook,
            // never accepted from the client.
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
        ];
    }
}
