<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Client ' . rand(100, 999),
            'domain' => 'example' . rand(100, 999) . '.com',
            'public_key' => 'test-public-key-' . uniqid(),
            'allowed_ips' => [
                '192.168.1.1',
                '10.0.0.1',
            ],
            'allowed_subdomains' => [
                'api',
                'admin',
            ],
        ];
    }
}
