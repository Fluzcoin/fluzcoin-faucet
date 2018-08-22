<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Class HealthController
 */
class ApiController extends Controller
{

    public function faucet($address)
    {
        dd($address);
        return hash('sha256', config('app.xpub'));
    }
}
