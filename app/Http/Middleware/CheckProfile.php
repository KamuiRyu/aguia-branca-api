<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$profiles
     */
    public function handle(Request $request, Closure $next, ...$profiles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->profile, $profiles)) {
            return response()->json([
                'mensagem' => 'Acesso não autorizado para o seu perfil.',
            ], 403);
        }

        return $next($request);
    }
}
