<?php

namespace Database\Seeders\WebSite;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MenuMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menus = [
            [
                'name' => 'Menus',
                'handle' => 'menus',
                'slug' => '/menu-list',
                'parent_id' => 31,
            ],
            [
                'name' => 'Add Menu',
                'handle' => 'add-menu',
                'slug' => '/menu-add',
                'parent_id' => 31,
            ],
            [
                'name' => 'Roles',
                'handle' => 'roles',
                'slug' => '/role-list',
                'parent_id' => 31,
            ],
            [
                'name' => 'Add Role',
                'handle' => 'add-role',
                'slug' => '/role-add',
                'is_parent' => 0,
                'parent_id' => 31,

            ],
            [
                'name' => 'Users',
                'handle' => 'users',
                'slug' => '/user-list',
                'is_parent' => 0,
                'parent_id' => 31,

            ],
            [
                'name' => 'Add User',
                'handle' => 'add-user',
                'slug' => '/user/add',
                'is_parent' => 0,
                'parent_id' => 31,

            ],
            [
                'name' => 'Edit User',
                'handle' => 'edit-user',
                'slug' => '/user/edit',
                'is_parent' => 0,
                'parent_id' => 31,

            ],
            [
                'name' => 'Update Profile',
                'handle' => 'update-profile',
                'slug' => '/update-profile',
                'is_parent' => 0,
                'parent_id' => 31,

            ],
            [
                'name' => 'Update Password',
                'handle' => 'update-password',
                'slug' => '/update-password',
                'is_parent' => 0,
                'parent_id' => 31,

            ],
            [
                'name' => 'Api Logs',
                'handle' => 'api-logs',
                'slug' => '/api-logs',
                'is_parent' => 0,
                'parent_id' => 34,

            ],
            [
                'name' => 'Lead Details',
                'handle' => 'lead-details',
                'slug' => '/lead-details',
                'is_parent' => 0,
                'parent_id' => 33,

            ],
            [
                'name' => 'Impressions',
                'handle' => 'impressions',
                'slug' => '/impressions',
                'is_parent' => 0,
                'parent_id' => 33,

            ],
            [
                'name' => 'Applications',
                'handle' => 'applications',
                'slug' => '/applications',
                'is_parent' => 0,
                'parent_id' => 33,

            ],
            [
                'name' => 'Master Product',
                'handle' => 'master-product',
                'slug' => '/masterproduct',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Product Add',
                'handle' => 'master-product-add',
                'slug' => '/master-product-add',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Project',
                'handle' => 'master-project',
                'slug' => '/masterproject',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Project Add',
                'handle' => 'master-project-add',
                'slug' => '/master-project-add',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Company',
                'handle' => 'master-company',
                'slug' => '/mastercompany',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Company Add',
                'handle' => 'master-company-add',
                'slug' => '/master-company-add',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Industry Type',
                'handle' => 'master-industry-type',
                'slug' => '/masterindustry',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Industry Add',
                'handle' => 'master-industry-add',
                'slug' => '/master-industry-add',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Pincode',
                'handle' => 'master-pincode',
                'slug' => '/masterpincode',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Master Pincode Add',
                'handle' => 'master-pincode-add',
                'slug' => '/master-pincode-add',
                'is_parent' => 0,
                'parent_id' => 32,

            ],
            [
                'name' => 'Auction Bid',
                'handle' => 'auction-bid',
                'slug' => '/auctionbid',
                'is_parent' => 0,
                'parent_id' => 35,

            ],
            [
                'name' => 'Pan Logs',
                'handle' => 'pan-logs',
                'slug' => '/pan-logs',
                'is_parent' => 0,
                'parent_id' => 34,

            ],
            [
                'name' => 'Payment Logs',
                'handle' => 'payment-logs',
                'slug' => '/payment-logs',
                'is_parent' => 0,
                'parent_id' => 34,

            ],
            [
                'name' => 'Payment Transaction',
                'handle' => 'payment-transaction',
                'slug' => '/payment-transaction',
                'is_parent' => 0,
                'parent_id' => 33,

            ],
            [
                'name' => 'Lumen Logs',
                'handle' => 'lumen-logs',
                'slug' => '/lumen-logs',
                'is_parent' => 0,
                'parent_id' => 34,

            ],
            [
                'name' => 'CC Push Logs',
                'handle' => 'cc-push-logs',
                'slug' => '/cc-push-logs',
                'is_parent' => 0,
                'parent_id' => 34,

            ],
            [
                'name' => 'Bre Logs',
                'handle' => 'bre-logs',
                'slug' => '/bre-logs',
                'is_parent' => 0,
                'parent_id' => 34,

            ],
        ];

        $now = Carbon::now();

        foreach ($menus as $menu) {
            $menu['is_active'] = 1;
            $menu['is_parent'] = 0;
            $menu['created_at'] = $now;
            $menu['created_by'] = 1;

            DB::table('menu_master')->insert($menu);
        }

        // Add parent menus
        $parentMenus = [
            [
                'name' => 'User Management',
                'is_active' => 1,
            ],
            [
                'name' => 'Masters',
                'is_active' => 1,
            ],
            [
                'name' => 'LMS',
                'is_active' => 1,
            ],
            [
                'name' => 'Logs',
                'is_active' => 1,
            ],
            [
                'name' => 'Website Submissions',
                'is_active' => 1,
            ],
        ];

        foreach ($parentMenus as $parentMenu) {
            $parentMenu['is_parent'] = 1;
            $parentMenu['parent_id'] = null;
            $parentMenu['created_at'] = $now;
            $parentMenu['created_by'] = 1;

            DB::table('menu_master')->insert($parentMenu);
        }
    }
}
