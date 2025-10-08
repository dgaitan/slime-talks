<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Repositories\CustomerRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Create a new customer for a client
     *
     * @param Client $client The client creating the customer
     * @param array $data Customer data
     * @return Customer Created customer
     * @throws ValidationException When validation fails
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
     * Get customer by UUID for a specific client
     *
     * @param Client $client The client requesting the customer
     * @param string $uuid Customer UUID
     * @return Customer
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found
     */
    public function getByUuid(Client $client, string $uuid): Customer
    {
        return $this->customerRepository->findByUuidAndClient($uuid, $client);
    }

    /**
     * List customers for a client with pagination
     *
     * @param Client $client The client requesting customers
     * @param int $limit Number of customers per page
     * @param string|null $startingAfter UUID to start after
     * @return array{data: \Illuminate\Database\Eloquent\Collection, has_more: bool, total_count: int}
     */
    public function list(Client $client, int $limit = 10, ?string $startingAfter = null): array
    {
        return $this->customerRepository->paginateByClient($client, $limit, $startingAfter);
    }

    /**
     * Validate customer data
     *
     * @param array $data Customer data to validate
     * @throws ValidationException When validation fails
     */
    private function validateCustomerData(array $data): void
    {
        if (empty($data['name'])) {
            throw new ValidationException('Name is required');
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Valid email is required');
        }
    }

    /**
     * Ensure email uniqueness within client
     *
     * @param Client $client The client
     * @param string $email Email to check
     * @throws ValidationException When email already exists
     */
    private function ensureEmailUniqueness(Client $client, string $email): void
    {
        if ($this->customerRepository->existsByEmailAndClient($email, $client)) {
            throw new ValidationException('Email already exists for this client');
        }
    }
}
