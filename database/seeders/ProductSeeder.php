<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy ID category
        $categories = [
            'Apple'   => DB::table('categories')->where('slug', 'apple')->value('id'),
            'Samsung' => DB::table('categories')->where('slug', 'samsung')->value('id'),
            'Xiaomi'  => DB::table('categories')->where('slug', 'xiaomi')->value('id'),
            'OPPO'    => DB::table('categories')->where('slug', 'oppo')->value('id'),
            'Realme'  => DB::table('categories')->where('slug', 'realme')->value('id'),
        ];

        // 1. Seed Brands
        $brandsData = [
            'Apple'   => ['name' => 'Apple', 'logo' => 'https://res.cloudinary.com/demo/image/upload/v1/brands/apple.png', 'description' => 'Thương hiệu công nghệ hàng đầu thế giới từ Mỹ.'],
            'Samsung' => ['name' => 'Samsung', 'logo' => 'https://res.cloudinary.com/demo/image/upload/v1/brands/samsung.png', 'description' => 'Tập đoàn công nghệ đa quốc gia từ Hàn Quốc.'],
            'Xiaomi'  => ['name' => 'Xiaomi', 'logo' => 'https://res.cloudinary.com/demo/image/upload/v1/brands/xiaomi.png', 'description' => 'Thương hiệu điện tử thông minh giá tốt từ Trung Quốc.'],
            'OPPO'    => ['name' => 'OPPO', 'logo' => 'https://res.cloudinary.com/demo/image/upload/v1/brands/oppo.png', 'description' => 'Thương hiệu chuyên điện thoại selfie từ Trung Quốc.'],
            'Realme'  => ['name' => 'Realme', 'logo' => 'https://res.cloudinary.com/demo/image/upload/v1/brands/realme.png', 'description' => 'Thương hiệu điện thoại trẻ trung, hiệu năng cao.'],
        ];

        $brandIds = [];
        foreach ($brandsData as $name => $bData) {
            $brandIds[$name] = DB::table('brands')->insertGetId([
                'name' => $bData['name'],
                'slug' => Str::slug($bData['name']),
                'logo' => $bData['logo'],
                'description' => $bData['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Seed Colors
        $colorData = [
            'Titanium Tự Nhiên' => '#bebbb4',
            'Đen Huyền Bí'      => '#0c0c0c',
            'Xanh Đại Dương'    => '#2e4a62',
            'Trắng Tinh Khiết'  => '#fbfbfb',
            'Hồng Thanh Lịch'   => '#fcd7d9',
        ];
        $colorIds = [];
        foreach ($colorData as $name => $hex) {
            $colorIds[$name] = DB::table('colors')->insertGetId([
                'name' => $name,
                'hex_code' => $hex,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Seed RAMs
        $ramValues = ['8GB', '12GB', '16GB'];
        $ramIds = [];
        foreach ($ramValues as $val) {
            $ramIds[$val] = DB::table('rams')->insertGetId([
                'value' => $val,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Seed Storages (ROM)
        $storageValues = ['128GB', '256GB', '512GB', '1TB'];
        $storageIds = [];
        foreach ($storageValues as $val) {
            $storageIds[$val] = DB::table('storages')->insertGetId([
                'value' => $val,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $phonesData = [
            'Apple' => [
                [
                    'name' => 'iPhone 15 Pro Max',
                    'specs' => [
                        'screen_size' => '6.7 inches',
                        'screen_tech' => 'Super Retina XDR OLED, 120Hz',
                        'rear_camera' => '48MP + 12MP + 12MP',
                        'front_camera' => '12MP',
                        'chipset' => 'Apple A17 Pro (3nm)',
                        'battery' => '4441 mAh',
                        'charging_speed' => '25W',
                        'operating_system' => 'iOS 17',
                        'weight_g' => 221,
                    ],
                    'base_price' => 34990000,
                ],
                [
                    'name' => 'iPhone 15 Pro',
                    'specs' => [
                        'screen_size' => '6.1 inches',
                        'screen_tech' => 'Super Retina XDR OLED, 120Hz',
                        'rear_camera' => '48MP + 12MP + 12MP',
                        'front_camera' => '12MP',
                        'chipset' => 'Apple A17 Pro (3nm)',
                        'battery' => '3274 mAh',
                        'charging_speed' => '25W',
                        'operating_system' => 'iOS 17',
                        'weight_g' => 187,
                    ],
                    'base_price' => 28990000,
                ],
                [
                    'name' => 'iPhone 15 Plus',
                    'specs' => [
                        'screen_size' => '6.7 inches',
                        'screen_tech' => 'Super Retina XDR OLED',
                        'rear_camera' => '48MP + 12MP',
                        'front_camera' => '12MP',
                        'chipset' => 'Apple A16 Bionic',
                        'battery' => '4383 mAh',
                        'charging_speed' => '20W',
                        'operating_system' => 'iOS 17',
                        'weight_g' => 201,
                    ],
                    'base_price' => 25990000,
                ],
                [
                    'name' => 'iPhone 15',
                    'specs' => [
                        'screen_size' => '6.1 inches',
                        'screen_tech' => 'Super Retina XDR OLED',
                        'rear_camera' => '48MP + 12MP',
                        'front_camera' => '12MP',
                        'chipset' => 'Apple A16 Bionic',
                        'battery' => '3349 mAh',
                        'charging_speed' => '20W',
                        'operating_system' => 'iOS 17',
                        'weight_g' => 171,
                    ],
                    'base_price' => 21990000,
                ],
            ],
            'Samsung' => [
                [
                    'name' => 'Galaxy S24 Ultra',
                    'specs' => [
                        'screen_size' => '6.8 inches',
                        'screen_tech' => 'Dynamic AMOLED 2X, 120Hz',
                        'rear_camera' => '200MP + 50MP + 12MP + 10MP',
                        'front_camera' => '12MP',
                        'chipset' => 'Snapdragon 8 Gen 3 for Galaxy',
                        'battery' => '5000 mAh',
                        'charging_speed' => '45W',
                        'operating_system' => 'Android 14 (One UI 6.1)',
                        'weight_g' => 232,
                    ],
                    'base_price' => 31990000,
                ],
                [
                    'name' => 'Galaxy S24 Plus',
                    'specs' => [
                        'screen_size' => '6.7 inches',
                        'screen_tech' => 'Dynamic AMOLED 2X, 120Hz',
                        'rear_camera' => '50MP + 10MP + 12MP',
                        'front_camera' => '12MP',
                        'chipset' => 'Exynos 2400',
                        'battery' => '4900 mAh',
                        'charging_speed' => '45W',
                        'operating_system' => 'Android 14 (One UI 6.1)',
                        'weight_g' => 196,
                    ],
                    'base_price' => 25990000,
                ],
                [
                    'name' => 'Galaxy Z Fold5',
                    'specs' => [
                        'screen_size' => '7.6 inches',
                        'screen_tech' => 'Foldable Dynamic AMOLED 2X, 120Hz',
                        'rear_camera' => '50MP + 10MP + 12MP',
                        'front_camera' => '4MP + 10MP',
                        'chipset' => 'Snapdragon 8 Gen 2 for Galaxy',
                        'battery' => '4400 mAh',
                        'charging_speed' => '25W',
                        'operating_system' => 'Android 13',
                        'weight_g' => 253,
                    ],
                    'base_price' => 40990000,
                ],
            ],
            'Xiaomi' => [
                [
                    'name' => 'Xiaomi 14 Ultra',
                    'specs' => [
                        'screen_size' => '6.73 inches',
                        'screen_tech' => 'LTPO AMOLED, 68B colors, 120Hz',
                        'rear_camera' => '50MP + 50MP + 50MP + 50MP Leica',
                        'front_camera' => '32MP',
                        'chipset' => 'Snapdragon 8 Gen 3',
                        'battery' => '5000 mAh',
                        'charging_speed' => '90W',
                        'operating_system' => 'Android 14 (HyperOS)',
                        'weight_g' => 220,
                    ],
                    'base_price' => 29990000,
                ],
                [
                    'name' => 'Xiaomi 14',
                    'specs' => [
                        'screen_size' => '6.36 inches',
                        'screen_tech' => 'LTPO OLED, 120Hz',
                        'rear_camera' => '50MP + 50MP + 50MP Leica',
                        'front_camera' => '32MP',
                        'chipset' => 'Snapdragon 8 Gen 3',
                        'battery' => '4610 mAh',
                        'charging_speed' => '90W',
                        'operating_system' => 'Android 14 (HyperOS)',
                        'weight_g' => 193,
                    ],
                    'base_price' => 22990000,
                ],
            ],
            'OPPO' => [
                [
                    'name' => 'OPPO Find X7 Ultra',
                    'specs' => [
                        'screen_size' => '6.82 inches',
                        'screen_tech' => 'LTPO AMOLED, 120Hz',
                        'rear_camera' => '50MP + 50MP + 50MP + 50MP Hasselblad',
                        'front_camera' => '32MP',
                        'chipset' => 'Snapdragon 8 Gen 3',
                        'battery' => '5000 mAh',
                        'charging_speed' => '100W',
                        'operating_system' => 'Android 14 (ColorOS 14)',
                        'weight_g' => 221,
                    ],
                    'base_price' => 26990000,
                ],
            ],
            'Realme' => [
                [
                    'name' => 'Realme GT5 Pro',
                    'specs' => [
                        'screen_size' => '6.78 inches',
                        'screen_tech' => 'AMOLED, 144Hz',
                        'rear_camera' => '50MP + 50MP + 8MP',
                        'front_camera' => '32MP',
                        'chipset' => 'Snapdragon 8 Gen 3',
                        'battery' => '5400 mAh',
                        'charging_speed' => '100W',
                        'operating_system' => 'Android 14 (Realme UI 5.0)',
                        'weight_g' => 218,
                    ],
                    'base_price' => 18990000,
                ],
            ],
        ];

        $colors = ['Titanium Tự Nhiên', 'Đen Huyền Bí', 'Xanh Đại Dương', 'Trắng Tinh Khiết', 'Hồng Thanh Lịch'];
        $variantsConfig = [
            ['size' => '128GB', 'ram' => '8GB', 'price_offset' => 0],
            ['size' => '256GB', 'ram' => '8GB', 'price_offset' => 3000000],
            ['size' => '512GB', 'ram' => '12GB', 'price_offset' => 7000000],
            ['size' => '1TB', 'ram' => '16GB', 'price_offset' => 12000000],
        ];

        $count = 1;
        foreach ($phonesData as $brand => $phones) {
            $catId = $categories[$brand];

            foreach ($phones as $phone) {
                $basePrice = $phone['base_price'];
                $salePrice = rand(0, 4) == 0 ? $basePrice - 1500000 : null; // 20% cơ hội giảm giá 1.5 triệu

                // 5. Insert Product
                $productId = DB::table('products')->insertGetId([
                    'category_id' => $catId,
                    'brand_id'    => $brandIds[$brand],
                    'name'        => $phone['name'],
                    'slug'        => Str::slug($phone['name']) . '-' . Str::random(5),
                    'sku'         => strtoupper(substr($brand, 0, 3)) . '-SKU-' . str_pad($count, 3, '0', STR_PAD_LEFT),
                    'description' => "Đánh giá chi tiết điện thoại {$phone['name']}. Thiết kế sang trọng đẳng cấp, hiệu năng ấn tượng với chipset {$phone['specs']['chipset']} hàng đầu thế giới, trang bị cụm camera đỉnh cao {$phone['specs']['rear_camera']} thách thức mọi góc chụp.",
                    'base_price'  => $basePrice,
                    'sale_price'  => $salePrice,
                    'is_featured' => rand(0, 1),
                    'is_active'   => 1,
                    'view_count'  => rand(50, 2000),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // 6. Insert specifications to phone_specifications table
                DB::table('phone_specifications')->insert(array_merge([
                    'product_id' => $productId,
                    'material'   => 'Hợp kim titan và kính cường lực',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $phone['specs']));

                // 7. Create variants (Color, RAM, Storage Options)
                $selectedVariants = array_slice($variantsConfig, 0, rand(2, 4)); // Mỗi máy có từ 2 đến 4 phiên bản bộ nhớ
                $varIndex = 1;

                foreach ($selectedVariants as $var) {
                    $selectedColors = array_slice($colors, 0, rand(2, 3)); // Mỗi cấu hình có 2 đến 3 lựa chọn màu sắc

                    foreach ($selectedColors as $color) {
                        $finalPrice = ($salePrice ?? $basePrice) + $var['price_offset'];
                        
                        DB::table('product_variants')->insert([
                            'product_id'     => $productId,
                            'sku'            => strtoupper(substr($brand, 0, 3)) . "-VAR-{$count}-" . $varIndex++,
                            'color_id'       => $colorIds[$color],
                            'ram_id'         => $ramIds[$var['ram']],
                            'storage_id'     => $storageIds[$var['size']],
                            'price'          => $finalPrice,
                            'stock_quantity' => rand(10, 100),
                            'is_available'   => true,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }
                }

                $count++;
            }
        }

        $this->command->info("Đã tạo xong dữ liệu điện thoại mẫu chuẩn 3NF cho {$count} sản phẩm!");
    }
}