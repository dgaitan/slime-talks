<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }
}
