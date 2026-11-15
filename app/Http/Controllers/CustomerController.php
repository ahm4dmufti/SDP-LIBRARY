<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'string', 'min:8'],
            'email' => ['required', 'string', 'email', 'unique:customers,email'],
            'phone_number' => ['required', 'string', 'unique:customers,phone_number'],
        ]);

        $customer = Customer::create($validatedData);

        return response()->json([
            'status' => 'success',
            'customer' => $customer,
        ], 201);
    }
}
