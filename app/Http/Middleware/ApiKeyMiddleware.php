<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        //obtener la api key del archivo .env
        $validApiKey = config('app.api_key');
        //comprobación
        if (!$apiKey || $apiKey !== $validApiKey) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'Invalida or missing API key'], 401);
        }
        return $next($request);
    }
}
