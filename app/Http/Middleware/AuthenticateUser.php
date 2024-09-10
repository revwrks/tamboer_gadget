<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\MUsers;

class AuthenticateUser
{
    public function handle($request, Closure $next)
    {
        $id_user = $request->header('Authorization'); // Expecting token as id_user

        if (!$id_user || !MUsers::find($id_user)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
