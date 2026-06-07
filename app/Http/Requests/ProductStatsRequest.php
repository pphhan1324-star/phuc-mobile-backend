<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductStatsRequest extends FormRequest
{
    /**
     * Xác định xem người dùng được quyền gửi request này
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules cho thống kê sản phẩm
     */
    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer|min:1|max:100',
            'days' => 'nullable|integer|min:1|max:365',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sort_by' => 'nullable|in:quantity,revenue,views',
            'order' => 'nullable|in:asc,desc',
        ];
    }

    /**
     * Custom messages cho validation errors
     */
    public function messages(): array
    {
        return [
            'limit.integer' => 'Limit phải là số nguyên',
            'limit.min' => 'Limit phải lớn hơn 0',
            'limit.max' => 'Limit không vượt quá 100',
            'days.integer' => 'Days phải là số nguyên',
            'days.min' => 'Days phải lớn hơn 0',
            'days.max' => 'Days không vượt quá 365',
            'category_id.exists' => 'Danh mục không tồn tại',
            'sort_by.in' => 'Sort by chỉ hỗ trợ: quantity, revenue, views',
            'order.in' => 'Order chỉ hỗ trợ: asc, desc',
        ];
    }

    /**
     * Handle validation failure
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi validation dữ liệu',
            'errors' => $validator->errors(),
        ], 422));
    }
}
