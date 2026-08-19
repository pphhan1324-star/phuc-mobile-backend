<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:15',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date|before_or_equal:today',
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
            'in' => 'Giới tính không hợp lệ.',
            'date' => 'Ngày sinh không đúng định dạng ngày tháng.',
            'max' => ':attribute quá dài.',
            'before_or_equal' => 'Ngày sinh không được là ngày trong tương lai.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Họ tên',
            'phone' => 'Số điện thoại',
        ];
    }
}
