<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Channel Request
 * 
 * Validates incoming requests for creating new channels.
 * Ensures proper channel type and customer UUID validation.
 * 
 * @package App\Http\Requests
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class CreateChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization is handled by the ClientAuthMiddleware.
     * 
     * @return bool Always returns true as authentication is handled by middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Validates channel type and customer UUIDs array.
     * Ensures proper format and constraints for channel creation.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Validation rules
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:general,custom',
            'customer_uuids' => 'required|array|min:2|max:5',
            'customer_uuids.*' => 'required|string|uuid|exists:customers,uuid',
        ];
    }

    /**
     * Get custom messages for validator errors.
     * 
     * Provides user-friendly error messages for validation failures.
     * 
     * @return array<string, string> Custom error messages
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Channel type is required',
            'type.in' => 'Channel type must be either general or custom',
            'customer_uuids.required' => 'At least two customers are required',
            'customer_uuids.array' => 'Customer UUIDs must be provided as an array',
            'customer_uuids.min' => 'At least two customers are required for a channel',
            'customer_uuids.max' => 'Maximum of 5 customers allowed per channel',
            'customer_uuids.*.required' => 'Customer UUID is required',
            'customer_uuids.*.uuid' => 'Customer UUID must be a valid UUID',
            'customer_uuids.*.exists' => 'One or more customers do not exist',
        ];
    }
}
