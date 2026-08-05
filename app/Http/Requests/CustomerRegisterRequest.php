<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'indisposable'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account already exists for this email address.',
            'email.indisposable' => 'Disposable email addresses are not accepted.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
