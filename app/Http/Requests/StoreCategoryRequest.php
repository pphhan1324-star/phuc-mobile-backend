<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
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
            'required' => ':attribute không được để trống.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'exists' => ':attribute không tồn tại.',
            'image' => ':attribute phải là hình ảnh.',
            'mimes' => ':attribute phải có định dạng: :values.',
            'integer' => ':attribute phải là số nguyên.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên danh mục',
            'parent_id' => 'Danh mục cha',
            'image' => 'Ảnh danh mục',
            'description' => 'Mô tả',
            'is_active' => 'Trạng thái',
            'sort_order' => 'Thứ tự sắp xếp',
        ];
    }
}
