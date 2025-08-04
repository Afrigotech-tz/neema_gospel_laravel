<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'children' => [
                    ['name' => 'Mobile Phones', 'description' => 'Smartphones and accessories'],
                    ['name' => 'Laptops', 'description' => 'Portable computers'],
                ],
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Home appliances and kitchenware',
                'children' => [
                    ['name' => 'Furniture', 'description' => 'Indoor and outdoor furniture'],
                    ['name' => 'Cookware', 'description' => 'Pots, pans, utensils'],
                ],
            ],
            [
                'name' => 'Fashion',
                'description' => 'Apparel and accessories',
                'children' => [
                    ['name' => 'Men', 'description' => 'Men clothing and accessories'],
                    ['name' => 'Women', 'description' => 'Women clothing and accessories'],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $parent = ProductCategory::updateOrCreate(
                ['name' => $catData['name']],
                [
                    'description' => $catData['description'],
                    'is_active' => true,

                ]
            );

            if (!empty($catData['children'])) {
                foreach ($catData['children'] as $childData) {
                    ProductCategory::updateOrCreate(
                        ['name' => $childData['name'], 'parent_id' => $parent->id],
                        [
                            'description' => $childData['description'],
                            'parent_id' => $parent->id,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
