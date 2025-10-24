<?php

use Illuminate\Http\Request;
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
        Route::post('/signin', function () {
            return response()->json(['message' => 'Crear nuevo usuario']);
        });

        //Login de usuario
        Route::post('/login', function () {
            return response()->json(['message' => 'Login de usuario']);
        });
    });
});