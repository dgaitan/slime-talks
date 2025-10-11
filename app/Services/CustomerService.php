<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Repositories\CustomerRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Customer Service
 * 
 * Handles business logic for customer management operations.
 * Provides methods for creating, retrieving, and listing customers with proper validation.
 * All operations are scoped to specific clients for data isolation.
 * 
 * @package App\Services
 * @author Laravel Slime Talks
 * @version 1.0.0
 * 
 * @example
 * $service = app(CustomerService::class);
 * $customer = $service->create($client, [
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com'
 * ]);
 */
class CustomerService implements CustomerServiceInterface
{
    /**
     * Create a new CustomerService instance.
     * 
     * Injects the customer repository dependency for data access operations.
     * 
     * @param CustomerRepositoryInterface $customerRepository Repository for customer data operations
     */
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Create a new customer for a client.
     * 
     * Validates customer data, ensures email uniqueness within the client,
     * and creates a new customer record with a generated UUID.
     * 
     * @param Client $client The client creating the customer
     * @param array $data Customer data containing name, email, and optional metadata
     * @return Customer Created customer instance
     * @throws ValidationException When validation fails or email already exists
     * 
     * @example
     * $customer = $service->create($client, [
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'metadata' => ['avatar' => 'https://example.com/avatar.jpg']
     * ]);
     */
    public function create(Client $client, array $data): Customer
    {
        try {
            $this->validateCustomerData($data);
            $exists = $this->ensureEmailUniqueness($client, $data['email'], false);
            if ($exists) {
                return $this->findByEmail($client, $data['email']);
            }
            
            $customerData = [
                'client_id' => $client->id,
                'uuid' => Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'metadata' => $data['metadata'] ?? [],
            ];
            
            $customer = $this->customerRepository->create($customerData);

            return $customer;

        } catch (ValidationException $e) {
            Log::warning('Customer creation failed due to validation error', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'customer_name' => $data['name'] ?? 'unknown',
                'customer_email' => $data['email'] ?? 'unknown',
                'validation_errors' => $e->errors(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('Customer creation failed with unexpected error', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'customer_name' => $data['name'] ?? 'unknown',
                'customer_email' => $data['email'] ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get customer by UUID for a specific client.
     * 
     * Retrieves a customer by UUID, ensuring it belongs to the specified client.
     * This provides client isolation - clients can only access their own customers.
     * 
     * @param Client $client The client requesting the customer
     * @param string $uuid Customer UUID to retrieve
     * @return Customer Customer instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found or doesn't belong to client
     * 
     * @example
     * $customer = $service->getByUuid($client, 'customer-uuid-here');
     * echo $customer->name; // "John Doe"
     */
    public function getByUuid(Client $client, string $uuid): Customer
    {
        return $this->customerRepository->findByUuidAndClient($uuid, $client);
    }

    /**
     * Find customer by email and client.
     *
     * @param Client $client
     * @param string $email
     * @return Customer
     */
    public function findByEmail(Client $client, string $email): Customer
    {
        return $this->customerRepository->findByEmailAndClient($email, $client);
    }

    /**
     * List customers for a client with pagination.
     * 
     * Retrieves a paginated list of customers belonging to the specified client.
     * Supports cursor-based pagination for efficient large dataset handling.
     * 
     * @param Client $client The client requesting customers
     * @param int $limit Number of customers per page (default: 10)
     * @param string|null $startingAfter UUID to start after for cursor pagination
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int} Paginated results
     * 
     * @example
     * $result = $service->list($client, 20, 'previous-customer-uuid');
     * $customers = $result['data']; // Collection of customers
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of customers for this client
     */
    public function list(Client $client, int $limit = 10, ?string $startingAfter = null): array
    {
        return $this->customerRepository->paginateByClient($client, $limit, $startingAfter);
    }

    /**
     * Validate customer data.
     * 
     * Performs basic validation on customer data including required fields
     * and email format validation.
     * 
     * @param array $data Customer data to validate
     * @return void
     * @throws ValidationException When validation fails
     * 
     * @example
     * $this->validateCustomerData([
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com'
     * ]); // Throws ValidationException if invalid
     */
    private function validateCustomerData(array $data): void
    {
        if (empty($data['name'])) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('name', 'Name is required');
            throw new ValidationException($validator);
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('email', 'Valid email is required');
            throw new ValidationException($validator);
        }
    }

    /**
     * Ensure email uniqueness within client.
     * 
     * Checks if the email already exists for the specified client.
     * Email addresses must be unique per client but can be duplicated across different clients.
     * 
     * @param Client $client The client to check email uniqueness for
     * @param string $email Email address to check
     * @return void
     * @throws ValidationException When email already exists for this client
     * 
     * @example
     * $this->ensureEmailUniqueness($client, 'john@example.com');
     * // Throws ValidationException if email already exists for this client
     */
    private function ensureEmailUniqueness(Client $client, string $email, bool $raiseException = true): bool
    {
        if ($this->customerRepository->existsByEmailAndClient($email, $client)) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('email', 'Email already exists for this client');
            if ($raiseException) {
                throw new ValidationException($validator);
            }

            return true;
        }

        return false;
    }

    /**
     * Get active customers ordered by latest message activity.
     *
     * Retrieves customers who have sent messages, ordered by their
     * latest message activity. This is useful for customer-centric
     * messaging interfaces where you want to show the most active
     * customers first.
     *
     * @param Client $client The client requesting the customers
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter Customer UUID to start after
     * @return array{data: array, has_more: bool, total_count: int} Active customers data
     *
     * @example
     * $result = $service->getActiveCustomers($client, 20, null);
     * $customers = $result['data']; // Array of customer data
     * $hasMore = $result['has_more']; // Boolean indicating if more results exist
     * $totalCount = $result['total_count']; // Total number of active customers
     */
    public function getActiveCustomers(Client $client, int $limit = 20, ?string $startingAfter = null): array
    {
        return $this->customerRepository->getActiveCustomers($client, $limit, $startingAfter);
    }

    /**
     * Get active customers for a specific sender.
     *
     * Returns customers who have exchanged messages with the specified sender,
     * ordered by the latest message activity between them. This is perfect for
     * building personalized conversation sidebars where a user sees only the
     * people they've communicated with.
     *
     * @param Client $client The client requesting the customers
     * @param string $senderEmail Email of the sender to filter by
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter Customer UUID to start after
     * @return array{data: array, has_more: bool, total_count: int} Active customers data
     *
     * @example
     * $result = $service->getActiveCustomersForSender($client, 'sender@example.com', 20);
     * $customers = $result['data']; // Customers who talked with sender
     */
    public function getActiveCustomersForSender(Client $client, string $senderEmail, int $limit = 20, ?string $startingAfter = null): array
    {
        return $this->customerRepository->getActiveCustomersForSender($client, $senderEmail, $limit, $startingAfter);
    }
}
