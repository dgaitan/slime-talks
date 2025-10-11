<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Client;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Repository
 * 
 * Handles data access operations for customer management.
 * Provides methods for creating, retrieving, updating, and deleting customers
 * with proper client isolation and pagination support.
 * 
 * @package App\Repositories
 * @author Laravel Slime Talks
 * @version 1.0.0
 * 
 * @example
 * $repository = app(CustomerRepository::class);
 * $customer = $repository->create([
 *     'client_id' => 1,
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com'
 * ]);
 */
class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Create a new customer.
     * 
     * Creates a new customer record in the database with the provided data.
     * The customer will be automatically associated with the client specified in the data.
     * 
     * @param array $data Customer data containing client_id, name, email, and optional metadata
     * @return Customer Created customer instance
     * 
     * @example
     * $customer = $repository->create([
     *     'client_id' => 1,
     *     'uuid' => 'customer-uuid-here',
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'metadata' => ['avatar' => 'https://example.com/avatar.jpg']
     * ]);
     */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * Find customer by UUID and client.
     * 
     * Retrieves a customer by UUID, ensuring it belongs to the specified client.
     * This provides client isolation - clients can only access their own customers.
     * 
     * @param string $uuid Customer UUID to find
     * @param Client $client Client instance to scope the search
     * @return Customer Customer instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found or doesn't belong to client
     * 
     * @example
     * $customer = $repository->findByUuidAndClient('customer-uuid-here', $client);
     * echo $customer->name; // "John Doe"
     */
    public function findByUuidAndClient(string $uuid, Client $client): Customer
    {
        return Customer::where('uuid', $uuid)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    /**
     * Find customer by email and client.
     *
     * @param string $email
     * @param Client $client
     * @return Customer
     */
    public function findByEmailAndClient(string $email, Client $client): Customer
    {
        return Customer::where('email', $email)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    /**
     * Check if email exists for client.
     * 
     * Verifies if an email address already exists for a specific client.
     * Email addresses must be unique per client but can be duplicated across different clients.
     * 
     * @param string $email Email address to check
     * @param Client $client Client instance to check against
     * @return bool True if email exists for this client, false otherwise
     * 
     * @example
     * $exists = $repository->existsByEmailAndClient('john@example.com', $client);
     * if ($exists) {
     *     // Email already exists for this client
     * }
     */
    public function existsByEmailAndClient(string $email, Client $client): bool
    {
        return Customer::where('email', $email)
            ->where('client_id', $client->id)
            ->exists();
    }

    /**
     * Paginate customers for a client.
     * 
     * Retrieves a paginated list of customers belonging to the specified client.
     * Supports cursor-based pagination for efficient handling of large datasets.
     * Results are ordered by creation date in descending order (newest first).
     * 
     * @param Client $client Client instance to get customers for
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after for cursor pagination
     * @return array{data: Collection, has_more: bool, total_count: int} Paginated results
     * 
     * @example
     * $result = $repository->paginateByClient($client, 20, 'previous-customer-uuid');
     * $customers = $result['data']; // Collection of customers
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of customers for this client
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
     * Update customer.
     * 
     * Updates an existing customer with new data and returns the fresh instance.
     * The customer must belong to the authenticated client for security.
     * 
     * @param Customer $customer Customer instance to update
     * @param array $data Data to update the customer with
     * @return Customer Updated customer instance with fresh data from database
     * 
     * @example
     * $updatedCustomer = $repository->update($customer, [
     *     'name' => 'Jane Doe',
     *     'email' => 'jane@example.com'
     * ]);
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    /**
     * Delete customer.
     * 
     * Soft deletes a customer record. The customer will be marked as deleted
     * but the record will remain in the database for audit purposes.
     * 
     * @param Customer $customer Customer instance to delete
     * @return bool True if customer was successfully deleted, false otherwise
     * 
     * @example
     * $deleted = $repository->delete($customer);
     * if ($deleted) {
     *     // Customer was successfully deleted
     * }
     */
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    /**
     * Get active customers ordered by latest message activity.
     *
     * Retrieves customers who have sent messages, ordered by their
     * latest message activity. This is useful for customer-centric
     * messaging interfaces where you want to show the most active
     * customers first.
     *
     * @param Client $client Client instance to get customers for
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter Customer UUID to start after
     * @return array{data: array, has_more: bool, total_count: int} Active customers data
     *
     * @example
     * $result = $repository->getActiveCustomers($client, 20, null);
     * $customers = $result['data']; // Array of customer data
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of active customers
     */
    public function getActiveCustomers(Client $client, int $limit = 20, ?string $startingAfter = null): array
    {
        // Get customers who have sent messages, ordered by their latest message time
        $query = Customer::where('client_id', $client->id)
            ->whereHas('sentMessages')
            ->withMax('sentMessages', 'created_at')
            ->orderBy('sent_messages_max_created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($startingAfter) {
            $startingCustomer = Customer::where('uuid', $startingAfter)->first();
            if ($startingCustomer) {
                $latestMessageTime = $startingCustomer->sentMessages()->max('created_at');
                $query->where(function ($q) use ($latestMessageTime, $startingCustomer) {
                    $q->where('sent_messages_max_created_at', '<', $latestMessageTime)
                      ->orWhere(function ($q2) use ($latestMessageTime, $startingCustomer) {
                          $q2->where('sent_messages_max_created_at', $latestMessageTime)
                             ->where('id', '<', $startingCustomer->id);
                      });
                });
            }
        }

        $customers = $query->limit($limit + 1)->get();
        $hasMore = $customers->count() > $limit;
        
        if ($hasMore) {
            $customers->pop();
        }

        // Format the response data
        $data = $customers->map(function ($customer) {
            $latestMessage = $customer->sentMessages()->latest()->first();
            
            return [
                'object' => 'customer',
                'id' => $customer->uuid,
                'name' => $customer->name,
                'email' => $customer->email,
                'metadata' => $customer->metadata,
                'latest_message_at' => $latestMessage?->created_at?->timestamp,
                'created' => $customer->created_at?->timestamp,
                'livemode' => false,
            ];
        })->toArray();

        return [
            'data' => $data,
            'has_more' => $hasMore,
            'total_count' => Customer::where('client_id', $client->id)
                ->whereHas('sentMessages')
                ->count(),
        ];
    }
}
