<?php

use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::get('/public', function (Request $request) {
//     return response()->json(['message' => 'Hola']);
// })->middleware('api.key');

Route::middleware('api.key')->group(function () {
    //Agregar prefijo a las rutas
    Route::prefix('v1/user')->group(function () {
        //Nuevo usuario
        Route::post('/signin', [User::class, 'register']);

        //Login de usuario
        Route::post('/login', [User::class, 'login']);

        // Obtener usuario por id
        Route::get('/{id}', [User::class, 'getUserById'])->middleware('jwt.auth');

        // Actualizar usuario
        Route::put('/{id}', [User::class, 'update'])->middleware('jwt.auth');

        // Eliminar usuario
        Route::delete('/{id}', [User::class, 'destroy'])->middleware('jwt.auth');

        // Obtener estadísticas de usuarios
        Route::get('/', [User::class, 'getStatistics'])->middleware('jwt.auth');
    });
});