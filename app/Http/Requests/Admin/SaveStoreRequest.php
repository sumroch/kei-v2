<?php

namespace App\Http\Requests\Admin;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Drop blank rows of the bcc list editor.
        $this->merge([
            'email_bcc' => array_values(array_filter((array) $this->input('email_bcc', []), fn ($v) => trim((string) $v) !== '')),
        ]);
    }

    public function rules(): array
    {
        $store = $this->route('store');

        return [
            'name' => ['required', 'string', 'max:64'],
            'name_id' => ['required', 'string', 'max:64'],
            'slug' => ['required', 'alpha_dash', 'max:32', Rule::unique('stores', 'slug')->ignore($store)],
            'brand' => ['required', Rule::in(Store::BRANDS)],
            'email' => ['required', 'email', 'max:32'],
            'email_bcc' => ['array'],
            'email_bcc.*' => ['email', 'max:128', 'distinct'],
            'postal_code' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:256'],
            'phone_number' => ['required', 'string', 'max:32'],
            'phone_number_2' => ['nullable', 'string', 'max:64'],
            'operational' => ['required', 'string', 'max:64'],
        ];
    }
}
