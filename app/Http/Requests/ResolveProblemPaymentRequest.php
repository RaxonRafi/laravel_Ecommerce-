<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Recording a payment by hand is the one place money can be marked received
 * without a gateway saying so. The reference is mandatory for exactly that
 * reason: every manual settlement has to point at something checkable.
 */
class ResolveProblemPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware handles the admin check.
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'Record the payment reference (bKash transaction id, bank slip number, receipt number) so this settlement can be traced.',
        ];
    }
}
