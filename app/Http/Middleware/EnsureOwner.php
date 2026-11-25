<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->level !== 'owner') {
            return response()->json([
                'error' => 'Akses ditolak. Hanya owner yang memiliki izin ini.',
            ], 403);
        }

        return $next($request);
    }
}
