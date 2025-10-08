<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerServiceInterface $customerService
    ) {}

    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create(
            auth('sanctum')->user(), // Client from middleware
            $request->validated()
        );

        return response()->json(new CustomerResource($customer), 201);
    }

    /**
     * Display the specified resource.
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
