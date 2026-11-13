<?php

namespace App\Http\Controllers;

use App\Services\CustomerSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerPageController extends Controller
{
    public function __invoke(Request $request, CustomerSearchService $search): View
    {
        return view('customers.index', [
            'counts' => $search->counts(),
            // ?review=1 mở thẳng tab "Cần kiểm tra" (liên kết dán lại được).
            'reviewOnly' => $request->boolean('review'),
        ]);
    }
}
