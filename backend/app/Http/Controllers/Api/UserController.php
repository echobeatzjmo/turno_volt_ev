<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Consultar los usuarios registrados.
     */
    public function index(Request $request)
    {
        $authenticatedUser = $request->user();

        // Verificar que el usuario tenga permisos administrativos.
        if (!in_array($authenticatedUser->role, [
            'administrador_avanzado',
            'administrador',
        ])) {
            return response()->json([
                'message' => 'No tienes permisos para consultar usuarios.',
            ], 403);
        }

        // Construir la consulta de usuarios.
        $query = User::query();

        // El administrador del edificio solo consulta residentes y operadores.
        if ($authenticatedUser->role === 'administrador') {
            $query->whereIn('role', ['usuario', 'operador']);
        }

        $users = $query
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Usuarios consultados correctamente.',
            'data' => $users,
        ]);
    }

    /**
     * Crear un nuevo usuario residente u operador.
     */
    public function store(Request $request)
    {
        $authenticatedUser = $request->user();

        // Verificar permisos administrativos.
        if (!in_array($authenticatedUser->role, [
            'administrador_avanzado',
            'administrador',
        ])) {
            return response()->json([
                'message' => 'No tienes permisos para crear usuarios.',
            ], 403);
        }

        // Validar los datos recibidos.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:usuario,operador'],
        ]);

        // Crear la cuenta con el rol autorizado.
        $user = User::create($data);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $user,
        ], 201);
    }

    /**
     * Consultar un usuario específico.
     */
    public function show(Request $request, User $user)
    {
        $authenticatedUser = $request->user();

        // Verificar permisos administrativos.
        if (!in_array($authenticatedUser->role, [
            'administrador_avanzado',
            'administrador',
        ])) {
            return response()->json([
                'message' => 'No tienes permisos para consultar usuarios.',
            ], 403);
        }

        // El administrador solo puede consultar residentes y operadores.
        if (
            $authenticatedUser->role === 'administrador' &&
            !in_array($user->role, ['usuario', 'operador'])
        ) {
            return response()->json([
                'message' => 'No tienes permisos para consultar esta cuenta.',
            ], 403);
        }

        return response()->json([
            'message' => 'Usuario consultado correctamente.',
            'data' => $user,
        ]);
    }

    /**
     * Actualizar los datos de un usuario.
     */
    public function update(Request $request, User $user)
    {
        $authenticatedUser = $request->user();

        // Verificar permisos administrativos.
        if (!in_array($authenticatedUser->role, [
            'administrador_avanzado',
            'administrador',
        ])) {
            return response()->json([
                'message' => 'No tienes permisos para modificar usuarios.',
            ], 403);
        }

        // El administrador solo puede modificar residentes y operadores.
        if (
            $authenticatedUser->role === 'administrador' &&
            !in_array($user->role, ['usuario', 'operador'])
        ) {
            return response()->json([
                'message' => 'No tienes permisos para modificar esta cuenta.',
            ], 403);
        }

        // Validar los datos recibidos.
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', 'in:usuario,operador'],
        ]);

        // Actualizar el usuario.
        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Eliminar un usuario (Solo lo desactivará).
     */
    public function destroy(Request $request, User $user)
    {
        $authenticatedUser = $request->user();

        if (!in_array($authenticatedUser->role, [
            'administrador_avanzado',
            'administrador'
        ])) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        // Evitar que el usuario desactive su propia cuenta.
        if ($authenticatedUser->id === $user->id) {
            return response()->json([
                'message' => 'No puedes desactivar tu propia cuenta.'
            ], 403);
        }

        // El administrador solo puede gestionar usuarios y operadores.
        if (
            $authenticatedUser->role === 'administrador' &&
            !in_array($user->role, ['usuario', 'operador'])
        ) {
            return response()->json([
                'message' => 'No tienes permisos para desactivar esta cuenta.'
            ], 403);
        }

        // Desactivar la cuenta sin eliminar sus registros.
        $user->is_active = false;
        $user->save();

        // Invalidar los tokens existentes de esa cuenta.
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Cuenta desactivada correctamente.',
            'user' => $user
        ], 200);
    }

}