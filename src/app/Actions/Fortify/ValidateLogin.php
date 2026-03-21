<?php

namespace App\Actions\Fortify;

use App\Http\Requests\LoginRequest;

class ValidateLogin
{
    public function __invoke($request)
    {
        // FormRequestのバリデーションを実行
        app(LoginRequest::class)->validateResolved();
    }
}
