<?php 


namespace App\Http;

use App\Http\Middleware\ValidateAppToken;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $routeMiddleware = [
        // Other middleware...
        'validate.app.token' => \App\Http\Middleware\ValidateAppToken::class,
    ];
    
}
