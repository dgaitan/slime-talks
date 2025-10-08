<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Customer Controller
 * 
 * Handles HTTP requests for customer management operations.
 * Provides RESTful endpoints for creating, retrieving, and listing customers.
 * All operations are scoped to the authenticated client.
 * 
 * @package App\Http\Controllers
 * @author Laravel Slime Talks
 * @version 1.0.0
 * 
 * @example
 * // Create a customer
 * POST /api/v1/customers
 * {
 *     "name": "John Doe",
 *     "email": "john@example.com",
 *     "metadata": {"avatar": "https://example.com/avatar.jpg"}
 * }
 */
class CustomerController extends Controller
{
    /**
     * Create a new CustomerController instance.
     * 
     * Injects the customer service dependency for handling business logic.
     * 
     * @param CustomerServiceInterface $customerService Service for customer operations
     */
    public function __construct(
        private CustomerServiceInterface $customerService
    ) {}

    /**
     * Display a listing of customers for the authenticated client.
     * 
     * Retrieves a paginated list of customers belonging to the authenticated client.
     * Supports cursor-based pagination using the 'starting_after' parameter.
     * 
     * @param Request $request HTTP request containing pagination parameters
     * @return JsonResponse JSON response with customer list and pagination info
     * 
     * @throws \Illuminate\Auth\AuthenticationException When client is not authenticated
     * 
     * @example
     * GET /api/v1/customers?limit=20&starting_after=customer_uuid_here
     * 
     * Response:
     * {
     *     "object": "list",
     *     "data": [...],
     *     "has_more": true,
     *     "total_count": 150
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);
        $startingAfter = $request->get('starting_after');

        $result = $this->customerService->list(
            auth('sanctum')->user(), // Client from middleware
            $limit,
            $startingAfter
        );

        return response()->json([
            'object' => 'list',
            'data' => CustomerResource::collection($result['data']),
            'has_more' => $result['has_more'],
            'total_count' => $result['total_count'],
        ]);
    }

    /**
     * Store a newly created customer in storage.
     * 
     * Creates a new customer for the authenticated client.
     * Validates the request data and ensures email uniqueness within the client.
     * 
     * @param CreateCustomerRequest $request Validated request containing customer data
     * @return JsonResponse JSON response with created customer data
     * 
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Illuminate\Auth\AuthenticationException When client is not authenticated
     * 
     * @example
     * POST /api/v1/customers
     * {
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "metadata": {"avatar": "https://example.com/avatar.jpg"}
     * }
     * 
     * Response (201):
     * {
     *     "object": "customer",
     *     "id": "customer_uuid",
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "metadata": {...},
     *     "created": 1640995200
     * }
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $client = auth('sanctum')->user();
        $validatedData = $request->validated();

        try {
            $customer = $this->customerService->create($client, $validatedData);
            
            return response()->json(new CustomerResource($customer), 201);

        } catch (\Exception $e) {
            Log::error('Customer creation request failed', [
                'client_id' => $client->id,
                'client_uuid' => $client->uuid,
                'customer_name' => $validatedData['name'] ?? 'unknown',
                'customer_email' => $validatedData['email'] ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Display the specified customer.
     * 
     * Retrieves a single customer by UUID for the authenticated client.
     * Only returns customers that belong to the authenticated client.
     * 
     * @param string $uuid Customer UUID to retrieve
     * @return JsonResponse JSON response with customer data
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When customer not found
     * @throws \Illuminate\Auth\AuthenticationException When client is not authenticated
     * 
     * @example
     * GET /api/v1/customers/customer_uuid_here
     * 
     * Response (200):
     * {
     *     "object": "customer",
     *     "id": "customer_uuid",
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "metadata": {...},
     *     "created": 1640995200
     * }
     */
    public function show(string $uuid): JsonResponse
    {
        $customer = $this->customerService->getByUuid(
            auth('sanctum')->user(), // Client from middleware
            $uuid
        );

        return response()->json(new CustomerResource($customer));
    }
}
