<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Model\PermissionGroup;

class MerchandisingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionGroup = PermissionGroup::create(['name' => 'roles']);
        Permission::create(['name' => 'role-list', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);
        Permission::create(['name' => 'role-create', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'role-edit', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);
        Permission::create(['name' => 'role-delete', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'permissions']);
        Permission::create(['name' => 'permission-list', 'guard_name' => 'web', 'group_id'      => $permissionGroup->id]);
        Permission::create(['name' => 'permission-create', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);
        Permission::create(['name' => 'permission-edit', 'guard_name' => 'web', 'group_id'      => $permissionGroup->id]);
        Permission::create(['name' => 'permission-delete', 'guard_name' => 'web', 'group_id'    => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'organizations']);
        Permission::create(['name' => 'organization-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'organization-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'organization-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'asset tracking']);
        Permission::create(['name' => 'asset-tracking-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'asset-tracking-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'campaign']);
        Permission::create(['name' => 'campaign-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'campaign-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'competitor info']);
        Permission::create(['name' => 'competitor-info-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'competitor-info-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'complaint feedback']);
        Permission::create(['name' => 'complaint-feedback-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'complaint-feedback-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'consumer survey']);
        Permission::create(['name' => 'consumer-survey-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'consumer-survey-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'credit notes']);
        Permission::create(['name' => 'credit-note-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'credit-note-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'custom field']);
        Permission::create(['name' => 'custom-field-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'custom-field-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'custom-field-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'custom-field-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'custom-field-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'customer']);
        Permission::create(['name' => 'customer-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'customer-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'customer-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'discounts']);
        Permission::create(['name' => 'discount-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'discount-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'discount-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'discount-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'discount-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'items']);
        Permission::create(['name' => 'item-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'item-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'item uoms']);
        Permission::create(['name' => 'item-uom-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-uom-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'item groups']);
        Permission::create(['name' => 'item-group-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'item-group-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'journey plans']);
        Permission::create(['name' => 'journey-plans-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'journey-plans-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'market promotion']);
        Permission::create(['name' => 'market-promotion-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'market-promotion-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'new lunch']);
        Permission::create(['name' => 'new-lunch-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'new-lunch-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'orders']);
        Permission::create(['name' => 'order-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'order-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'order-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'order-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'order-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'planogram']);
        Permission::create(['name' => 'planogram-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'planogram-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'portfolio managements']);
        Permission::create(['name' => 'portfolio-management-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'portfolio-management-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'pricing check']);
        Permission::create(['name' => 'pricing-check-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-check-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'promotions']);
        Permission::create(['name' => 'promotion-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotion-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'pricing']);
        Permission::create(['name' => 'pricing-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'pricing-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'promotional accountability']);
        Permission::create(['name' => 'promotional-accountability-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'promotional-accountability-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'route item groupings']);
        Permission::create(['name' => 'route-item-grouping-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'route-item-grouping-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'sales targets']);
        Permission::create(['name' => 'sales-target-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sales-target-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'salesmans']);
        Permission::create(['name' => 'salesman-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'salesman-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'sensory survey']);
        Permission::create(['name' => 'sensory-survey-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'sensory-survey-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'share of shelf']);
        Permission::create(['name' => 'share-of-shelf-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'share-of-shelf-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'shelf display']);
        Permission::create(['name' => 'shelf-display-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'shelf-display-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'stock in store']);
        Permission::create(['name' => 'stock-in-store-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'stock-in-store-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'workflow']);
        Permission::create(['name' => 'workflow-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'workflow-detail', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'workflow-add', 'guard_name' => 'web', 'group_id'   => $permissionGroup->id]);
        Permission::create(['name' => 'workflow-edit', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);
        Permission::create(['name' => 'workflow-delete', 'guard_name' => 'web', 'group_id'=> $permissionGroup->id]);

        $permissionGroup = PermissionGroup::create(['name' => 'reports']);
        Permission::create(['name' => 'new-lunch-list', 'guard_name' => 'web', 'group_id'  => $permissionGroup->id]);

        // create superadmin roles and assign existing permissions
        $superAdminRole = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo(Permission::all());
        // $superAdminUser->assignRole('superadmin');

        // create admin and assign permissions
        $orgAdminRole = Role::create(['name' => 'org-admin', 'guard_name' => 'web']);
        $admin = 'organization-detail organization-add organization-edit country-list country-detail country-add country-edit country-delete region-list region-detail region-add region-edit region-delete area-list area-detail area-add area-edit area-delete branch-depot-list branch-depot-detail branch-depot-add branch-depot-edit branch-depot-delete van-master-list van-master-detail van-master-add van-master-edit van-master-delete route-list route-detail route-add route-edit route-delete collection-list collection-detail collection-add collection-edit collection-delete credit-limit-list credit-limit-detail credit-limit-add credit-limit-edit credit-limit-delete outlet-product-code-list outlet-product-code-detail outlet-product-code-add outlet-product-code-edit outlet-product-code-delete brand-list brand-detail brand-add brand-edit brand-delete credit-note-list credit-note-detail credit-note-add credit-note-edit credit-note-delete debit-note-list debit-note-detail debit-note-add debit-note-edit debit-note-delete item-group-list item-group-detail item-group-add item-group-edit item-group-delete item-uom-list item-uom-detail item-uom-add item-uom-edit item-uom-delete customer-list customer-detail customer-add customer-edit customer-delete item-list item-detail item-add item-edit item-delete salesman-list salesman-detail salesman-add salesman-edit salesman-delete delivery-list delivery-detail delivery-add delivery-edit delivery-delete invoice-list invoice-detail invoice-add invoice-edit invoice-delete journey-plans-list journey-plans-detail journey-plans-add journey-plans-edit journey-plans-delete order-list order-detail order-add order-edit order-delete promotion-list promotion-detail promotion-add promotion-edit promotion-delete pricing-list pricing-detail pricing-add pricing-edit pricing-delete discount-list discount-detail discount-add discount-edit discount-delete rebate-list rebate-detail rebate-add rebate-edit rebate-delete route-item-grouping-list route-item-grouping-detail route-item-grouping-add route-item-grouping-edit route-item-grouping-delete portfolio-management-list portfolio-management-detail portfolio-management-add portfolio-management-edit portfolio-management-delete salesman-load-list salesman-load-detail salesman-load-add salesman-load-edit salesman-load-delete van-to-van-transfer-list van-to-van-transfer-detail van-to-van-transfer-add van-to-van-transfer-edit van-to-van-transfer-delete vendor-list vendor-detail vendor-add vendor-edit vendor-delete purchase-order-list purchase-order-detail purchase-order-add purchase-order-edit purchase-order-delete grn-list grn-detail grn-add grn-edit grn-delete depot-damages-expires-list depot-damages-expires-detail depot-damages-expires-add depot-damages-expires-edit depot-damages-expires-delete stock-adjustment-list stock-adjustment-detail stock-adjustment-add stock-adjustment-edit stock-adjustment-delete cashier-receipt-list cashier-receipt-detail cashier-receipt-add cashier-receipt-edit cashier-receipt-delete pdc-list pdc-detail pdc-add pdc-edit pdc-delete salesman-reconciliation-list salesman-reconciliation-detail salesman-reconciliation-add salesman-reconciliation-edit salesman-reconciliation-delete session-endorsement-list session-endorsement-detail session-endorsement-add session-endorsement-edit session-endorsement-delete sales-target-list sales-target-detail sales-target-add sales-target-edit sales-target-delete expense-list expense-detail expense-add expense-edit expense-delete estimate-list estimate-detail estimate-add estimate-edit estimate-delete bank-list bank-detail bank-add bank-edit bank-delete stock-in-store-list stock-in-store-detail stock-in-store-add stock-in-store-edit stock-in-store-delete complaint-feedback-list complaint-feedback-detail complaint-feedback-add complaint-feedback-edit complaint-feedback-delete competitor-info-list competitor-info-detail competitor-info-add competitor-info-edit competitor-info-delete campaign-list campaign-detail campaign-add campaign-edit campaign-delete planogram-list planogram-detail planogram-add planogram-edit planogram-delete shelf-display-list shelf-display-detail shelf-display-add shelf-display-edit shelf-display-delete asset-tracking-list asset-tracking-detail asset-tracking-add asset-tracking-edit asset-tracking-delete consumer-survey-list consumer-survey-detail consumer-survey-add consumer-survey-edit consumer-survey-delete sensory-survey-list sensory-survey-detail sensory-survey-add sensory-survey-edit sensory-survey-delete promotional-accountability-list promotional-accountability-detail promotional-accountability-add promotional-accountability-edit promotional-accountability-delete';
        foreach (explode(' ', $admin) as $key => $value) {
            $orgAdminRole->givePermissionTo($value);
        }
    }
}
