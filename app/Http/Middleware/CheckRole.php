<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!in_array(auth()->user()->role, $roles)) {
            return response()->json([
                'status'         => false,
                'message'        => 'Akses ditolak. Role Anda tidak diizinkan.',
                'required_roles' => $roles,
                'your_role'      => auth()->user()->role,
            ], 403);
        }

        return $next($request);
    }
}
