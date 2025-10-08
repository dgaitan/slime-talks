<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerServiceInterface
{
    /**
     * Create a new customer for a client
     *
     * @param Client $client The client creating the customer
     * @param array $data Customer data
     * @return Customer Created customer
     * @throws \Illuminate\Validation\ValidationException When validation fails
     */
    public function create(Client $client, array $data): Customer;

    /**
     * Get customer by UUID for a specific client
     *
     * @param Client $client The client requesting the customer
     * @param string $uuid Customer UUID
     * @return Customer
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found
     */
    public function getByUuid(Client $client, string $uuid): Customer;

    /**
     * List customers for a client with pagination
     *
     * @param Client $client The client requesting customers
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after
     * @return array{data: Collection, has_more: bool, total_count: int}
     */
    public function list(Client $client, int $limit = 10, ?string $startingAfter = null): array;
}
