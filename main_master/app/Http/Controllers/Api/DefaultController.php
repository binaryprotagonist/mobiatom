<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\Channel;
use App\Model\CombinationMaster;
use App\Model\CombinationPlanKey;
use App\Model\CustomerCategory;
use App\Model\CustomerGroup;
use App\Model\CustomerInfo;
use App\Model\CustomerLob;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\Item;
use App\Model\ItemLob;
use App\Model\ItemMainPrice;
use App\Model\ItemMajorCategory;
use App\Model\ItemUom;
use App\Model\Lob;
use App\Model\PaymentTerm;
use App\Model\PDPCustomer;
use App\Model\PDPItem;
use App\Model\PriceDiscoPromoPlan;
use App\Model\Region;
use App\Model\Route;
use App\Model\SalesmanInfo;
use App\Model\SalesmanLob;
use App\Model\SalesmanNumberRange;
use App\Model\SalesOrganisation;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;
use App\Model\Warehouse;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use File;
use \JsonMachine\JsonMachine;
use Ixudra\Curl\Facades\Curl;

class DefaultController extends Controller
{

    public function index(Request $request)
    {
        if ($request->type == "customer") {
            // $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/customers');

            $data = File::get("C:\Users\admin\Downloads\customer.txt");
            $this->saveCustomer($data);

            return 'done';
            // pre($data);
        } else if ($request->type == "salesman") {
            $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/salesman');
            $this->saveSalesman($data);
            return 'done';
            pre($data);
        } else if ($request->type == "route") {
            $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/route');
            $this->saveRoute($data);
            return 'done';
            pre($data);
        } else if ($request->type == "invoice") {
            $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/invoice');
            $this->saveInvoice($data);
            return 'done';
            pre($data);
        } else if ($request->type == "item") {
            $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/item');
            $this->saveItem($data);
            return 'done';
            pre($data);
        } else if ($request->type == "pricing") {
            $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/pricelist');
            $this->savePricing($data);
            return 'done';
        } else if ($request->type == "stock") {
            // $data = $this->getData('http://rfctest.dyndns.org:11214/api/get/stock');

            $data = Curl::to('http://rfctest.dyndns.org:11214/api/get/stock')
                ->withData(array('params' => array("name" => $request->name)))
                ->asJson()
                ->post();

            $this->saveStock($data->result, $request->name);
            return 'done';
            pre($data);
        }
    }

