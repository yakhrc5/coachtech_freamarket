<?php

namespace App\Http\Controllers;

class StripeSuccessController extends Controller
{
    public function success()
    {
        return redirect('/');
    }
}
