<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevenueStatsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $period = $this->input('period');

        $rules = [
            'period' => [
                'required',
                Rule::in(['day', 'month', 'quarter', 'year']),
            ],
        ];

        // Validation tùy theo từng period
        switch ($period) {
            case 'day':
                $rules['date'] = 'required|date_format:Y-m-d';
                break;

            case 'month':
                $rules['year'] = 'required|integer|min:2000|max:2099';
                $rules['month'] = 'required|integer|min:1|max:12';
                break;

            case 'quarter':
                $rules['year'] = 'required|integer|min:2000|max:2099';
                $rules['quarter'] = 'required|integer|in:1,2,3,4';
                break;

            case 'year':
                $rules['year'] = 'required|integer|min:2000|max:2099';
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'period.required' => 'Vui lòng chọn kỳ thống kê (day/month/quarter/year)',
            'period.in' => 'Kỳ thống kê phải là: day, month, quarter hoặc year',
            
            // Day mode
            'date.required' => 'Vui lòng chọn ngày thống kê',
            'date.date_format' => 'Ngày phải có định dạng Y-m-d (ví dụ: 2026-04-07)',

            // Month mode
            'month.required' => 'Vui lòng chọn tháng',
            'month.integer' => 'Tháng phải là số nguyên',
            'month.min' => 'Tháng phải từ 1 đến 12',
            'month.max' => 'Tháng phải từ 1 đến 12',

            // Quarter mode
            'quarter.required' => 'Vui lòng chọn quý (1, 2, 3 hoặc 4)',
            'quarter.in' => 'Quý phải là 1, 2, 3 hoặc 4',

            // Year validation chung
            'year.required' => 'Vui lòng chọn năm',
            'year.integer' => 'Năm phải là số nguyên',
            'year.min' => 'Năm phải từ 2000 trở lên',
            'year.max' => 'Năm không vượt quá 2099',
        ];
    }
}

