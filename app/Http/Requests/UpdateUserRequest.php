<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin middleware đã check, nên ở đây trả về true
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
            'is_active' => 'boolean'
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
            'max' => ':attribute không được vượt giá trị cho phép.',
            'in' => ':attribute không hợp lệ.',
            'date' => ':attribute phải là định dạng ngày.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên người dùng',
            'phone' => 'Số điện thoại',
            'gender' => 'Giới tính',
            'birthday' => 'Ngày sinh',
            'is_active' => 'Trạng thái hoạt động',
        ];
    }
}
