<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Customer::all());
    }

    /**
     * Sign up a new customer and return a token, so they don't have to log in again.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'string', 'min:8'],
            'email' => ['required', 'string', 'email', 'unique:customers,email'],
            'phone_number' => ['required', 'string', 'unique:customers,phone_number'],
        ]);

        $customer = Customer::create($data);
        $token = $customer->createToken('login')->plainTextToken;

        return response()->json([
            'customer' => $customer,
            'token' => $token,
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json($customer->load('borrowings'));
    }
}
