<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Student;
use Closure;
use Illuminate\Http\Request;

class EnsureAccountRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        $allowed = $role === 'admin' ? $user instanceof Admin : $user instanceof Student;

        if (!$user || !$allowed) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak valid atau akses ditolak.'], 401);
        }

        return $next($request);
    }
}