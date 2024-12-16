<?php
 

 namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateAppToken
{
    public function handle(Request $request, Closure $next)
    {
        // Check for a valid app token (example logic)
        $token = $request->header('App-Token');
        $validToken = env('APP_API_TOKEN');
        if (!$token || $token !==$validToken) {
            // Optionally, return an unauthorized response
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Proceed with the request
        return $next($request);
    }
}
