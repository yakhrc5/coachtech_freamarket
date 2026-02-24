<?php

namespace App\Actions\Fortify;

use App\Http\Requests\LoginRequest;

class ValidateLogin
{
    public function __invoke($request)
    {
        // FormRequest を手動で発火させる
        app(LoginRequest::class)->validateResolved();
    }
}
