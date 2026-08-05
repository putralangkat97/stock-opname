<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'bin_location_id' => ['nullable', 'exists:bin_locations,id'],

            'sku' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'sku')->where('warehouse_id', $this->input('warehouse_id')),
            ],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'qr_code' => ['nullable', 'string', 'max:100', 'unique:products,qr_code'],
            'name' => ['required', 'string', 'max:255'],

            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'gte:min_stock'],

            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],

            'is_fast_moving' => ['boolean'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
