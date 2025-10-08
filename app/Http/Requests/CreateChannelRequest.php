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
     * Validates channel type, customer UUIDs array, and custom channel name.
     * Ensures proper format and constraints for channel creation.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Validation rules
     */
    public function rules(): array
    {
        $rules = [
            'type' => 'required|string|in:general,custom',
            'customer_uuids' => 'required|array|min:2|max:5',
            'customer_uuids.*' => 'required|string|uuid|exists:customers,uuid',
        ];

        // Add name validation for custom channels
        if ($this->input('type') === 'custom') {
            $rules['name'] = 'required|string|max:255|unique:channels,name,NULL,id,client_id,' . auth('sanctum')->id();
        }

        return $rules;
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
            'name.required' => 'Channel name is required for custom channels',
            'name.string' => 'Channel name must be a string',
            'name.max' => 'Channel name cannot exceed 255 characters',
            'name.unique' => 'A channel with this name already exists for your client',
        ];
    }
}
