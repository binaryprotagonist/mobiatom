<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Model\PermissionGroup;
use App\Model\CountryMaster;
use App\Model\Organisation;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();
        $superAdminUser = new User();
        $superAdminUser->usertype              = 0;
        $superAdminUser->firstname          = 'SFA';
        $superAdminUser->lastname              = 'Super Admin';
        $superAdminUser->email                 = 'superadmin@admin.com';
        $superAdminUser->email_verified_at     = date('Y-m-d H:i:s');
        $superAdminUser->password              = \Hash::make('123456');
        $superAdminUser->country_id           = CountryMaster::whereName('India')->first()->id;
        $superAdminUser->mobile               = '9874563210';
        $superAdminUser->api_token          = Str::random(35);
        $superAdminUser->role_id               = 1;
        $superAdminUser->save();

        $orgAdminUser = new User();
        $orgAdminUser->usertype           = 1;
        $orgAdminUser->organisation_id    = 1;
        $orgAdminUser->firstname          = 'Engear';
        $orgAdminUser->lastname           = '7869';
        $orgAdminUser->email              = 'nfpc@gmail.com';
        $orgAdminUser->email_verified_at  = date('Y-m-d H:i:s');
        $orgAdminUser->password           = \Hash::make('123456');
        $orgAdminUser->country_id         = CountryMaster::whereName('United Arab Emirates')->first()->id;
        $orgAdminUser->mobile             = '507844941';
        $orgAdminUser->api_token          = Str::random(35);
        $orgAdminUser->role_id            = 2;
        $orgAdminUser->save();

        app()['cache']->forget('spatie.permission.cache');

        $permissionGroup = PermissionGroup::create(['name' => 'roles', 'module_name' => "Master"]);
        Permission::create(['name' => 'role-list', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);
        Permission::create(['name' => 'role-create', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'role-edit', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);
        Permission::create(['name' => 'role-delete', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'permissions', 'module_name' => "Master"]);
        Permission::create(['name' => 'permission-list', 'guard_name' => 'web', 'group_id'      => $permissionGroup->id]);
        Permission::create(['name' => 'permission-create', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);
        Permission::create(['name' => 'permission-edit', 'guard_name' => 'web', 'group_id'      => $permissionGroup->id]);
        Permission::create(['name' => 'permission-delete', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Organization', 'module_name' => "Master"]);
        Permission::create(['name' => 'organization-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'organization-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'organization-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Dashboard 2']);
        Permission::create(['name' => 'dashboard-2-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Dashboard 3']);
        Permission::create(['name' => 'dashboard-3-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Dashboard 4']);
        Permission::create(['name' => 'dashboard-4-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Dashboard 5']);
        Permission::create(['name' => 'dashboard-5-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Customer', 'module_name' => "Master"]);
        Permission::create(['name' => 'customer-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'customer-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Item', 'module_name' => "Master"]);
        Permission::create(['name' => 'item-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'item-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Salesman', 'module_name' => "Master"]);
        Permission::create(['name' => 'salesman-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Merchandiser', 'module_name' => "Master"]);
        Permission::create(['name' => 'merchandiser-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'merchandiser-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'merchandiser-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'merchandiser-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'merchandiser-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Journey Plan', 'module_name' => "Master"]);
        Permission::create(['name' => 'journey-plans-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Promotion', 'module_name' => "Master"]);
        Permission::create(['name' => 'promotion-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Pricing', 'module_name' => "Master"]);
        Permission::create(['name' => 'pricing-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Discount', 'module_name' => "Master"]);
        Permission::create(['name' => 'discount-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'discount-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'discount-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'discount-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'discount-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Rebate', 'module_name' => "Master"]);
        Permission::create(['name' => 'rebate-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'rebate-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'rebate-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'rebate-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'rebate-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Order', 'module_name' => "Master"]);
        Permission::create(['name' => 'order-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'order-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'order-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'order-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'order-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Delivery', 'module_name' => "Master"]);
        Permission::create(['name' => 'delivery-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'delivery-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'delivery-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'delivery-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'delivery-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Invoice', 'module_name' => "Master"]);
        Permission::create(['name' => 'invoice-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'invoice-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'invoice-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'invoice-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'invoice-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Credit Note', 'module_name' => "Master"]);
        Permission::create(['name' => 'credit-note-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Debit Note', 'module_name' => "Master"]);
        Permission::create(['name' => 'debit-note-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'debit-note-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'debit-note-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'debit-note-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'debit-note-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Collection', 'module_name' => "Master"]);
        Permission::create(['name' => 'collection-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'collection-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'collection-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'collection-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'collection-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Route Item Grouping', 'module_name' => "Master"]);
        Permission::create(['name' => 'route-item-grouping-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Portfolio Management', 'module_name' => "Master"]);
        Permission::create(['name' => 'portfolio-management-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Salesman Load', 'module_name' => "Master"]);
        Permission::create(['name' => 'salesman-load-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-load-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-load-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-load-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-load-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Load Request', 'module_name' => "Master"]);
        Permission::create(['name' => 'load-request-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'load-request-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'load-request-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'load-request-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'load-request-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Salesman Unload', 'module_name' => "Master"]);
        Permission::create(['name' => 'salesman-unload-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-unload-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-unload-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-unload-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-unload-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Van to Van Transfer', 'module_name' => "Master"]);
        Permission::create(['name' => 'van-to-van-transfer-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'van-to-van-transfer-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'van-to-van-transfer-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'van-to-van-transfer-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'van-to-van-transfer-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Vendor', 'module_name' => "Master"]);
        Permission::create(['name' => 'vendor-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'vendor-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'vendor-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'vendor-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'vendor-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Purchase Order', 'module_name' => "Master"]);
        Permission::create(['name' => 'purchase-order-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'purchase-order-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'purchase-order-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'purchase-order-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'purchase-order-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Manual Inventory', 'module_name' => "Master"]);
        Permission::create(['name' => 'manual-inventory-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'manual-inventory-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'manual-inventory-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'manual-inventory-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'manual-inventory-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'GRN', 'module_name' => "Master"]);
        Permission::create(['name' => 'grn-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'grn-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'grn-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'grn-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'grn-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Depot Damage Expiry', 'module_name' => "Master"]);
        Permission::create(['name' => 'depot-damages-expires-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'depot-damages-expires-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'depot-damages-expires-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'depot-damages-expires-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'depot-damages-expires-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Stock Adjustment', 'module_name' => "Master"]);
        Permission::create(['name' => 'stock-adjustment-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-adjustment-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-adjustment-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'stock-adjustment-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-adjustment-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Cashier Receipt', 'module_name' => "Master"]);
        Permission::create(['name' => 'cashier-receipt-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'cashier-receipt-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'cashier-receipt-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'cashier-receipt-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'cashier-receipt-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'PDC', 'module_name' => "Master"]);
        Permission::create(['name' => 'pdc-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pdc-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pdc-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'pdc-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pdc-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Salesman Reconciliation', 'module_name' => "Master"]);
        Permission::create(['name' => 'salesman-reconciliation-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-reconciliation-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-reconciliation-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-reconciliation-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-reconciliation-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Session Endorsement', 'module_name' => "Master"]);
        Permission::create(['name' => 'session-endorsement-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'session-endorsement-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'session-endorsement-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'session-endorsement-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'session-endorsement-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Sales Target', 'module_name' => "Master"]);
        Permission::create(['name' => 'sales-target-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Expenses', 'module_name' => "Master"]);
        Permission::create(['name' => 'expense-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'expense-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'expense-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'expense-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'expense-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Estimate', 'module_name' => "Master"]);
        Permission::create(['name' => 'estimate-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'estimate-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'estimate-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'estimate-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'estimate-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Stock In Store', 'module_name' => "Master"]);
        Permission::create(['name' => 'stock-in-store-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Complaint Feedback', 'module_name' => "Master"]);
        Permission::create(['name' => 'complaint-feedback-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Competitor Info', 'module_name' => "Master"]);
        Permission::create(['name' => 'competitor-info-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Campaign', 'module_name' => "Master"]);
        Permission::create(['name' => 'campaign-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Planogram', 'module_name' => "Master"]);
        Permission::create(['name' => 'planogram-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Shelf Display', 'module_name' => "Master"]);
        Permission::create(['name' => 'shelf-display-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Asset Tracking', 'module_name' => "Master"]);
        Permission::create(['name' => 'asset-tracking-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Consumer Survey', 'module_name' => "Master"]);
        Permission::create(['name' => 'consumer-survey-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Sensory Survey', 'module_name' => "Master"]);
        Permission::create(['name' => 'sensory-survey-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Promotional Accountability', 'module_name' => "Master"]);
        Permission::create(['name' => 'promotional-accountability-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Product Catalog', 'module_name' => "Master"]);
        Permission::create(['name' => 'product-catalog-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'product-catalog-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'product-catalog-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'product-catalog-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'product-catalog-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'New Lunch', 'module_name' => "Master"]);
        Permission::create(['name' => 'new-lunch-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Share Of Shelf', 'module_name' => "Master"]);
        Permission::create(['name' => 'share-of-shelf-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Pricing Check', 'module_name' => "Master"]);
        Permission::create(['name' => 'pricing-check-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Market Promotion', 'module_name' => "Master"]);
        Permission::create(['name' => 'market-promotion-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'WorkFlow', 'module_name' => "Master"]);
        Permission::create(['name' => 'work-flow-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'work-flow-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'work-flow-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'work-flow-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'work-flow-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Warehouse', 'module_name' => "Master"]);
        Permission::create(['name' => 'warehouse-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'warehouse-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'warehouse-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'warehouse-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'warehouse-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);


        $permissionGroup = PermissionGroup::create(['name' => 'Planogram Compliance', 'module_name' => "Reports"]);
        Permission::create(['name' => 'planogram-compliance-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Visual Merchandising', 'module_name' => "Reports"]);
        Permission::create(['name' => 'visual-merchandising-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Merchandising Audit', 'module_name' => "Reports"]);
        Permission::create(['name' => 'merchandising-audit-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Category', 'module_name' => "Reports"]);
        Permission::create(['name' => 'category-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Journey Plan Compliance', 'module_name' => "Reports"]);
        Permission::create(['name' => 'journey-plan-compliance-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Competitor Product', 'module_name' => "Reports"]);
        Permission::create(['name' => 'competitor-product-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Order/Returns', 'module_name' => "Reports"]);
        Permission::create(['name' => 'order-returns-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Time Sheets', 'module_name' => "Reports"]);
        Permission::create(['name' => 'time-sheets-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Photos', 'module_name' => "Reports"]);
        Permission::create(['name' => 'photos-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'New Customer', 'module_name' => "Reports"]);
        Permission::create(['name' => 'new-customer-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Closed Visits', 'module_name' => "Reports"]);
        Permission::create(['name' => 'closed-visits-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Visit Summary', 'module_name' => "Reports"]);
        Permission::create(['name' => 'visit-summary-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Order Summary', 'module_name' => "Reports"]);
        Permission::create(['name' => 'order-summary-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Task Answers', 'module_name' => "Reports"]);
        Permission::create(['name' => 'task-answers-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Task Summary', 'module_name' => "Reports"]);
        Permission::create(['name' => 'task-summary-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'SOS', 'module_name' => "Reports"]);
        Permission::create(['name' => 'sos-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Stock Availability', 'module_name' => "Reports"]);
        Permission::create(['name' => 'stock-availability-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Store Summary', 'module_name' => "Reports"]);
        Permission::create(['name' => 'store-summary-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Users & Roles', 'module_name' => "Master"]);
        Permission::create(['name' => 'users-roles-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'users-roles-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'users-roles-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'users-roles-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'users-roles-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Preferences', 'module_name' => "Master"]);
        Permission::create(['name' => 'preferences-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'preferences-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'preferences-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'preferences-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'preferences-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Taxes', 'module_name' => "Master"]);
        Permission::create(['name' => 'taxes-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'taxes-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'taxes-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'taxes-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'taxes-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Reason', 'module_name' => "Master"]);
        Permission::create(['name' => 'reason-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'reason-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'reason-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'reason-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'reason-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Bank', 'module_name' => "Master"]);
        Permission::create(['name' => 'bank-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'bank-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'bank-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'bank-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'bank-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Currency', 'module_name' => "Master"]);
        Permission::create(['name' => 'currency-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'currency-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'currency-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'currency-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'currency-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Country', 'module_name' => "Master"]);
        Permission::create(['name' => 'country-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'country-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'country-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'country-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'country-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Region', 'module_name' => "Master"]);
        Permission::create(['name' => 'region-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'region-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'region-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'region-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'region-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Area', 'module_name' => "Master"]);
        Permission::create(['name' => 'area-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'area-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'area-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'area-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'area-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Van Master', 'module_name' => "Master"]);
        Permission::create(['name' => 'van-master-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'van-master-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'van-master-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'van-master-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'van-master-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Branch Depot', 'module_name' => "Master"]);
        Permission::create(['name' => 'branch-depot-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'branch-depot-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'branch-depot-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'branch-depot-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'branch-depot-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Route', 'module_name' => "Master"]);
        Permission::create(['name' => 'route-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'route-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Customer Category', 'module_name' => "Master"]);
        Permission::create(['name' => 'customer-category-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-category-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-category-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'customer-category-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-category-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        // $permissionGroup = PermissionGroup::create(['name' => 'channel', 'module_name' => "Master"]);
        // Permission::create(['name' => 'channel-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        // Permission::create(['name' => 'channel-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        // Permission::create(['name' => 'channel-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        // Permission::create(['name' => 'channel-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        // Permission::create(['name' => 'channel-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Credit Limits', 'module_name' => "Master"]);
        Permission::create(['name' => 'credit-limit-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-limit-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-limit-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'credit-limit-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-limit-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        // $permissionGroup = PermissionGroup::create(['name' => 'sales organisations', 'module_name' => "Master"]);
        // Permission::create(['name' => 'sales-organisation-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        // Permission::create(['name' => 'sales-organisation-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        // Permission::create(['name' => 'sales-organisation-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        // Permission::create(['name' => 'sales-organisation-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        // Permission::create(['name' => 'sales-organisation-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Outlet Product Code', 'module_name' => "Master"]);
        Permission::create(['name' => 'outlet-product-code-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'outlet-product-code-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'outlet-product-code-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'outlet-product-code-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'outlet-product-code-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Brand', 'module_name' => "Master"]);
        Permission::create(['name' => 'brand-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'brand-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'brand-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'brand-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'brand-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Item Group', 'module_name' => "Master"]);
        Permission::create(['name' => 'item-group-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'UOM', 'module_name' => "Master"]);
        Permission::create(['name' => 'item-uom-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Premium', 'module_name' => "Master"]);
        Permission::create(['name' => 'premium-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'premium-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'premium-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'premium-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'premium-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Listing Fee', 'module_name' => "Master"]);
        Permission::create(['name' => 'listing-fee-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'listing-fee-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'listing-fee-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'listing-fee-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'listing-fee-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Shelf Rent', 'module_name' => "Master"]);
        Permission::create(['name' => 'shelf-rent-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-rent-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-rent-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-rent-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-rent-delete', 'guard_name' => 'web', 'group_id' => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'Load Sheet', 'module_name' => "Report"]);
        Permission::create(['name' => 'load-sheet-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Sales Analysis', 'module_name' => "Report"]);
        Permission::create(['name' => 'sales-analysis-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Periodic Wise Collection', 'module_name' => "Report"]);
        Permission::create(['name' => 'periodic-wise-collection-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Sales Summary By Product Class', 'module_name' => "Report"]);
        Permission::create(['name' => 'sales-summary-by-product-class-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Product Summary By Customer Sales', 'module_name' => "Report"]);
        Permission::create(['name' => 'product-summary-by-customer-sales-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Customer Sales Per Month', 'module_name' => "Report"]);
        Permission::create(['name' => 'customer-sales-per-month-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Customer Statement', 'module_name' => "Report"]);
        Permission::create(['name' => 'customer-statement-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Customer Summary By Product', 'module_name' => "Report"]);
        Permission::create(['name' => 'customer-summary-by-product-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Order Reports', 'module_name' => "Report"]);
        Permission::create(['name' => 'order-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Van Customer', 'module_name' => "Report"]);
        Permission::create(['name' => 'van-customer-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Weekly Customer Calls', 'module_name' => "Report"]);
        Permission::create(['name' => 'weekly-customer-calls-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Visit Analysis By Van or Salesman', 'module_name' => "Report"]);
        Permission::create(['name' => 'visit-analysis-by-van-or-salesman-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Carry Over', 'module_name' => "Report"]);
        Permission::create(['name' => 'carry-over-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Daily Field Activity', 'module_name' => "Report"]);
        Permission::create(['name' => 'daily-field-activity-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Monthly Ageing', 'module_name' => "Report"]);
        Permission::create(['name' => 'monthly-ageing-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Sales Quantity Analysis', 'module_name' => "Report"]);
        Permission::create(['name' => 'sales-quantity-analysis-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Trip Execution', 'module_name' => "Report"]);
        Permission::create(['name' => 'trip-execution-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Order Returns', 'module_name' => "Report"]);
        Permission::create(['name' => 'order-returns-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'New Customer', 'module_name' => "Report"]);
        Permission::create(['name' => 'new-customer-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Time Sheet', 'module_name' => "Report"]);
        Permission::create(['name' => 'time-sheet-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Closed Visits', 'module_name' => "Report"]);
        Permission::create(['name' => 'closed-visits-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        $permissionGroup = PermissionGroup::create(['name' => 'Visit Summary', 'module_name' => "Report"]);
        Permission::create(['name' => 'visit-summary-report-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        // create superadmin roles and assign existing permissions
        $superAdminRole = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo(Permission::all());
        $superAdminUser->assignRole('superadmin');

        // create admin and assign permissions
        $orgAdminRole = Role::create(['name' => 'org-admin', 'guard_name' => 'web']);
        $NSMRole = Role::create(['name' => 'NSM', 'guard_name' => 'web']);
        $ASMRole = Role::create(['name' => 'ASM', 'guard_name' => 'web']);
        $superVisorRole = Role::create(['name' => 'Supervisor', 'guard_name' => 'web']);

        $admin = ' role-list role-create role-edit role-delete permission-list permission-create permission-edit permission-delete organization-detail organization-add organization-edit dashboard-2-list dashboard-3-list dashboard-4-list dashboard-5-list customer-list customer-detail customer-add customer-edit customer-delete item-list item-detail item-add item-edit item-delete salesman-list salesman-detail salesman-add salesman-edit salesman-delete merchandiser-list merchandiser-detail merchandiser-add merchandiser-edit merchandiser-delete journey-plans-list journey-plans-detail journey-plans-add journey-plans-edit journey-plans-delete promotion-list promotion-detail promotion-add promotion-edit promotion-delete pricing-list pricing-detail pricing-add pricing-edit pricing-delete discount-list discount-detail discount-add discount-edit discount-delete rebate-list rebate-detail rebate-add rebate-edit rebate-delete order-list order-detail order-add order-edit order-delete delivery-list delivery-detail delivery-add delivery-edit delivery-delete invoice-list invoice-detail invoice-add invoice-edit invoice-delete credit-note-list credit-note-detail credit-note-add credit-note-edit credit-note-delete debit-note-list debit-note-detail debit-note-add debit-note-edit debit-note-delete collection-list collection-detail collection-add collection-edit collection-delete route-item-grouping-list route-item-grouping-detail route-item-grouping-add route-item-grouping-edit route-item-grouping-delete portfolio-management-list portfolio-management-detail portfolio-management-add portfolio-management-edit portfolio-management-delete salesman-load-list salesman-load-detail salesman-load-add salesman-load-edit salesman-load-delete load-request-list load-request-detail load-request-add load-request-edit load-request-delete salesman-unload-list salesman-unload-detail salesman-unload-add salesman-unload-edit salesman-unload-delete van-to-van-transfer-detail van-to-van-transfer-add van-to-van-transfer-edit van-to-van-transfer-delete van-to-van-transfer-list vendor-list vendor-detail vendor-add vendor-edit vendor-delete purchase-order-list purchase-order-detail purchase-order-add purchase-order-edit purchase-order-delete manual-inventory-list manual-inventory-detail manual-inventory-add manual-inventory-edit manual-inventory-delete grn-list grn-detail grn-add grn-edit grn-delete depot-damages-expires-list depot-damages-expires-detail depot-damages-expires-add depot-damages-expires-edit depot-damages-expires-delete stock-adjustment-list stock-adjustment-detail stock-adjustment-add stock-adjustment-edit stock-adjustment-delete cashier-receipt-list cashier-receipt-detail cashier-receipt-add cashier-receipt-edit cashier-receipt-delete pdc-list pdc-detail pdc-add pdc-edit pdc-delete salesman-reconciliation-list salesman-reconciliation-detail salesman-reconciliation-add salesman-reconciliation-edit salesman-reconciliation-delete session-endorsement-list session-endorsement-detail session-endorsement-add session-endorsement-edit session-endorsement-delete sales-target-list sales-target-detail sales-target-add sales-target-edit sales-target-delete expense-list expense-detail expense-add expense-edit expense-delete estimate-list estimate-detail estimate-add estimate-edit estimate-delete stock-in-store-list stock-in-store-detail stock-in-store-add stock-in-store-edit stock-in-store-delete complaint-feedback-list complaint-feedback-detail complaint-feedback-add complaint-feedback-edit complaint-feedback-delete competitor-info-list competitor-info-detail competitor-info-add competitor-info-edit competitor-info-delete campaign-list campaign-detail campaign-add campaign-edit campaign-delete planogram-list planogram-detail planogram-add planogram-edit planogram-delete shelf-display-list shelf-display-detail shelf-display-add shelf-display-edit shelf-display-delete asset-tracking-list asset-tracking-detail asset-tracking-add asset-tracking-edit asset-tracking-delete consumer-survey-list consumer-survey-detail consumer-survey-add consumer-survey-edit consumer-survey-delete sensory-survey-list sensory-survey-detail sensory-survey-add sensory-survey-edit sensory-survey-delete promotional-accountability-list promotional-accountability-detail promotional-accountability-add promotional-accountability-edit promotional-accountability-delete product-catalog-list product-catalog-detail product-catalog-add product-catalog-edit product-catalog-delete new-lunch-list new-lunch-detail new-lunch-add new-lunch-edit new-lunch-delete share-of-shelf-list share-of-shelf-detail share-of-shelf-add share-of-shelf-edit share-of-shelf-delete pricing-check-list pricing-check-detail pricing-check-add pricing-check-edit pricing-check-delete market-promotion-list market-promotion-detail market-promotion-add market-promotion-edit market-promotion-delete work-flow-list work-flow-detail work-flow-add work-flow-edit work-flow-delete warehouse-list warehouse-detail warehouse-add warehouse-edit warehouse-delete planogram-compliance-list visual-merchandising-list merchandising-audit-list category-list journey-plan-compliance-list competitor-product-list order-returns-list time-sheets-list photos-list new-customer-list closed-visits-list visit-summary-list order-summary-list task-answers-list task-summary-list sos-list stock-availability-list store-summary-list users-roles-list users-roles-detail users-roles-add users-roles-edit users-roles-delete preferences-list preferences-detail preferences-add preferences-edit preferences-delete taxes-list taxes-detail taxes-add taxes-edit taxes-delete reason-list reason-detail reason-add reason-edit reason-delete bank-list bank-detail bank-add bank-edit bank-delete currency-list currency-detail currency-add currency-edit currency-delete country-list country-detail country-add country-edit country-delete region-list region-detail region-add region-edit region-delete area-list area-detail area-add area-edit area-delete van-master-list van-master-detail van-master-add van-master-edit van-master-delete branch-depot-list branch-depot-detail branch-depot-add branch-depot-edit branch-depot-delete route-list route-detail route-add route-edit route-delete customer-category-list customer-category-detail customer-category-add customer-category-edit customer-category-delete credit-limit-list credit-limit-detail credit-limit-add credit-limit-edit credit-limit-delete outlet-product-code-list outlet-product-code-detail outlet-product-code-add outlet-product-code-edit outlet-product-code-delete brand-list brand-detail brand-add brand-edit brand-delete item-group-list item-group-detail item-group-add item-group-edit item-group-delete item-uom-list item-uom-detail item-uom-add item-uom-edit item-uom-delete listing-fee-list listing-fee-detail listing-fee-add listing-fee-edit listing-fee-delete shelf-rent-list shelf-rent-detail shelf-rent-add shelf-rent-edit shelf-rent-delete load-sheet-report-list sales-analysis-report-list periodic-wise-collection-report-list sales-summary-by-product-class-report-list product-summary-by-customer-sales-report-list customer-sales-per-month-report-list customer-statement-report-list customer-summary-by-product-report-list order-report-list van-customer-report-list weekly-customer-calls-report-list';

        // $admin = 'organization-detail organization-add organization-edit country-list country-detail country-add country-edit country-delete region-list region-detail region-add region-edit region-delete area-list area-detail area-add area-edit area-delete branch-depot-list branch-depot-detail branch-depot-add branch-depot-edit branch-depot-delete van-master-list van-master-detail van-master-add van-master-edit van-master-delete route-list route-detail route-add route-edit route-delete collection-list collection-detail collection-add collection-edit collection-delete credit-limit-list credit-limit-detail credit-limit-add credit-limit-edit credit-limit-delete outlet-product-code-list outlet-product-code-detail outlet-product-code-add outlet-product-code-edit outlet-product-code-delete brand-list brand-detail brand-add brand-edit brand-delete credit-note-list credit-note-detail credit-note-add credit-note-edit credit-note-delete debit-note-list debit-note-detail debit-note-add debit-note-edit debit-note-delete item-group-list item-group-detail item-group-add item-group-edit item-group-delete item-uom-list item-uom-detail item-uom-add item-uom-edit item-uom-delete customer-list customer-detail customer-add customer-edit customer-delete item-list item-detail item-add item-edit item-delete salesman-list salesman-detail salesman-add salesman-edit salesman-delete delivery-list delivery-detail delivery-add delivery-edit delivery-delete invoice-list invoice-detail invoice-add invoice-edit invoice-delete journey-plans-list journey-plans-detail journey-plans-add journey-plans-edit journey-plans-delete order-list order-detail order-add order-edit order-delete promotion-list promotion-detail promotion-add promotion-edit promotion-delete pricing-list pricing-detail pricing-add pricing-edit pricing-delete discount-list discount-detail discount-add discount-edit discount-delete rebate-list rebate-detail rebate-add rebate-edit rebate-delete route-item-grouping-list route-item-grouping-detail route-item-grouping-add route-item-grouping-edit route-item-grouping-delete portfolio-management-list portfolio-management-detail portfolio-management-add portfolio-management-edit portfolio-management-delete salesman-load-list salesman-load-detail salesman-load-add salesman-load-edit salesman-load-delete van-to-van-transfer-list van-to-van-transfer-detail van-to-van-transfer-add van-to-van-transfer-edit van-to-van-transfer-delete vendor-list vendor-detail vendor-add vendor-edit vendor-delete purchase-order-list purchase-order-detail purchase-order-add purchase-order-edit purchase-order-delete grn-list grn-detail grn-add grn-edit grn-delete depot-damages-expires-list depot-damages-expires-detail depot-damages-expires-add depot-damages-expires-edit depot-damages-expires-delete stock-adjustment-list stock-adjustment-detail stock-adjustment-add stock-adjustment-edit stock-adjustment-delete cashier-receipt-list cashier-receipt-detail cashier-receipt-add cashier-receipt-edit cashier-receipt-delete pdc-list pdc-detail pdc-add pdc-edit pdc-delete salesman-reconciliation-list salesman-reconciliation-detail salesman-reconciliation-add salesman-reconciliation-edit salesman-reconciliation-delete session-endorsement-list session-endorsement-detail session-endorsement-add session-endorsement-edit session-endorsement-delete sales-target-list sales-target-detail sales-target-add sales-target-edit sales-target-delete expense-list expense-detail expense-add expense-edit expense-delete estimate-list estimate-detail estimate-add estimate-edit estimate-delete bank-list bank-detail bank-add bank-edit bank-delete stock-in-store-list stock-in-store-detail stock-in-store-add stock-in-store-edit stock-in-store-delete complaint-feedback-list complaint-feedback-detail complaint-feedback-add complaint-feedback-edit complaint-feedback-delete competitor-info-list competitor-info-detail competitor-info-add competitor-info-edit competitor-info-delete campaign-list campaign-detail campaign-add campaign-edit campaign-delete planogram-list planogram-detail planogram-add planogram-edit planogram-delete shelf-display-list shelf-display-detail shelf-display-add shelf-display-edit shelf-display-delete asset-tracking-list asset-tracking-detail asset-tracking-add asset-tracking-edit asset-tracking-delete consumer-survey-list consumer-survey-detail consumer-survey-add consumer-survey-edit consumer-survey-delete sensory-survey-list sensory-survey-detail sensory-survey-add sensory-survey-edit sensory-survey-delete promotional-accountability-list promotional-accountability-detail promotional-accountability-add promotional-accountability-edit promotional-accountability-delete product-catalog-list product-catalog-detail product-catalog-add product-catalog-edit product-catalog-delete new-lunch-list new-lunch-detail new-lunch-add new-lunch-edit new-lunch-delete share-of-shelf-list share-of-shelf-detail share-of-shelf-add share-of-shelf-edit share-of-shelf-delete pricing-check-list pricing-check-detail pricing-check-add pricing-check-edit pricing-check-delete market-promotion-list market-promotion-detail market-promotion-add market-promotion-edit market-promotion-delete workFlow-list workFlow-detail workFlow-add workFlow-edit workFlow-delete dashboard-2-list dashboard-3-list dashboard-4-list dashboard-5-list planogram-compliance-list visual-merchandising-list merchandising-audit-list category-list journey-plan-compliance-list competitor-product-list order-returns-list time-sheets-list photos-list new-customer-list closed-visits-list visit-summary-list order-summary-list';

        foreach (explode(' ', $admin) as $key => $value) {
            $orgAdminRole->givePermissionTo($value);
            $NSMRole->givePermissionTo($value);
            $ASMRole->givePermissionTo($value);
            $superVisorRole->givePermissionTo($value);
        }

        $manager = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $cust = 'organization-detail';
        foreach (explode(' ', $cust) as $key => $value) {
            $manager->givePermissionTo($value);
        }

        if ($orgAdminUser) {
            $roles = Role::find(2); //assigned org-roles all permission
            foreach ($roles->permissions as $key => $permission) {
                $orgAdminUser->givePermissionTo($permission->name);
            }
        }
    }
}
