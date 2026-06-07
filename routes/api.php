<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\UserAddressController;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;


use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProductStatsController;
use App\Http\Controllers\RevenueStatsController;


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
// Biến thể: lấy danh sách variant (public - không cần đăng nhập)
Route::get('/products/{productId}/variants', [ProductVariantController::class, 'index']);

// Danh mục sản phẩm (public)
Route::get('/categories', [CategoryController::class, 'index']);


use App\Http\Controllers\CartController;

// Public routes (không cần token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



// Protected routes (cần token)
Route::middleware('auth:api')->group(function () {
    // Cart Routes (Public, uses auth or session_id)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/update/{itemId}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{itemId}', [CartController::class, 'remove']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);

    // Quản lý địa chỉ
    Route::get('user/addresses', [UserAddressController::class, 'index']);
    Route::post('user/addresses', [UserAddressController::class, 'store']);
    Route::put('user/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('user/addresses/{id}', [UserAddressController::class, 'destroy']);
    Route::patch('user/addresses/{id}/set-default', [UserAddressController::class, 'setDefault']);

    //Quản lí wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/add', [WishlistController::class, 'store']);
    Route::delete('/wishlist/remove/{itemId}', [WishlistController::class, 'remove']);
    Route::delete('/wishlist/clear', [WishlistController::class, 'clear']);

    // Áp dụng mã giảm giá
    Route::post('/coupons/apply', [CouponController::class, 'apply']);

    // Đặt hàng
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

// Public route cho Admin
Route::post('/admin/login', [AdminController::class, 'login']);

// Route cho Quản trị viên (Admin)
Route::middleware('auth:admin-api')->group(function () {
    Route::get('/admin/me', [AdminController::class, 'me']);
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::get('/admin/dashboard', [AdminController::class, 'index']);

    // --- Nhóm quyền: Chỉ Superadmin ---
    Route::middleware('role.admin:superadmin')->group(function () {


        // Quản lý Staff (Nhân viên/Admins)
        Route::get('/admin/staff', [AdminController::class, 'index']);
        Route::post('/admin/staff', [AdminController::class, 'store']);
        Route::get('/admin/staff/{id}', [AdminController::class, 'show']);
        Route::put('/admin/staff/{id}', [AdminController::class, 'update']);
        Route::delete('/admin/staff/{id}', [AdminController::class, 'destroy']);
    });

    // --- Nhóm quyền: Superadmin & Admin ---
    Route::middleware('role.admin:superadmin,admin')->group(function () {
        // Quản lý Sản phẩm
        Route::post('/products/create', [ProductController::class, 'store']);
        Route::match(['POST', 'PUT'], '/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::patch('/products/{productId}/set-primary-image/{imageId}', [ProductController::class, 'setPrimaryImage']);

        // Product Variants
        Route::post('/products/{productId}/variants', [ProductVariantController::class, 'store']);
        Route::match(['POST', 'PUT'], '/products/{productId}/variants/{variantId}', [ProductVariantController::class, 'update']);
        Route::delete('/products/{productId}/variants/{variantId}', [ProductVariantController::class, 'destroy']);

        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::match(['POST', 'PUT'], '/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);


        // Quản lý Mã giảm giá (Coupons)
        Route::get('/admin/coupons', [CouponController::class, 'index']);
        // Route::post('/admin/coupons', [CouponController::class, 'store']); // Sẽ được merge vào nhóm admin bên dưới
        Route::post('/admin/coupons', [CouponController::class, 'store']);
        Route::get('/admin/coupons/{coupon}', [CouponController::class, 'show']);
        Route::put('/admin/coupons/{coupon}', [CouponController::class, 'update']);
        Route::delete('/admin/coupons/{coupon}', [CouponController::class, 'destroy']);

        // Thống kê sản phẩm (Product Stats)
        Route::get('/admin/products/stats/by-price', [ProductStatsController::class, 'byPrice']);
        Route::get('/admin/products/stats/by-brand', [ProductStatsController::class, 'byBrand']);

        // Thống kê doanh thu (Revenue Stats)
        Route::get('/admin/revenue/stats', [RevenueStatsController::class, 'stats']);

    });

    // --- Nhóm quyền: Nhân viên (Staff) ---
    Route::middleware('role.admin:superadmin,admin,staff')->group(function () {
        // Quản lý đơn hàng cho Admin/Staff
        Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
        Route::get('/admin/orders/{id}', [OrderController::class, 'adminShow']);
        Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
    });

    // Các route khác nếu có...
});

// Quản lý User (Khách hàng)
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);



// Public routes quên mật khẩu
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// Link này trỏ từ Frontend để bắt đầu redirect sang Google
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
// Link này là callback từ Google trả về
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::post('/reset-password', [AuthController::class, 'resetPassword']);




