<?php

namespace App\Http\Controllers;


/**
 * @OA\Info(
 *     title="API HỆ THỐNG CỬA HÀNG ĐIỆN THOẠI PHÚC MOBILE",
 *     version="1.0.0",
 *     description="Hệ thống API quản lý cửa hàng kinh doanh điện thoại di động và phụ kiện công nghệ PHÚC MOBILE. Hỗ trợ đầy đủ phân quyền người dùng (RBAC) và chuẩn hóa dữ liệu quan hệ 3NF.
 *     
 *     ### Danh sách Vai trò (Roles):
 *     - **superadmin**: Quyền cao nhất, quản lý toàn bộ hệ thống bao gồm cả nhân viên và khách hàng.
 *     - **admin**: Quản lý nghiệp vụ chính (Điện thoại, Hãng sản xuất, Danh mục, Mã giảm giá, Đơn hàng, Đánh giá).
 *     - **staff**: Nhân viên vận hành cửa hàng, xử lý trạng thái đơn hàng và phê duyệt đánh giá sản phẩm.
 *     - **user**: Khách hàng (Xem sản phẩm, thêm giỏ hàng, đặt mua điện thoại và gửi đánh giá)."
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API Server (Tự động nhận diện Host)"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Nhập JWT token vào đây để thực hiện các yêu cầu có bảo mật."
 * )
 */
abstract class Controller
{
    //
}
