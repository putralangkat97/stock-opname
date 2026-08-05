<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'bin_location_id' => ['nullable', 'exists:bin_locations,id'],

            'sku' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'sku')->where('warehouse_id', $product->warehouse_id)->ignore($product->id),
            ],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'qr_code' => ['nullable', 'string', 'max:100', Rule::unique('products', 'qr_code')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],

            // Deliberately no 'stock' here — stock only ever changes via
            // approve() on a Goods Receipt/Issue/Transfer/Adjustment/Opname,
            // never through a direct product edit. Don't add it back without
            // revisiting that invariant.
            'min_stock' => ['required', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'gte:min_stock'],

            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],

            'is_fast_moving' => ['boolean'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
