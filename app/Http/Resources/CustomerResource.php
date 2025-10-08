<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'object' => 'customer',
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'metadata' => $this->metadata ?? [],
            'created' => $this->created_at?->timestamp,
            'livemode' => false, // TODO: Implement livemode logic
        ];
    }
}
