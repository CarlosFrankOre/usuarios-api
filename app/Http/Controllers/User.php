<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Stmt\TryCatch;
use App\Services\JWTService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class User extends Controller
{
    public function register(Request $request)
    {
        //Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|min:3',
            'lastname' => 'required|string|max:100|min:3',
            'email' => 'required|email|max:100|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:manager,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => $validator->errors()
            ], 422);
        }

        try {
            //Crear el usuario
            $user = UserModel::create([
                'name' => $request->name,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'user',
                'is_active' => true,
                'login_attempts' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'database_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request, JWTService $jwtService)
    {
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => $validator->errors()
            ], 422);
        }

        try{
            // Lógica de autenticación aquí. Buscar el usuario y por email
            $user = UserModel::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'user_not_found',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            //Verificar si el usuario está activo
            if (!$user->is_active) {
                return response()->json([
                    'status' => 'user_not_active',
                    'message' => 'Account is inactive. Please contact support.'
                ], 403);
            }

            //Verificar si la cuenta está bloqueada por intentos fallidos
            if ($user->login_attempts >= 5) {
                return response()->json([
                    'status' => 'account_locked',
                    'message' => 'Account is locked due to multiple failed login attempts. Please try again later.'
                ], 403);
            }

            //Verificar la contraseña
            if (!Hash::check($request->password, $user->password)) {
                //Incrementar los intentos de login fallidos
                $user->increment('login_attempts');
                return response()->json([
                    'status' => 'invalid_credentials',
                    'message' => 'Invalid email or password',
                    'attempts_remaining' => max(0, 5 - $user->login_attempts)//si se tarda mucho es porque max es una función nativa de php
                ], 401);
            }

            //Resetear los intentos de login fallidos
            $user->login_attempts = 0;
            $user->last_login = now();
            $user->save();

            // Generar y retornar tokens JWT aquí (lógica no implementada en este snippet)
            $tokenPayload = [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ];

            $tokens = $jwtService->generateTokenPair($tokenPayload);

            return response()->json([
                'status' => 'success',
                'message' => 'User logged in successfully',
                'data' => $tokens
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'database_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getUserById(Request $request, $id)
    {
        try {
            // Buscar el usuario por ID
            $user = UserModel::find($id);

            // Verificar si el usuario existe
            if (!$user) {
                return response()->json([
                    'status' => 'user_not_found',
                    'message' => 'User not found'
                ], 404);
            }

            // Retornar los datos del usuario 
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'login_attempts' => $user->login_attempts,
                    'last_login' => $user->last_login,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'database_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //Metodo para mostrar todos los usuarios (para pruebas)
    public function index()
    {
        $users = UserModel::all();
        return response()->json(['data' => $users], 200);
    }

    // Método para actualizar usuario
    public function update(Request $request, $id)
    {
        $user = UserModel::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'user_not_found',
                'message' => 'User not found'
            ], 404);
        }

        // Validar y actualizar los datos del usuario
        $user->fill($request->only(['name', 'lastname', 'email', 'role', 'is_active']));
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ], 200);
    }

    // Método para eliminar usuario
    public function destroy($id)
    {
        $user = UserModel::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'user_not_found',
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ], 200);
    }

    //Obtener el número de usuarios por dia, semana y mes (para estadísticas). Mostrar el numero de usuarios por fecha de creación, por semana y por mes
    //Obtener el número de usuarios por dia, semana y mes (para estadísticas). Mostrar el numero de usuarios por fecha de creación, por semana y por mes
    public function getStatistics()
    {
        // Usar una única instancia de tiempo para evitar inconsistencias
        $now = Carbon::now();

        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $startOfWeek = $now->copy()->startOfWeek()->startOfDay();
        $endOfWeek = $now->copy()->endOfWeek()->endOfDay();

        $startOfMonth = $now->copy()->startOfMonth()->startOfDay();
        $endOfMonth = $now->copy()->endOfMonth()->endOfDay();

        // Conteos exactos usando rangos con start/end day
        $todayCount = UserModel::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $weekCount = UserModel::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $monthCount = UserModel::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Agrupar por fecha (DATE(created_at)) y ordenar
        $usersByDay = UserModel::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        $usersByWeek = UserModel::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

            // Mostrar los datos agrupados por la semana (Lunes a Domingo)
            $usersByWeek = $usersByWeek->map(function ($item) {
                $date = Carbon::parse($item->date);
                $weekStart = $date->copy()->startOfWeek()->format('Y-m-d');
                $weekEnd = $date->copy()->endOfWeek()->format('Y-m-d');
                $item->date = $weekStart . ' to ' . $weekEnd;
                return $item;
            })->groupBy('date')->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'count' => $items->sum('count')
                ];
            })->values();

           //Contar usuarios por mes, agrupados por mes
         $usersByMonth = UserModel::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

       // Mostrar los datos del mes agrupados por el nombre del mes y no por fecha. Mostrar el conteo de todos los meses, no solo del mes actual
       // Si hay datos del mismo mes, agruparlos y mostrarlos como "January 2024", "February 2024", etc.
       $usersByMonth = $usersByMonth->map(function ($item) {    
           $item->date = Carbon::parse($item->date)->format('F Y'); // Formatear a nombre del mes y año
           return $item;
       });

       $usersByMonth = $usersByMonth->groupBy('date')->map(function ($items, $date) {
           return [
               'date' => $date,
               'count' => $items->sum('count')
           ];
       })->values();


        $data = [
            'today' => $todayCount,
            'this_week' => $weekCount,
            'this_month' => $monthCount,
            'users_by_day' => $usersByDay,
            'users_by_week' => $usersByWeek,
            'users_by_month' => $usersByMonth,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}
