<?php

namespace App\Http\Requests\Admin;

use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('store_type');

        return [
            'store_type' => ['required', Rule::in(Car::STORE_TYPES)],
            // keigayasui: title1 = maker name in latin letters (also used for the carousel id).
            // flat7_joycal: title1 = monthly price, title2 = sub text, color = header color.
            'title1' => ['required', 'string', 'max:64'],
            'title2' => ['nullable', 'string', 'max:64'],
            'maker' => [$type === 'keigayasui' ? 'required' : 'nullable', 'string', 'max:32'],
            'color' => ['nullable', 'string', 'max:32'],
            'order_slide' => ['nullable', 'integer', 'between:0,127'],
            'active' => ['required', 'boolean'],
            'cars' => ['array'],
            // Only cars of the same site can be put on a slide.
            'cars.*' => ['integer', 'distinct', Rule::exists('cars', 'id')->where('store_type', $type)->whereNull('deleted_at')],
        ];
    }
}
