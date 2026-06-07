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



/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model schema",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Nguyen Van A"),
 *     @OA\Property(property="email", type="string", example="user@gmail.com"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="0123456789"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, nullable=true, example="male"),
 *     @OA\Property(property="birthday", type="string", format="date", nullable=true, example="1990-01-01"),
 *     @OA\Property(property="avatar", type="string", nullable=true, example="avatar.png"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Đăng ký tài khoản",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Nguyen Van A"),
     *             @OA\Property(property="email", type="string", example="user@gmail.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="password_confirmation", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Đăng ký thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đăng ký thành công"),
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUz..."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Lỗi validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Dữ liệu gửi lên không hợp lệ."),
     *             @OA\Property(property="errors", type="object", example={"email": {"Email đã được sử dụng."}})
     *         )
     *     )
     * )
     */
    // SIGNUP
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công',
            'token' => $token,
            'user' => $user
        ], 201);
    }


    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Đăng nhập hệ thống",
     *     description="API cho phép người dùng đăng nhập bằng email và mật khẩu. Nếu thông tin hợp lệ, hệ thống sẽ trả về JWT token.",
     *     operationId="loginUser",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Thông tin đăng nhập",
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="user@gmail.com",
     *                 description="Email của người dùng"
     *             ),
     *
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="123456",
     *                 description="Mật khẩu đăng nhập"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công",
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Đăng nhập thành công"
     *             ),
     *
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     *             ),
     *
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nguyen Van A"),
     *                 @OA\Property(property="email", type="string", example="user@gmail.com"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Sai email hoặc mật khẩu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email hoặc mật khẩu không đúng")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Tài khoản bị khóa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Dữ liệu gửi lên không hợp lệ."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "email": {"Email không hợp lệ."},
     *                     "password": {"Mật khẩu không hợp lệ."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    // LOGIN
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
    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Đăng xuất",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true
     *     ),
     *     @OA\Response(response=200, description="Đăng xuất thành công"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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



    /**
     * @OA\Get(
     *     path="/me",
     *     summary="Lấy thông tin người dùng đang đăng nhập",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     summary="Quên mật khẩu - gửi link đặt lại qua email",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="user@gmail.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gửi email thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Link đặt lại mật khẩu đã được gửi vào email của bạn.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation lỗi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="Email không tồn tại trong hệ thống.")
     *         )
     *     )
     * )
     */
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
        $resetLink = env('FRONTEND_URL_RESSETPASS', 'https://lt-createwebfunitureluxury.onrender.com/reset-password')
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
    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="Đặt lại mật khẩu mới",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","token","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", example="user@gmail.com"),
     *             @OA\Property(property="token", type="string", example="abc123xyz..."),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đặt lại mật khẩu thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đặt lại mật khẩu thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token không hợp lệ hoặc đã hết hạn",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token không hợp lệ hoặc đã hết hạn.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation lỗi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Put(
     *     path="/update-profile",
     *     summary="Cập nhật thông tin cá nhân",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Nguyen Van B"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male"),
     *             @OA\Property(property="birthday", type="string", format="date", example="1990-01-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thành công"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation lỗi")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/auth/google",
     *     summary="Chuyển hướng sang trang đăng nhập Google",
     *     description="GỌI TRÊN TRÌNH DUYỆT: Endpoint này không trả về JSON. Nó sẽ redirect người dùng sang trang OAuth của Google. Sau khi người dùng đăng nhập, Google sẽ gọi lại endpoint /callback kèm theo mã 'code'.",
     *     tags={"Auth"},
     *     @OA\Response(response=302, description="Redirect to Google OAuth")
     * )
     */
    public function redirectToGoogle()
    {
        $redirectUrl = config('services.google.redirect');
        $driver = Socialite::driver('google')->stateless()->redirectUrl($redirectUrl);

        // Bỏ qua kiểm tra SSL trên môi trường local (Windows)
        if (config('app.env') === 'local') {
            $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }

        $url = $driver->redirect()->getTargetUrl();

        return response()
            ->view('auth.redirect', ['url' => $url])
            ->header('Content-Type', 'text/html');
    }

    /**
     * @OA\Get(
     *     path="/auth/google/callback",
     *     summary="Xử lý callback từ Google (Hệ thống tự động gọi)",
     *     description="HÀNH VI TỰ ĐỘNG: Endpoint này được thiết kế để Google gọi lại sau khi người dùng xác thực thành công. Mã 'code' được Google cấp tự động và chỉ có hiệu lực một lần trong vài giây. Không nên gọi endpoint này một cách thủ công từ Swagger/Postman.",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Mã xác thực một lần (One-time code) do Google cấp",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=302, description="Redirect về Frontend kèm token hoặc lỗi")
     * )
     */

    public function handleGoogleCallback()
    {
        try {
            $redirectUrl = config('services.google.redirect');
            $driver = Socialite::driver('google')->stateless()->redirectUrl($redirectUrl);

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
                ]);
            }

            // Đăng nhập và tạo token
            /** @var string $token */
            $token = Auth::login($user);

            // Redirect về Frontend kèm Token
            $frontendUrl = env('FRONTEND_URL', 'https://tttn-2.onrender.com');
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
            $frontendUrl = env('FRONTEND_URL', 'https://tttn-2.onrender.com');
            return response()
                ->view('auth.callback', [
                    'error' => 'google_failed',
                    'frontendUrl' => $frontendUrl
                ])
                ->header('Content-Type', 'text/html');
        }

    }




}
