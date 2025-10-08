<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Repositories\CustomerRepositoryInterface;
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
        $this->validateCustomerData($data);
        $this->ensureEmailUniqueness($client, $data['email']);
        
        $customer = $this->customerRepository->create([
            'client_id' => $client->id,
            'uuid' => Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'metadata' => $data['metadata'] ?? [],
        ]);

        return $customer;
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
    private function ensureEmailUniqueness(Client $client, string $email): void
    {
        if ($this->customerRepository->existsByEmailAndClient($email, $client)) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('email', 'Email already exists for this client');
            throw new ValidationException($validator);
        }
    }
}
