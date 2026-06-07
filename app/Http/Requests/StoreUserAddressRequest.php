<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi validation dữ liệu',
            'errors' => $validator->errors()
        ], 422));
    }

    public function rules(): array
    {
        return [
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:15',
            'province_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'ward_id' => 'nullable|integer',
            'address_detail' => 'required|string|max:255',
            'is_default' => 'boolean',
            'type' => 'string|in:home,office,other'
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute không được để trống.',
            'in' => ':attribute không hợp lệ.',
            'max' => ':attribute không được vượt quá :max ký tự.',
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
