<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $admin = Auth::guard('admin-api')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập bằng tài khoản admin.'
            ], 401);
        }

        if (!in_array($admin->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này.'
            ], 403);
        }

        return $next($request);
    }
}
