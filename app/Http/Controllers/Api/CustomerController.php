<?php

namespace App\Http\Controllers\Api;

use App\Actions\UpdateCustomerNames;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListCustomersRequest;
use App\Http\Requests\UpdateCustomerNamesRequest;
use App\Models\Customer;
use App\Services\CustomerNameResolver;
use App\Services\CustomerSearchService;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(ListCustomersRequest $request, CustomerSearchService $search): JsonResponse
    {
        return response()->json($search->search(
            $request->searchText(),
            $request->reviewOnly(),
            $request->limit(),
            $request->offset(),
        ));
    }

    /** Toàn bộ cột của một khách hàng, kèm bản đúng dấu. */
    public function show(Customer $customer, CustomerNameResolver $resolver): JsonResponse
    {
        return response()->json(array_merge($customer->extra ?? [], $resolver->present($customer)));
    }

    public function updateNames(
        UpdateCustomerNamesRequest $request,
        Customer $customer,
        UpdateCustomerNames $updateNames,
    ): JsonResponse {
        $updateNames($customer, $request->validated());

        return response()->json(['ok' => true]);
    }
}
