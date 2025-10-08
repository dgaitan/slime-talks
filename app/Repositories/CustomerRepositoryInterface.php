<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Client;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    /**
     * Create a new customer
     *
     * @param array $data Customer data
     * @return Customer Created customer
     */
    public function create(array $data): Customer;

    /**
     * Find customer by UUID and client
     *
     * @param string $uuid Customer UUID
     * @param Client $client Client instance
     * @return Customer
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found
     */
    public function findByUuidAndClient(string $uuid, Client $client): Customer;

    /**
     * Check if email exists for client
     *
     * @param string $email Email to check
     * @param Client $client Client instance
     * @return bool True if email exists
     */
    public function existsByEmailAndClient(string $email, Client $client): bool;

    /**
     * Paginate customers for a client
     *
     * @param Client $client Client instance
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after
     * @return array{data: Collection, has_more: bool, total_count: int}
     */
    public function paginateByClient(Client $client, int $limit, ?string $startingAfter = null): array;

    /**
     * Update customer
     *
     * @param Customer $customer Customer to update
     * @param array $data Data to update
     * @return Customer Updated customer
     */
    public function update(Customer $customer, array $data): Customer;

    /**
     * Delete customer
     *
     * @param Customer $customer Customer to delete
     * @return bool True if deleted
     */
    public function delete(Customer $customer): bool;

    public function findByEmailAndClient(string $email, Client $client): Customer;
}
