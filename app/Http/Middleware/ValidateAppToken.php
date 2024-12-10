<?php
// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;

// class ValidateAppToken
// {
//     public function handle(Request $request, Closure $next)
//     {
//         // Add your token validation logic
//         if ($request->header('Authorization') !== 'f2d4e3a0c9b22c485fbaf23c6d8174f74d8e6b9f8d7a5e9f536c4adbc84a8f5a') {
//             return response()->json(['message' => 'Unauthorized'], 401);
//         }

//         return $next($request);
//     }
// }

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
