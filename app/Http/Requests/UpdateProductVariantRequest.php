<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variantId');
        return [
            'sku' => "sometimes|required|string|max:120|unique:product_variants,sku,{$variantId}",
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:50',
            'ram' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'delete_gallery_ids' => 'nullable', // JSON array string or array
            'is_available' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi validation dữ liệu',
            'errors' => $validator->errors()
        ], 422));
    }

    public function attributes(): array
    {
        return [
            'sku' => 'Mã SKU biến thể',
            'price' => 'Giá biến thể',
            'stock_quantity' => 'Số lượng tồn kho',
            'image' => 'Ảnh biến thể',
        ];
    }
    public function messages(): array
    {
        return [
            'sku.unique' => 'Mã SKU này đã được sử dụng.',
            'price.numeric' => 'Giá phải là số.',
            'image.max' => 'Ảnh không được vượt quá 2MB.',
        ];
    }
}
