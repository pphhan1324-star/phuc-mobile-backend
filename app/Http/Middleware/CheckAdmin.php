<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;    
use Symfony\Component\HttpFoundation\Response;  
use Illuminate\Support\Facades\Auth;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // 1. Kiểm tra xem có đang login bằng guard 'admin-api' không
        if (!Auth::guard('admin-api')->check()) {
            return response()->json(['message' => 'Bạn không có quyền truy cập'], 403);
        }

        // 2. Kiểm tra sâu hơn về role
        $admin = Auth::guard('admin-api')->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Cấp bậc của bạn không đủ'], 403);
        }

        return $next($request);
    }

}
