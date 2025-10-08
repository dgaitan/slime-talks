<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Message Request
 *
 * Validates incoming message creation requests.
 * Ensures all required fields are present and properly formatted.
 *
 * @package App\Http\Requests
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class CreateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if authorized
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channel_uuid' => 'required|string|exists:channels,uuid',
            'sender_uuid' => 'required|string|exists:customers,uuid',
            'type' => 'required|string|in:text,image,file',
            'content' => 'required|string|min:1',
            'metadata' => 'nullable|array',
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
            'channel_uuid.required' => 'Channel UUID is required',
            'channel_uuid.exists' => 'Channel does not exist',
            'sender_uuid.required' => 'Sender UUID is required',
            'sender_uuid.exists' => 'Sender does not exist',
            'type.required' => 'Message type is required',
            'type.in' => 'Message type must be text, image, or file',
            'content.required' => 'Message content is required',
            'content.min' => 'Message content cannot be empty',
        ];
    }
}
