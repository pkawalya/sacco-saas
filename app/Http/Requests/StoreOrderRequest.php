<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'organization_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'plan_tier' => ['required', 'in:starter,growth,enterprise'],
            'billing_cycle' => ['required', 'in:monthly,annual'],
            'member_count' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_name.required' => 'Please enter your SACCO name.',
            'contact_person.required' => 'We need a contact person to reach out to.',
            'email.required' => 'A valid email is required to send your credentials.',
            'phone.required' => 'A phone number is required for account setup.',
            'plan_tier.required' => 'Please select a plan.',
        ];
    }
}
