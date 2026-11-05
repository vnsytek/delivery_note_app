<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class CustomerPageController extends Controller
{
    public function __invoke(): View
    {
        return view('customers.index');
    }
}
