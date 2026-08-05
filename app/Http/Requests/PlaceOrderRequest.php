<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Only gateways that are enabled and hold credentials may be selected, so a
        // crafted request cannot pick an unimplemented one.
        $available = array_keys(app(PaymentGatewayManager::class)->available());

        return [
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', Rule::in($available)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.in' => 'Please choose an available payment method.',
        ];
    }
}
