<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'parent_id' => null,
                'description' => 'Điện thoại iPhone chính hãng Apple với thiết kế sang trọng và hiệu năng đỉnh cao.',
                'is_active' => true,
                'sort_order' => 1,
                'updated_at' => now(),
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'parent_id' => null,
                'description' => 'Điện thoại thông minh Samsung Galaxy với công nghệ màn hình đỉnh cao và camera sắc nét.',
                'is_active' => true,
                'sort_order' => 2,
                'updated_at' => now(),
            ],
            [
                'name' => 'Xiaomi',
                'slug' => 'xiaomi',
                'parent_id' => null,
                'description' => 'Điện thoại Xiaomi với cấu hình mạnh mẽ, dung lượng pin lớn và giá cả cực kỳ hợp lý.',
                'is_active' => true,
                'sort_order' => 3,
                'updated_at' => now(),
            ],
            [
                'name' => 'OPPO',
                'slug' => 'oppo',
                'parent_id' => null,
                'description' => 'Điện thoại OPPO sở hữu thiết kế thời trang cùng khả năng chụp ảnh chuyên nghiệp.',
                'is_active' => true,
                'sort_order' => 4,
                'updated_at' => now(),
            ],
            [
                'name' => 'Realme',
                'slug' => 'realme',
                'parent_id' => null,
                'description' => 'Thương hiệu điện thoại trẻ trung, hiệu năng vượt trội và sạc siêu nhanh.',
                'is_active' => true,
                'sort_order' => 5,
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                array_merge($cat, ['created_at' => now()])
            );
        }
    }
}