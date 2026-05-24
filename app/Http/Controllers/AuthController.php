<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Handle user login and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'mensagem' => 'Credenciais inválidas',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensagem' => 'Login realizado com sucesso',
            'token' => $token,
            'usuario' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'perfil' => $user->profile,
            ],
        ]);
    }

    /**
     * Validate the given Sanctum token stateless.
     */
    public function validateToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = PersonalAccessToken::findToken($request->input('token'));

        if (! $token) {
            return response()->json([
                'valido' => false,
            ], 401);
        }

        $user = $token->tokenable;

        if (! $user) {
            return response()->json([
                'valido' => false,
            ], 401);
        }

        return response()->json([
            'valido' => true,
            'usuario' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'perfil' => $user->profile,
            ],
        ]);
    }
}
