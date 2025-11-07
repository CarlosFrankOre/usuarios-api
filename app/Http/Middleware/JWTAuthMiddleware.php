<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JWTService;
use Exception;

class JWTAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el token de la cabecera Authorization
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'status' => 'not_authenticated',
                'message' => 'Missing or invalid Authorization header'
            ], 401);
        }

        //Extraer el token (Remover "Bearer " al inicio)

        $token = substr($authHeader, strlen('Bearer '));
        try {
            // Decodificar y validar el access token
            $decoded = $this->jwtService->decodeAccessToken($token);
            // Verificar que sea un access token válido
            if($decoded->type !== 'access_token') {
                return response()->json([
                    'status' => 'unauthorized',
                    'message' => 'Invalid token type'
                ], 401);
            }
            
            return $next($request);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid or expired token' . $e->getMessage()
            ], 401);
        }
    }
}
