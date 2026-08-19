<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;



class AuthController extends Controller
{
    /**
     * Xử lý Đăng ký tài khoản khách hàng.
     * Đầu vào: Tên, Email, Mật khẩu.
     * Hoạt động: Lưu user mới vào DB, mã hóa mật khẩu, tự động đăng nhập và trả về Token JWT.
     */
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công',
            'token' => $token,
            'user' => $user
        ], 201);
    }


    /**
     * Xử lý Đăng nhập hệ thống.
     * Đầu vào: Email, Mật khẩu.
     * Hoạt động: Kiểm tra tài khoản, nếu đúng cấp phát JWT Token. Nếu tài khoản bị khóa (is_active = false) thì từ chối.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        $user = Auth::user();
        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => $user
        ]);
    }
    // LOGOUT
    public function logout()
    {
        try {
            Auth::logout(); // invalidate token
            return response()->json(['success' => true, 'message' => 'Đăng xuất thành công']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    }



    // LẤY THÔNG TIN USER ĐANG ĐĂNG NHẬP
    public function me()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token đã hết hạn'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        // Tạo token ngẫu nhiên
        $token = Str::random(64);
        // Lưu vào DB (xóa cũ nếu có)
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => hash('sha256', $token), // lưu đã hash
            'created_at' => now(),
        ]);
        // Link gửi cho user (trỏ về frontend)
        $resetLink = env('FRONTEND_URL_RESSETPASS', 'http://localhost:5173/reset-password')
            . '?token=' . $token
            . '&email=' . urlencode($request->email);
        // Gửi mail
        try {
            Mail::to($request->email)->send(new ResetPasswordMail($resetLink));
        } catch (\Exception $e) {
            \Log::error('Mail Reset Password Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi email: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link đặt lại mật khẩu đã được gửi vào email của bạn.',
        ]);
    }
    // ========================
    // ĐẶT LẠI MẬT KHẨU MỚI
    // ========================
    public function resetPassword(ResetPasswordRequest $request)
    {
        // Kiểm tra token có hợp lệ không
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', hash('sha256', $request->token))
            ->first();
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn.'
            ], 400);
        }
        // Kiểm tra hết hạn 60 phút
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token đã hết hạn, vui lòng yêu cầu lại.'
            ], 400);
        }
        // Cập nhật mật khẩu mới
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);
        // Xóa token sau khi dùng
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công.'
        ]);
    }
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Cập nhật thông tin (chỉ lấy các trường được gửi lên)
        $data = $request->validated();

        // Hiện tại dùng JWT, Auth::user() trả về model User của chúng ta
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user
        ]);
    }

    public function redirectToGoogle()
    {
        $redirectUrl = config('services.google.redirect');
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        $driver->stateless()->redirectUrl($redirectUrl);

        // Bỏ qua kiểm tra SSL trên môi trường local (Windows)
        if (config('app.env') === 'local') {
            $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }

        $url = $driver->redirect()->getTargetUrl();

        return response()
            ->view('auth.redirect', ['url' => $url])
            ->header('Content-Type', 'text/html');
    }

    public function handleGoogleCallback()
    {
        try {
            $redirectUrl = config('services.google.redirect');
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $driver->stateless()->redirectUrl($redirectUrl);

            // Bỏ qua kiểm tra SSL trên môi trường local (Windows)
            if (config('app.env') === 'local') {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }

            $googleUser = $driver->user();

            // Tìm user theo email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Tạo mới nếu chưa có
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'is_active' => true,
                ]);
            }

            // Kiểm tra xem tài khoản có bị khóa (is_active = false) không
            if (isset($user->is_active) && !$user->is_active) {
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
                return response()
                    ->view('auth.callback', [
                        'error' => 'locked',
                        'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                        'frontendUrl' => $frontendUrl
                    ])
                    ->header('Content-Type', 'text/html');
            }

            // Đăng nhập và tạo token
            /** @var string $token */
            $token = Auth::login($user);

            // Redirect về Frontend kèm Token
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            // Thay vì redirect toàn trang, trả về view để gửi message tới trang mẹ
            return response()
                ->view('auth.callback', [
                    'token' => $token,
                    'user' => $user,
                    'frontendUrl' => $frontendUrl
                ])
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            \Log::error('Google Login Error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return response()
                ->view('auth.callback', [
                    'error' => 'google_failed',
                    'frontendUrl' => $frontendUrl
                ])
                ->header('Content-Type', 'text/html');
        }

    }




    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu cũ không đúng.'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công.'
        ]);
    }

}
