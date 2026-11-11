<?php

namespace App\Http\Requests\Admin;

use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->route('car') === null;

        return [
            'store_type' => ['required', Rule::in(Car::STORE_TYPES)],
            'brand' => ['required', 'string', 'max:128'],
            'maker' => ['required', 'string', 'max:128'],
            'grade' => ['required', 'string', 'max:128'],
            'fuel' => ['nullable', 'string', 'max:128'],
            'price' => ['nullable', 'string', 'max:32'],
            'total' => ['nullable', 'string', 'max:32'],
            'monthly' => ['nullable', 'string', 'max:32'],
            'new' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'max:2048'],
        ];
    }
}
