<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_name' => 'sometimes|required|string|max:255',
            'receiver_phone' => 'sometimes|required|string|max:255',
            'province_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'ward_id' => 'nullable|integer',
            'address_detail' => 'sometimes|required|string|max:255',
            'is_default' => 'boolean',
            'type' => 'string|in:home,office,other'
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
            'in' => ':attribute không hợp lệ.',
            'integer' => ':attribute phải là số nguyên.',
        ];
    }

    public function attributes(): array
    {
        return [
            'receiver_name' => 'Tên người nhận',
            'receiver_phone' => 'Số điện thoại',
            'address_detail' => 'Địa chỉ chi tiết',
            'type' => 'Loại địa chỉ',
        ];
    }
}
