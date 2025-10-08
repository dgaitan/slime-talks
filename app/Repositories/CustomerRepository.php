<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Client;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Create a new customer
     *
     * @param array $data Customer data
     * @return Customer Created customer
     */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * Find customer by UUID and client
     *
     * @param string $uuid Customer UUID
     * @param Client $client Client instance
     * @return Customer
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found
     */
    public function findByUuidAndClient(string $uuid, Client $client): Customer
    {
        return Customer::where('uuid', $uuid)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    /**
     * Check if email exists for client
     *
     * @param string $email Email to check
     * @param Client $client Client instance
     * @return bool True if email exists
     */
    public function existsByEmailAndClient(string $email, Client $client): bool
    {
        return Customer::where('email', $email)
            ->where('client_id', $client->id)
            ->exists();
    }

    /**
     * Paginate customers for a client
     *
     * @param Client $client Client instance
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after
     * @return array{data: Collection, has_more: bool, total_count: int}
     */
    public function paginateByClient(Client $client, int $limit, ?string $startingAfter = null): array
    {
        $query = Customer::where('client_id', $client->id)
            ->orderBy('created_at', 'desc');

        if ($startingAfter) {
            $startingCustomer = Customer::where('uuid', $startingAfter)->first();
            if ($startingCustomer) {
                $query->where('created_at', '<', $startingCustomer->created_at);
            }
        }

        $customers = $query->limit($limit + 1)->get();
        $hasMore = $customers->count() > $limit;
        
        if ($hasMore) {
            $customers->pop();
        }

        return [
            'data' => $customers,
            'has_more' => $hasMore,
            'total_count' => Customer::where('client_id', $client->id)->count(),
        ];
    }

    /**
     * Update customer
     *
     * @param Customer $customer Customer to update
     * @param array $data Data to update
     * @return Customer Updated customer
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    /**
     * Delete customer
     *
     * @param Customer $customer Customer to delete
     * @return bool True if deleted
     */
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }
}
