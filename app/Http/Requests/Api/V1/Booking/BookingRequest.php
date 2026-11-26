<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'field_slot_id' => ['required_without:field_slot_ids', 'integer', 'exists:field_slots,id', 'prohibits:field_slot_ids'],
            'field_slot_ids' => ['required_without:field_slot_id', 'array', 'min:1', 'max:8'],
            'field_slot_ids.*' => ['integer', 'distinct', 'exists:field_slots,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'field_slot_id.required_without' => 'Slot waktu wajib dipilih.',
            'field_slot_id.exists' => 'Slot waktu tidak ditemukan.',
            'field_slot_id.prohibits' => 'Gunakan salah satu: field_slot_id atau field_slot_ids.',
            'field_slot_ids.required_without' => 'Slot waktu wajib dipilih.',
            'field_slot_ids.array' => 'Daftar slot waktu tidak valid.',
            'field_slot_ids.min' => 'Minimal satu slot waktu harus dipilih.',
            'field_slot_ids.max' => 'Maksimal 8 slot waktu dalam satu booking.',
            'field_slot_ids.*.integer' => 'Slot waktu tidak valid.',
            'field_slot_ids.*.distinct' => 'Slot waktu tidak boleh dipilih lebih dari sekali.',
            'field_slot_ids.*.exists' => 'Slot waktu tidak ditemukan.',
        ];
    }

    /**
     * Daftar ID slot yang diminta, terurut dan unik.
     *
     * @return array<int, int>
     */
    public function slotIds(): array
    {
        $ids = $this->input('field_slot_ids') ?? $this->input('field_slot_id');

        return collect(Arr::wrap($ids))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
