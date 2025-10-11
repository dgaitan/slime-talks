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

    /**
     * Get active customers ordered by latest message activity
     *
     * @param Client $client The client requesting customers
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after
     * @return array{data: array, has_more: bool, total_count: int}
     */
    public function getActiveCustomers(Client $client, int $limit = 20, ?string $startingAfter = null): array;

    /**
     * Get active customers for a specific sender
     * 
     * Returns customers who have exchanged messages with the specified sender
     *
     * @param Client $client The client requesting customers
     * @param string $senderEmail Email of the sender to filter by
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after
     * @return array{data: array, has_more: bool, total_count: int}
     */
    public function getActiveCustomersForSender(Client $client, string $senderEmail, int $limit = 20, ?string $startingAfter = null): array;
}
