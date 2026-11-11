<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SavePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->route('photo') === null;

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'initial' => ['required', 'string', 'max:128'],
            'maker' => ['required', 'string', 'max:128'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'max:4096'],
        ];
    }
}
