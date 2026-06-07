<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Services\DiscountService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $discountService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = new DiscountService();
    }

    /** @test */
    public function it_can_validate_a_valid_coupon()
    {
        $coupon = Coupon::create([
            'code' => 'TEST10',
            'type' => 'fixed',
            'value' => 10000,
            'min_order_amount' => 50000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $validatedCoupon = $this->discountService->validateCoupon('TEST10', 60000);

        $this->assertEquals($coupon->id, $validatedCoupon->id);
    }

    /** @test */
    public function it_throws_exception_if_coupon_not_found()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mã giảm giá không tồn tại.');

        $this->discountService->validateCoupon('INVALID', 100000);
    }

    /** @test */
    public function it_throws_exception_if_coupon_expired()
    {
        Coupon::create([
            'code' => 'EXPIRED',
            'type' => 'fixed',
            'value' => 10000,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(1),
            'is_active' => true,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mã giảm giá này đã hết hạn.');

        $this->discountService->validateCoupon('EXPIRED', 100000);
    }

    /** @test */
    public function it_calculates_percent_discount_correctly()
    {
        $coupon = new Coupon([
            'type' => 'percent',
            'value' => 10,
            'max_discount' => 50000,
        ]);

        $discount = $this->discountService->calculateDiscount($coupon, 200000);
        $this->assertEquals(20000, $discount);

        $discountLimit = $this->discountService->calculateDiscount($coupon, 1000000);
        $this->assertEquals(50000, $discountLimit);
    }

    /** @test */
    public function it_calculates_fixed_discount_correctly()
    {
        $coupon = new Coupon([
            'type' => 'fixed',
            'value' => 30000,
        ]);

        $discount = $this->discountService->calculateDiscount($coupon, 100000);
        $this->assertEquals(30000, $discount);

        $discountLimit = $this->discountService->calculateDiscount($coupon, 20000);
        $this->assertEquals(20000, $discountLimit);
    }
}
