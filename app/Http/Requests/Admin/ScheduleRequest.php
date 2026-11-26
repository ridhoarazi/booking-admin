<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $field = $this->route('field');

        return [
            'date' => [
                'required', 'date', 'after_or_equal:today',
                Rule::unique('field_schedules', 'date')->where('field_id', $field?->id),
            ],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('open_time') && $this->filled('close_time')) {
                    $open = strtotime($this->input('open_time'));
                    $close = strtotime($this->input('close_time'));

                    if ($open !== false && $close !== false && ($close - $open) < 3600) {
                        $validator->errors()->add('close_time', 'Rentang jadwal minimal 1 jam.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'Jadwal untuk lapangan dan tanggal ini sudah ada.',
            'date.after_or_equal' => 'Tanggal jadwal tidak boleh di masa lalu.',
        ];
    }
}
