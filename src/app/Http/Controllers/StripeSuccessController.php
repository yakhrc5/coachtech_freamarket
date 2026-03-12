<?php

namespace App\Http\Controllers;

class StripeSuccessController extends Controller
{
    public function success()
    {
        return redirect('/')->with('message', '購入が完了しました。');
    }
}
