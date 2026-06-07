<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->input('sale_price') === 'null' || $this->input('sale_price') === '') {
            $this->merge([
                'sale_price' => null,
            ]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        return [
            // 'sometimes|required' có nghĩa là: 
            // - Nếu không gửi trường này lên Bỏ qua, giữ giá trị cũ.
            // - Nếu có gửi lên: Bắt buộc không được để trống (không được để "").
            'name' => "sometimes|required|string|max:255|unique:products,name,{$productId}",
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'base_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sku' => "sometimes|required|string|max:100|unique:products,sku,{$productId}",
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
            'brand_id' => 'sometimes|required|integer|exists:brands,id',
            'description' => 'nullable|string',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
            'gallery_images' => 'nullable|array|max:20',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:20480',
            'delete_gallery_ids' => 'nullable', // Cho phép JSON string hoặc array từ FormData
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Bắt buộc trả về JSON 422 thay vì redirect về HTML Swagger
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi validation dữ liệu',
            'errors' => $validator->errors()
        ], 422));
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute không được để trống khi cập nhật.',
            'unique' => ':attribute này đã được sử dụng.',
            'exists' => ':attribute không hợp lệ.',
            'image.image' => 'File tải lên phải là một hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg hoặc webp.',
            'image.max' => 'Ảnh đại diện không được vượt quá 20MB.',
            'gallery_images.max' => 'Chỉ được tải lên tối đa 20 ảnh phụ.',
            'gallery_images.*.image' => 'File tải lên phải là một hình ảnh hợp lệ.',
            'gallery_images.*.max' => 'Mỗi ảnh phụ không được vượt quá 20MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên sản phẩm',
            'category_id' => 'Danh mục',
            'base_price' => 'Giá sản phẩm',
            'sku' => 'Mã SKU',
            'stock_quantity' => 'Số lượng tồn kho',
            'image' => 'Ảnh sản phẩm',
        ];
    }
}
