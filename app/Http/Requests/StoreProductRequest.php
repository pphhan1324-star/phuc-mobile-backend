<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductRequest extends FormRequest
{
    /**
     * Xác định xem người dùng nào được quyền gửi Request này.
     */
    public function authorize(): bool
    {
        // Bất cứ ai đã pass Middleware đều được phép (Admin)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|integer|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:base_price|min:0',
            'sku' => 'required|string|max:100|unique:products,sku',
            'screen_size' => 'nullable|string|max:50',
            'screen_tech' => 'nullable|string|max:100',
            'rear_camera' => 'nullable|string|max:150',
            'front_camera' => 'nullable|string|max:100',
            'chipset' => 'nullable|string|max:100',
            'battery' => 'nullable|string|max:50',
            'charging_speed' => 'nullable|string|max:50',
            'operating_system' => 'nullable|string|max:50',
            'weight_g' => 'nullable|integer|min:0',
            'material' => 'nullable|string|max:100',
            'brand_id' => 'required|integer|exists:brands,id',
            'description' => 'nullable|string',
            'stock_quantity' => 'required|integer|min:0',
            // Rule quan trọng cho File ảnh
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
            'gallery_images' => 'nullable|array|max:20',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:20480',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
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

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'base_price.required' => 'Giá sản phẩm không được để trống.',
            'base_price.numeric' => 'Giá sản phẩm phải là số.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
            'sku.required' => 'Mã SKU không được để trống.',
            'sku.unique' => 'Mã SKU này đã được sử dụng.',
            'image.image' => 'File tải lên phải là một hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg hoặc webp.',
            'image.max' => 'Ảnh đại diện không được vượt quá 20MB.',
            'gallery_images.max' => 'Chỉ được tải lên tối đa 20 ảnh phụ.',
            'gallery_images.*.image' => 'File tải lên phải là một hình ảnh hợp lệ.',
            'gallery_images.*.max' => 'Mỗi ảnh phụ không được vượt quá 20MB.',
        ];
    }
}