    private function saveStock($data, $name)
    {
        $data = json_decode($data, true);
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['Stock'])) {
                        collect($data['response'][0]['Stock'])->each(function ($stock, $key) use ($name) {
                            if ($stock) {
                                $storageLocation = Storagelocation::where('name', 'like', '%' . $name . '%')
                                    ->where('loc_type', 1)
                                    ->first();
                                collect(collect($stock)['items'])->each(function ($items, $iKey) use ($storageLocation) {
                                    $item = Item::where('item_name', 'like', '%' . $items['Item Name'] . '%')->first();
                                    if ($item) {
                                        $uom_id = $item->lower_unit_uom_id;
                                        $item_id = $item->id;

                                        $storageLocationDetails = StoragelocationDetail::where('item_id', $item_id)
                                            ->where('item_uom_id', $uom_id)
                                            ->where('storage_location_id', $storageLocation->id)
                                            ->first();

                                        if ($storageLocationDetails) {
                                            $storageLocationDetails->qty = $items['Qty'];
                                            $storageLocationDetails->save();
                                        } else {
                                            $storageLocationDetail = new StoragelocationDetail();
                                            $storageLocationDetail->storage_location_id = $storageLocation->id;
                                            $storageLocationDetail->item_id = $item_id;
                                            $storageLocationDetail->item_uom_id = $uom_id;
                                            $storageLocationDetail->qty = $items['Qty'];
                                            $storageLocationDetail->status = 1;
                                            $storageLocationDetail->save();
                                        }
                                    }
                                });
                            }
                        });
                    }
                }
            }
        }
    }

    private function savePricing($data)
    {
        $data = json_decode($data, true);
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['Price'])) {

                        collect($data['response'][0]['Price'])->each(function ($pricing, $key) {

                            if ($pricing['Pricelist Name'] == "SP1") {
                                foreach ($pricing['Product'] as $pricing) {
                                    if ($pricing) {
                                        $uom = explode('[', $pricing['uom']);

                                        if (count($uom) > 1) {
                                            $item_uom = ItemUom::where('name', 'like', '%' . $uom[0] . '%')->first();
                                        } else {
                                            $item_uom = ItemUom::where('name', 'like', '%' . $pricing['uom'] . '%')->first();
                                        }

                                        $itemOld = Item::where('item_name', 'like', '%' . $pricing['Product'] . '%')
                                            ->first();

                                        $item = Item::where('item_name', 'like', '%' . $pricing['Product'] . '%')
                                            //->where('lower_unit_uom_id', $item_uom->id)
                                            ->first();

                                        if ($item) {
                                            $item->lower_unit_item_price = $pricing['Price'];
                                            $item->save();
                                        } else {
                                            $itemMainPrice = ItemMainPrice::where('item_id', $itemOld->id)
                                                ->where('item_id', $item_uom->id)
                                                ->first();

                                            if ($itemMainPrice) {
                                                $itemMainPrice->item_price = $pricing['Price'];
                                                $itemMainPrice->save();
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ((count($pricing['customer'])) && count($pricing['Product'])) {
                                    $combination_customer_id = CombinationMaster::where('name', 'like', 'Customer')->first();
                                    $combination_material_id = CombinationMaster::where('name', 'like', 'Material')->first();

                                    $combination_plan_key = CombinationPlanKey::where('combination_key', "%Customer/Item%")->first();
                                    if (!is_object($combination_plan_key)) {
                                        $combination_plan_key = new CombinationPlanKey();
                                    }
                                    // update and create
                                    $combination_plan_key->organisation_id = 1;
                                    $combination_plan_key->combination_key_name = 'Customer to Item';
                                    $combination_plan_key->combination_key = 'Customer/Item';
                                    $combination_plan_key->combination_key_code = $combination_customer_id->id . '/' . $combination_material_id->id;
                                    $combination_plan_key->status = 1;
                                    $combination_plan_key->save();


                                    $pricingDisco = PriceDiscoPromoPlan::updateOrCreate(
                                        ['combination_plan_key_id' => $combination_plan_key->id],
                                        [
                                            'organisation_id' => 1,
                                            'combination_plan_key_id' => $combination_plan_key->id,
                                            'use_for' => 'Pricing',
                                            'name' => $pricing['Pricelist Name'],
                                            'start_date' => Carbon::now()->format('Y-m-d'),
                                            'end_date' => '2021-12-31',
                                            'combination_key_value' => $combination_plan_key->combination_key,
                                            'type' => 1,
                                            'priority_sequence' => 1,
                                            'status' => 1,
                                            'discount_main_type' => 0,
                                        ]
                                    );
                                    /* $pricingDisco = new PriceDiscoPromoPlan();
                                     $pricingDisco->organisation_id = 1;
                                     $pricingDisco->combination_plan_key_id = $combination_plan_key->id;
                                     $pricingDisco->use_for = 'Pricing';
                                     $pricingDisco->name = $pricing['Pricelist Name'];
                                     $pricingDisco->start_date = Carbon::now()->format('Y-m-d');
                                     $pricingDisco->end_date = '2021-12-31';
                                     $pricingDisco->combination_key_value = $combination_plan_key->combination_key;
                                     $pricingDisco->type = 1;
                                     $pricingDisco->priority_sequence = 1;
                                     $pricingDisco->status = 1;
                                     $pricingDisco->discount_main_type = 0;
                                     $pricingDisco->save();*/

                                    foreach ($pricing['Product'] as $product) {
                                        if ($product['uom']) {
                                            $uom = explode("[", $product['uom']);
                                            if (!count($uom)) {
                                                $uom_name = $uom;
                                            } else {
                                                $uom_name = $uom[0];
                                            }

                                            $itemName = Item::where('item_name', 'like', '%' . $product['Product'] . '%')->first();
                                            $item_uom = ItemUom::where('name', 'like', '%' . $uom_name . '%')->first();

                                            if ($itemName && $item_uom) {
                                                $pdpItem = new PDPItem();
                                                $pdpItem->price_disco_promo_plan_id = ($pricingDisco) ? $pricingDisco->id : 0;
                                                $pdpItem->item_id = (is_object($itemName)) ? $itemName->id : 0;
                                                $pdpItem->item_uom_id = $item_uom->id;
                                                $pdpItem->price = $product['Price'];
                                                $pdpItem->save();
                                            }
                                        }
                                    }

                                    foreach ($pricing['customer'] as $customer) {
                                        $user = User::where('firstname', 'like', '%' . $customer['name'] . '%')->first();
                                        $customerInfo = CustomerInfo::where('user_id', $user->id)->first();
                                        if ($customerInfo) {
                                            $pdpCustomer = new PDPCustomer();
                                            $pdpCustomer->price_disco_promo_plan_id = ($pricingDisco) ? $pricingDisco->id : 0;
                                            $pdpCustomer->customer_id = $customerInfo->id;
                                            $pdpCustomer->save();
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            }
        }
    }

    private function saveInvoice($data)
    {
        $data = json_decode($data, true);
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['invoices'])) {
                        collect($data['response'][0]['invoices'])->each(function ($item, $key) {

                            $items = $item['items'];
                            $users = $item['users'];

                            $customer = '';
                            $paymentId = 0;
                            $saleManId = 0;
                            $lobId = 0;

                            $route = Route::where('route_name', 'like', '%' . $item['route_id'] . '%')
                                ->first();

                            if ($item['payment_term_id']) {
                                $paymentId = PaymentTerm::where('name', $item['payment_term_id'])->pluck('id');
                            }

                            if ($item['route_division']) {
                                $lobId = Lob::where('name', $item['route_division'])->pluck('id');
                            }

                            if ($item['salesman_id']) {
                                $saleManId = SalesmanInfo::where('salesman_code', $item['route_code'])->pluck('id');
                            }

                            $customer = CustomerInfo::where('customer_code', $item['users']['customer_info']['customer_code'])->first();

                            $totalQty = 0;

                            foreach ($items as $itemQty) {
                                $totalQty += $itemQty['item_qty'];
                            }
                            if ($customer) {
                                $invoice = new Invoice();
                                $invoice->organisation_id = 1;
                                $invoice->customer_id = ($customer) ? $customer->user_id : 0;
                                $invoice->depot_id = 0;
                                $invoice->order_id = 0;
                                $invoice->order_type_id = 2;
                                $invoice->delivery_id = 0;
                                $invoice->salesman_id = $saleManId;
                                $invoice->route_id = $route['id'];
                                $invoice->trip_id = 0;
                                $invoice->invoice_type = 1;
                                $invoice->invoice_number = (!$item['invoice_number']) ? 0 : $item['invoice_number'];
                                $invoice->invoice_date = $item['invoice_date'];
                                $invoice->invoice_due_date = $item['invoice_due_date'];
                                $invoice->payment_term_id = $paymentId;
                                $invoice->total_qty = $totalQty;
                                $invoice->total_gross = $item['subtotal'];
                                $invoice->total_discount_amount = $item['discount_amount'];
                                $invoice->total_net = number_format($item['subtotal'] - $item['discount_amount']);
                                $invoice->total_vat = $item['total_vat'];
                                $invoice->total_excise = 0;
                                $invoice->grand_total = $item['total'];
                                $invoice->pending_credit = $item['amount_due'];;
                                $invoice->odoo_id = $item['id'];;
                                $invoice->current_stage = 'Approved';
                                $invoice->current_stage_comment = null;
                                $invoice->approval_status = 'Created';
                                $invoice->payment_received = 0;
                                $invoice->is_exchange = 0;
                                $invoice->exchange_number = 0;
                                $invoice->source = 1;
                                $invoice->status = 1;
                                $invoice->is_premium_invoice = 0;
                                $invoice->lob_id = $lobId;
                                $invoice->customer_lpo = ($item['customer_LPO'] == false) ? 0 : $item['customer_LPO'];
                                $invoice->created_at = $item['created_at'];
                                $invoice->save();

                                foreach ($items as $item) {

                                    $contains = Str::contains($item['item_uom_id'], '[');
                                    if ($contains) {
                                        $itemUomData = explode("[", $item['item_uom_id']);
                                        $itemUom = ItemUom::where('name', 'like', '%' . $itemUomData[0] . '%')
                                            ->first();
                                    } else {
                                        $itemUom = ItemUom::where('name', 'like', '%' . $item['item_uom_id'] . '%')
                                            ->first();
                                    }
                                    $itemName = Item::where('item_code', 'like', '%' . $item['item_code'] . '%')
                                        ->first();

                                    $invoice_detail = new InvoiceDetail();
                                    $invoice_detail->invoice_id = $invoice->id;
                                    $invoice_detail->item_id = ($itemName) ? $itemName->id : 0;
                                    $invoice_detail->item_uom_id = ($itemUom) ? $itemUom->id : 0;
                                    $invoice_detail->discount_id = 0;
                                    $invoice_detail->is_free = 0;
                                    $invoice_detail->is_item_poi = 0;
                                    $invoice_detail->promotion_id = 0;
                                    $invoice_detail->item_qty = $item['item_qty'];
                                    $invoice_detail->item_price = $item['item_price'];
                                    $invoice_detail->item_gross = $item['item_qty'] * $item['item_price'];
                                    $invoice_detail->item_discount_amount = $item['item_discount_amount'];
                                    $invoice_detail->item_net = $item['amount'];
                                    $invoice_detail->item_vat = $item['item_vat'];
                                    $invoice_detail->item_excise = 0;
                                    $invoice_detail->item_grand_total = $item['total'];
                                    $invoice_detail->batch_number = 0;
                                    $invoice_detail->created_at = $item['created_at'];
                                    $invoice_detail->save();
                                }
                            }
                        });
                    }
                }
            }
        }
    }

    private function saveItem($data)
    {
        $data = json_decode($data, true);
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['Item'])) {
                        collect($data['response'][0]['Item'])->each(function ($item, $key) {
                            $Division = $item['Division'];
                            $item_name = $item['Item Name'];
                            $Purchase_uom = $item['Purchase uom'];
                            $stock_keeping_unit = $item['Stock Keeping Unit'];
                            $brand_name = $item['Brand'];
                            $Sale_uom = $item['Sale uom'];
                            $Item_Code = $item['Item Code'];
                            $Item_Category = $item['Item Category'];
                            $Item_Barcode = $item['Item Barcode'];

                            \DB::beginTransaction();
                            try {
                                $category = ItemMajorCategory::where('name', 'like', '%' . $Item_Category . '%')
                                    ->where('organisation_id', 1)
                                    ->first();

                                if (!is_object($category)) {
                                    $category = new ItemMajorCategory;
                                    $category->organisation_id = 1;
                                    $category->name = $Item_Category;
                                    $category->status = 1;
                                    $category->save();
                                }

                                $brand = Brand::where('brand_name', 'like', '%' . $brand_name . '%')
                                    ->where('organisation_id', 1)
                                    ->first();

                                if (!is_object($brand)) {
                                    $nellara_brand = Brand::where('brand_name', 'like', '% nellara %')
                                        ->where('organisation_id', 1)
                                        ->first();

                                    if (!is_object($nellara_brand)) {
                                        $brand = new Brand;
                                        $brand->organisation_id = 1;
                                        if ($brand_name) {
                                            $bName = $brand_name;
                                        } else {
                                            $bName = "nellara";
                                        }
                                        $brand->brand_name = $bName;
                                        $brand->status = 1;
                                        $brand->save();
                                    } else {
                                        $brand = $nellara_brand;
                                    }
                                }

                                $lob = null;

                                $base_uom = ItemUom::where('name', 'like', '%' . $Sale_uom . '%')
                                    ->where('organisation_id', 1)
                                    ->first();

                                if (!is_object($base_uom)) {
                                    $base_uom = new ItemUom;
                                    $base_uom->organisation_id = 1;
                                    $base_uom->name = $Sale_uom;
                                    $base_uom->code = $Item_Code;
                                    $base_uom->status = 1;
                                    $base_uom->save();
                                }

                                $item = Item::where('organisation_id', 1)
                                    ->where('item_code', 'like', '%' . $Item_Code . '%')
                                    ->first();

                                if (!is_object($item)) {
                                    $item = new Item;
                                }

                                $item->item_major_category_id = (!empty($category)) ? $category->id : null;
                                $item->organisation_id = 1;
                                $item->item_group_id = null;
                                $item->brand_id = (is_object($brand)) ? $brand->id : null;
                                $item->is_product_catalog = 0;
                                $item->is_promotional = 0;
                                $item->item_code = $Item_Code;
                                $item->erp_code = $Item_Code;
                                $item->item_name = $item_name;
                                $item->item_description = null;
                                $item->item_barcode = (!empty($Item_Barcode)) ? $Item_Barcode : null;
                                $item->item_weight = null;
                                $item->item_shelf_life = null;
                                $item->volume = null;
                                $item->lower_unit_uom_id = (!empty($base_uom)) ? $base_uom->id : null;
                                $item->is_tax_apply = 0;
                                $item->lower_unit_item_upc = 1;
                                $item->lower_unit_item_price = 0;
                                $item->lower_unit_purchase_order_price = 0;
                                $item->item_vat_percentage = 0;
                                $item->stock_keeping_unit = 1;
                                $item->item_excise = 0;
                                $item->new_lunch = 0;
                                $item->start_date = null;
                                $item->end_date = null;
                                $item->supervisor_category_id = null;
                                $item->current_stage = "Approved";
                                $item->current_stage_comment = null;
                                $item->status = 1;
                                $item->lob_id = (is_object($lob)) ? $lob->id : null;
                                $item->save();

                                // PSC [10 PSC]
                                $exData = explode(' ', sr($Purchase_uom));
                                // Save Item Lob
                                // if (is_object($lob)) {
                                //     $item_lob = new ItemLob;
                                //     $item_lob->item_id = $item->id;
                                //     $item_lob->lob_id = $lob->id;
                                //     $item_lob->save();
                                // }
                                //save Secondry Uom
                                if (is_array($exData) && count($exData) > 1) {
                                    if (end($exData) == $Sale_uom) {
                                        $sec_uom = ItemUom::where('name', 'like', '%' . $exData[0] . '%')
                                            ->where('organisation_id', 1)
                                            ->first();

                                        if (!is_object($sec_uom)) {
                                            $sec_uom = new ItemUom;
                                            $sec_uom->organisation_id = 1;
                                            $sec_uom->name = $exData[0];
                                            $sec_uom->code = $Item_Code;
                                            $sec_uom->status = 1;
                                            $sec_uom->save();
                                        }

                                        $item_main_price = ItemMainPrice::where('item_id', $item->id)
                                            ->where('item_uom_id', $sec_uom->id)
                                            ->first();

                                        if (!is_object($item_main_price)) {
                                            $item_main_price = new ItemMainPrice;
                                        }

                                        $item_main_price->item_id = $item->id;
                                        $item_main_price->item_upc = $exData[1];
                                        $item_main_price->item_uom_id = $sec_uom->id;
                                        $item_main_price->item_price = 0;
                                        $item_main_price->purchase_order_price = 0;
                                        $item_main_price->stock_keeping_unit = 0;
                                        $item_main_price->status = 1;
                                        $item_main_price->save();
                                    }
                                }

                                if (count($Division)) {

                                    foreach ($Division as $div) {
                                        $lob = Lob::where('name', 'like', '%' . $div['Division'] . '%')
                                            ->where('organisation_id', 1)
                                            ->first();

                                        if (!is_object($lob)) {
                                            $lob = new Lob;
                                            $lob->organisation_id = 1;
                                            $lob->user_id = 2;
                                            $lob->name = $div['Division'];
                                            $lob->save();
                                        }

                                        $item_lob = ItemLob::where('item_id', $item->id)
                                            ->where('lob_id', $lob->id)
                                            ->first();

                                        if (!is_object($item_lob)) {
                                            $item_lob = new ItemLob();
                                            $item_lob->item_id = $item->id;
                                            $item_lob->lob_id = $lob->id;
                                            $item_lob->save();
                                        }
                                    }
                                }

                                \DB::commit();
                            } catch (\Exception $exception) {
                                \DB::rollback();
                                return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            } catch (\Throwable $exception) {
                                \DB::rollback();
                                return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        });
                    }
                }
            }
        }
    }

    private function saveRoute($data)
    {
        $data = json_decode($data, true);
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['Route'])) {
                        collect($data['response'][0]['Route'])->each(function ($route, $key) {

                            $region = $route['Region'];
                            $code = $route['Code'];
                            $name = $route['Route Name'];

                            $region = Region::where('region_name', 'like', '%' . $region . '%')
                                ->where('organisation_id', 1)
                                ->first();

                            if (!is_object($region)) {
                                $region = new Region();
                                $region->organisation_id = 1;
                                $region->country_id = 1;
                                $region->region_code = $code;
                                $region->region_name = $route['Region'];
                                $region->region_status = 1;
                                $region->save();
                            }

                            if (isset($route['Code']) && isset($route['Route Name'])) {

                                $route = Route::where('route_name', 'like', '%' . $name . '%')->first();
                                if (!is_object($route)) {
                                    $route = new Route;
                                }
                                $route->organisation_id = 1;
                                $route->area_id = null;
                                $route->depot_id = null;
                                $route->route_code = $code;
                                $route->route_name = $name;
                                $route->status = 1;
                                $route->save();
                            }
                        });
                        return "done";
                    }
                    echo "error";
                }
                echo "error1";
            }
            echo "error2";
        }
    }

    private function saveSalesman($data)
    {
        $data = json_decode($data, true);
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['Salesman'])) {
                        collect($data['response'][0]['Salesman'])->each(function ($salesman, $key) {
                            $route_name = $salesman['Route Name'];
                            $Region = $salesman['Region'];
                            $Division = $salesman['Division'];
                            $Code = $salesman['Code'];
                            $Salesman_Mobile = $salesman['Salesman Mobile'];
                            $Helper = $salesman['Helper'];
                            $Salesman_Name = $salesman['Salesman Name'];
                            $Salesman_Email = $salesman['Salesman Email'];

                            $Receipt_No = $salesman['Receipt No'];
                            $Vehicle_No = $salesman['Vehicle No.'];
                            $Invoice_No = $salesman['Invoice No'];
                            $GRV_No = $salesman['GRV No'];

                            $route = Route::where('route_name', 'like', '%' . $route_name . '%')
                                ->where('organisation_id', 1)
                                ->first();

                            if (!is_object($route)) {
                                $route = new Route;
                                $route->organisation_id = 1;
                                $route->area_id = null;
                                $route->depot_id = null;
                                $route->route_code = $Code;
                                $route->route_name = $route_name;
                                $route->status = 1;
                                $route->save();
                            }

                            $region = Region::where('region_name', 'like', '%' . $Region . '%')
                                ->where('organisation_id', 1)
                                ->first();

                            \DB::beginTransaction();
                            try {

                                $salesman_infos = SalesmanInfo::wheresalesman_code($Code)
                                    ->where('organisation_id', 1)
                                    ->first();

                                if (!is_object($salesman_infos)) {
                                    $user = new User;
                                    $salesman_infos = new SalesmanInfo;
                                } else {
                                    $user = $salesman_infos->user;
                                }

                                $user->usertype = 3;
                                $user->parent_id = 960;
                                $user->organisation_id = 1;
                                $user->firstname = $Salesman_Name;
                                $user->lastname = " ";
                                $user->email = substr($Salesman_Email, 0, 4) . '_' . $Code . '@nerrala.com';
                                $user->password = Hash::make("123456");
                                $user->mobile = (!empty($Salesman_Mobile)) ? ps($Salesman_Mobile) : null;
                                $user->country_id = 1;
                                $user->api_token = \Str::random(35);
                                $user->is_approved_by_admin = 1;
                                $user->role_id = 4;
                                $user->status = 1;
                                $user->save();

                                $salesman_infos->user_id = $user->id;
                                $salesman_infos->organisation_id = 1;
                                $salesman_infos->salesman_type_id = 1;
                                $salesman_infos->salesman_code = $Code;
                                $salesman_infos->salesman_helper_id = null;
                                $salesman_infos->region_id = (!empty($region)) ? $region->id : null;
                                $salesman_infos->route_id = (!empty($route)) ? $route->id : null;
                                $salesman_infos->salesman_role_id = 2;
                                $salesman_infos->salesman_supervisor = null;

                                if (empty($Division)) {
                                    $salesman_infos->is_lob = 0;
                                } else {
                                    $salesman_infos->is_lob = 1;
                                }

                                $salesman_infos->current_stage = "Approved";
                                $salesman_infos->status = 1;
                                $salesman_infos->category_id = 1;
                                $salesman_infos->save();

                                if ($Division) {
                                    $lob = Lob::where('name', 'like', '%' . $Division . '%')
                                        ->where('organisation_id', 1)
                                        ->where('user_id', 2)
                                        ->first();

                                    if (!is_object($lob)) {
                                        $lob = new Lob;
                                        $lob->organisation_id = 1;
                                        $lob->user_id = 2;
                                        $lob->name = $Division;
                                        $lob->save();
                                    }

                                    $salesman_lob = SalesmanLob::where('salesman_info_id', $salesman_infos->id)
                                        ->where('lob_id', $lob->id)
                                        ->first();

                                    if (!is_object($salesman_lob)) {
                                        $salesman_lob = new SalesmanLob;
                                    }

                                    $salesman_lob->salesman_info_id = $salesman_infos->id;
                                    $salesman_lob->organisation_id = 1;
                                    $salesman_lob->lob_id = $lob->id;
                                    $salesman_lob->save();
                                }

                                $salesman_number_range = new SalesmanNumberRange;
                                $salesman_number_range->salesman_id = $salesman_infos->id;
                                $salesman_number_range->customer_from = $Code . "C100000";
                                $salesman_number_range->customer_to = $Code . "C999999";
                                $salesman_number_range->order_from = $Code . "O100000";
                                $salesman_number_range->order_to = $Code . "O999999";
                                $salesman_number_range->invoice_from = $Code . 'I' . setSalesmanNumberRange($Invoice_No);
                                $salesman_number_range->invoice_to = $Code . "I999999";
                                $salesman_number_range->collection_from = $Code . "R" . setSalesmanNumberRange($Receipt_No);
                                $salesman_number_range->collection_to = $Code . "R999999";
                                $salesman_number_range->credit_note_from = $Code . "G" . setSalesmanNumberRange($GRV_No);
                                $salesman_number_range->credit_note_to = $Code . "G999999";
                                $salesman_number_range->unload_from = $Code . "U100000";
                                $salesman_number_range->unload_to = $Code . "U999999";
                                $salesman_number_range->save();

                                \DB::commit();
                            } catch (\Exception $exception) {
                                \DB::rollback();
                                return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            } catch (\Throwable $exception) {
                                \DB::rollback();
                                return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        });
                    }
                }
            }
        }
    }

    private function saveCustomer($data)
    {
        if (isset($data['response'])) {
            if (isset($data['response'][0])) {
                if ($data['response'][0]['state'] == "success") {
                    if (count($data['response'][0]['partners'])) {
                        collect($data['response'][0]['partners'])->each(function ($customer, $key) {
                            $Mobile = $customer['Mobile'];
                            $Region = $customer['Region'];
                            $Customer_Group = $customer['Customer Group'];
                            $State = $customer['State'];
                            $Customer_Name = $customer['Customer Name'];
                            $Email = $customer['Email'];
                            $Division = $customer['Division'];
                            $Code = $customer['Customer Code'];
                            $Customer_Category = $customer['Customer Category'];
                            $Customer_Type = $customer['Customer Type'];
                            $Phone = $customer['Phone'];
                            $Address = $customer['Address'];
                            $route_array = $customer['Route'];

                            // if ($key > 2500) {
                            if ($Code) {

                                \DB::beginTransaction();
                                try {
                                    if (empty($Email)) {
                                        $email = Str::substr(str_replace(' ', '_', $Customer_Name), 0, 4) . '_' . $Code . '@nerrala.com';
                                    } else {
                                        $email = $Email;
                                    }

                                    // $route = Route::where('route_name', 'like', '%' . $route_name . '%')->first();

                                    $region = Region::where('region_name', 'like', '%' . $Region . '%')
                                        ->where('organisation_id', 1)
                                        ->first();

                                    if (!is_object($region)) {
                                        $region = new Region;
                                        $region->organisation_id = 1;
                                        $region->region_code = $Code;
                                        $region->region_name = $Region;
                                        $region->country_id = 1;
                                        $region->region_status = 1;
                                        $region->save();
                                    }

                                    $sales_organisation = SalesOrganisation::where('name', "nellara")
                                        ->where('organisation_id', 1)
                                        ->first();

                                    if (!is_object($sales_organisation)) {
                                        $sales_organisation = new SalesOrganisation;
                                        $sales_organisation->organisation_id = 1;
                                        $sales_organisation->name = "nellara";
                                        $sales_organisation->status = 1;
                                        $sales_organisation->save();
                                    }

                                    $channel = Channel::where('name', "retail")
                                        ->where('organisation_id', 1)
                                        ->first();

                                    if (!is_object($channel)) {
                                        $channel = new Channel;
                                        $channel->organisation_id = 1;
                                        $channel->name = "retail";
                                        $channel->status = 1;
                                        $channel->save();
                                    }

                                    $customer_category = CustomerCategory::where('customer_category_name', "supermarket")
                                        ->where('organisation_id', 1)
                                        ->first();

                                    if (!is_object($customer_category)) {
                                        $customer_category = new CustomerCategory;
                                        $customer_category->organisation_id = 1;
                                        $customer_category->customer_category_code = $Code;
                                        $customer_category->customer_category_name = "supermarket";
                                        $customer_category->status = 1;
                                        $customer_category->save();
                                    }

                                    $customer_group = null;

                                    if ($Customer_Group != "") {
                                        $customerGroup = CustomerGroup::where('organisation_id', 1)
                                            ->where('group_name', 'like', '%' . $customer_category . '%')
                                            ->first();

                                        if (!is_object($customerGroup)) {
                                            $customer_group = new CustomerGroup;
                                            $customer_group->organisation_id = 1;
                                            $customer_group->group_code = $Code;
                                            $customer_group->group_name = $Customer_Group;
                                            $customer_group->status = 1;
                                            $customer_group->save();
                                        }
                                    }

                                    // check if customer code is there then update the code
                                    $customer_info = CustomerInfo::where('customer_code', 'like', "%" . $Code . "%")
                                        ->where('organisation_id', 1)
                                        ->first();

                                    // if not code then create new customer
                                    if (!is_object($customer_info)) {
                                        $user = new User;
                                        $customer_info = new CustomerInfo;
                                    } else {
                                        $user = $customer_info->user;
                                    }

                                    $user->usertype = 2;
                                    $user->organisation_id = 1;
                                    $user->parent_id = 2;
                                    $user->firstname = $Customer_Name;
                                    $user->lastname = " ";
                                    $user->email = $email;
                                    $user->password = Hash::make("123456");
                                    $user->mobile = (!empty($Phone)) ? ps($Phone) : null;
                                    $user->country_id = 1;
                                    $user->api_token = \Str::random(35);
                                    $user->is_approved_by_admin = 1;
                                    $user->role_id = 2;
                                    $user->status = 1;
                                    $user->save();

                                    $customer_infos = new CustomerInfo;
                                    $customer_infos->user_id = $user->id;
                                    $customer_infos->customer_code = $Code;
                                    $customer_infos->erp_code = $Code;
                                    $customer_infos->organisation_id = 1;
                                    $customer_infos->customer_address_1 = (!empty($Address)) ? $Address : "Dubai";
                                    $customer_infos->customer_address_2 = null;
                                    $customer_infos->customer_city = "Dubai";
                                    $customer_infos->customer_state = (!empty($State)) ? $State : "Dubai";
                                    $customer_infos->customer_zipcode = null;
                                    $customer_infos->customer_phone = (!empty($Phone)) ? ps($Phone) : null;
                                    $customer_infos->customer_address_1_lat = null;
                                    $customer_infos->customer_address_1_lang = null;
                                    $customer_infos->customer_address_2_lat = null;
                                    $customer_infos->customer_address_2_lang = null;
                                    $customer_infos->payment_term_id = null;
                                    $customer_infos->current_stage = "Approved";
                                    $customer_infos->current_stage_comment = null;
                                    $customer_infos->status = 1;
                                    $customer_infos->is_lob = (count($Division)) ? 1 : 0;
                                    $customer_infos->expired_date = null;
                                    $customer_infos->source = 3;
                                    $customer_infos->trn_no = $TRN_No ?? null;
                                    $customer_infos->customer_group_id = (is_object($customer_group)) ? $customer_group->id : null;
                                    if (count($Division) == 0) {
                                        $customer_infos->amount = 0;
                                        $customer_infos->balance = 0;
                                        $customer_infos->credit_limit = 0;
                                        $customer_infos->credit_days = 0;
                                        $customer_infos->due_on = null;
                                        $customer_infos->region_id = (!empty($region)) ? $region->id : null;
                                        $customer_infos->route_id = (!empty($route)) ? $route->id : null;
                                        $customer_infos->sales_organisation_id = 1;
                                        $customer_infos->channel_id = 1;
                                        $customer_infos->customer_category_id = 1;
                                        $customer_infos->customer_type_id = ($Customer_Type == "individual") ? 2 : 1;
                                        $customer_infos->ship_to_party = $Code;
                                        $customer_infos->sold_to_party = $Code;
                                        $customer_infos->payer = $Code;
                                        $customer_infos->bill_to_payer = $Code;
                                    }

                                    $customer_infos->save();

                                    if ($customer_infos->is_lob == 1) {
                                        if (is_array($Division)) {
                                            $customer_infos = CustomerInfo::find($customer_infos->id);
                                            $customer_infos->ship_to_party = null;
                                            $customer_infos->sold_to_party = null;
                                            $customer_infos->payer = null;
                                            $customer_infos->bill_to_payer = null;
                                            $customer_infos->save();
                                            foreach ($Division as $customer_lob_value) {

                                                $pt = $customer_lob_value['Payment Term'];
                                                $cdiv = $customer_lob_value['Division'];
                                                $cCredit = $customer_lob_value['Credit'];
                                                $cCreditL = $customer_lob_value['Credit Limit'];

                                                // preg_match_all('!\d+!', $pt, $matches);

                                                if ($pt != "Immediate Payment") {
                                                    $ex_payment_terms = explode(' ', $pt);
                                                } else {
                                                    $ex_payment_terms = "Immediate Payment";
                                                }

                                                $payment_term = PaymentTerm::where('name', 'like', '%' . $pt . '%')
                                                    ->where('organisation_id', 1)
                                                    ->first();

                                                if (!is_object($payment_term)) {
                                                    $payment_term = new PaymentTerm;
                                                    $payment_term->organisation_id = 1;
                                                    $payment_term->name = $pt;
                                                    $payment_term->number_of_days = (isset($ex_payment_terms[0]) ? $ex_payment_terms[0] : 0);
                                                    $payment_term->status = 1;
                                                    $payment_term->save();
                                                }

                                                $lob = Lob::where('name', 'like', '%' . $cdiv . '%')
                                                    ->where('organisation_id', 1)
                                                    ->first();

                                                if (!is_object($lob)) {
                                                    $lob = new Lob;
                                                    $lob->organisation_id = 1;
                                                    $lob->user_id = $customer_infos->user_id;
                                                    $lob->name = $cdiv;
                                                    $lob->save();
                                                }

                                                $route = null;
                                                foreach ($route_array as $key => $route) {
                                                    if ($route['Divison'] == $cdiv) {
                                                        $route_name = $route['Route'];
                                                        $route = Route::where('route_name', 'like', '%' . $route_name . '%')
                                                            ->where('organisation_id', 1)
                                                            ->first();

                                                        if (is_object($route)) {
                                                            break;
                                                        }
                                                    }
                                                }

                                                $this->saveCustomerLob(
                                                    $customer_infos,
                                                    $region,
                                                    $route,
                                                    $payment_term,
                                                    $lob,
                                                    $cCreditL,
                                                    $Code,
                                                    $ex_payment_terms
                                                );
                                            }
                                        }
                                    }

                                    \DB::commit();
                                } catch (\Exception $exception) {
                                    \DB::rollback();
                                    return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                } catch (\Throwable $exception) {
                                    \DB::rollback();
                                    return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                            }
                            // }
                        });
                    }
                }
            }
        }
    }

    private function saveCustomerLob(
        $customer_infos,
        $region,
        $route,
        $payment_term,
        $lob,
        $cCreditL,
        $Code,
        $ex_payment_terms
    ) {
        $customer_lob = new CustomerLob;
        $customer_lob->customer_info_id = $customer_infos->id;
        $customer_lob->region_id = (is_object($region) ? $region->id : null);
        $customer_lob->organisation_id = 1;
        $customer_lob->route_id = (is_object($route) ? $route->id : null);
        $customer_lob->payment_term_id = (is_object($payment_term) ? $payment_term->id : null);
        $customer_lob->lob_id = (is_object($lob) ? $lob->id : null);
        $customer_lob->amount = $cCreditL;
        // $customer_lob->customer_group_id            = $customer_group;
        $customer_lob->sales_organisation_id = 1;
        $customer_lob->channel_id = 1;
        $customer_lob->customer_category_id = 1;
        $customer_lob->balance = $cCreditL;
        $customer_lob->credit_limit = $cCreditL;
        $customer_lob->credit_days = (isset($ex_payment_terms[0])) ? $ex_payment_terms[0] : 0;

        if ($ex_payment_terms == "Immediate Payment") {
            $customer_lob->customer_type_id = 1;
            $customer_lob->due_on = 1;
        } else {
            if (count($ex_payment_terms) > 1) {
                $customer_lob->customer_type_id = 2;
                $customer_lob->due_on = 1;
            }
        }

        $customer_lob->ship_to_party = $Code;
        $customer_lob->sold_to_party = $Code;
        $customer_lob->payer = $Code;
        $customer_lob->bill_to_payer = $Code;
        $customer_lob->save();
    }

    private function getData($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            ""
        );
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        // In real life you should use something like:
        // curl_setopt($ch, CURLOPT_POSTFIELDS,
        // http_build_query(array('postvar1' => 'value1')));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (isset($error_msg)) {
            return $error_msg;
        }

        $server_output = curl_exec($ch);
        curl_close($ch);
        return $server_output;
    }
}
