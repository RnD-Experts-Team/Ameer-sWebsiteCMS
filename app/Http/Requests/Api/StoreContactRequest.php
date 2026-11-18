<?php

namespace App\Http\Requests\Api;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContactRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
                if (PhoneNumber::digitCount((string) $value) < 10) {
                    $fail('Please enter a phone number with at least 10 digits.');
                }
            }],
            'email' => ['required', 'string', 'email', 'max:254'],
            'projectType' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'min:5', 'max:120'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'humanToken' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.*' => 'Please enter your full name.',
            'email.*' => 'Please enter a valid email address.',
            'address.*' => 'Please enter the project address or ZIP code.',
            'message.*' => 'Please tell us a little more about your project.',
            'humanToken.*' => 'Please confirm you are human and try again.',
        ];
    }

    /**
     * The front end shows one short message, not per-field errors. Sent with
     * HTTP 200, because it treats any other status as a network failure.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'error' => $validator->errors()->first(),
        ]));
    }
}
