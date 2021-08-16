<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\TransactionDetail;
use DB;
use App\Model\SalesmanUnload;
use App\Model\SalesmanUnloadDetail;
use App\Model\SalesmanTripInfos;
use App\Model\Item;
use App\Model\Warehouse;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;
use App\Model\Route;
use App\Model\Goodreceiptnote;
use App\Model\Goodreceiptnotedetail;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\ItemMainPrice;
use App\Model\SalesmanNumberRange;
use App\User;
use Ixudra\Curl\Facades\Curl;

class SalesmanUnloadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $UnloadHeader_query = SalesmanUnload::with(
            'salesman:id,firstname,lastname',
            'salesmanUnloadDetail',
            'salesmanUnloadDetail.item:id,item_name',
            'salesmanUnloadDetail.itemUom:id,name'
        );

        if ($request->date) {
            $UnloadHeader_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->code) {
            $UnloadHeader_query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->load_type) {
            $UnloadHeader_query->where('load_type', 'like', '%' . $request->load_type . '%');
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $UnloadHeader_query->whereHas('salesman_infos.user', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $UnloadHeader_query->whereHas('salesman_infos.user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->salesman_code) {
            $salesman_code = $request->salesman_code;
            $UnloadHeader_query->whereHas('salesman_infos', function ($q) use ($salesman_code) {
                $q->where('salesman_code', 'like', $salesman_code);
            });
        }

        if ($request->route) {
            $route = $request->route;
            $UnloadHeader_query->whereHas('route', function ($q) use ($route) {
                $q->where('route_name', 'like', $route);
            });
        }

        $UnloadHeader = $UnloadHeader_query->orderBy('id', 'desc')
            ->get();

        $UnloadHeader_array = array();
        if (is_object($UnloadHeader)) {
            foreach ($UnloadHeader as $key => $UnloadHeader1) {
                $UnloadHeader_array[] = $UnloadHeader[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($UnloadHeader_array[$offset])) {
                    $data_array[] = $UnloadHeader_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($UnloadHeader_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($UnloadHeader_array);
        } else {
            $data_array = $UnloadHeader_array;
        }

        return prepareResult(true, $data_array, [], "Salesman Unload listing", $this->success, $pagination);
    }

    public function unloadlist(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $input = $request->json()->all();

        $validate = $this->validations($input, "unloadlist");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header", $this->unprocessableEntity);
        }
        //$route_id = $request->route_id;
        $unloadtype = $request->unload_type;
        $filter = $request->filter;
        if ($request->filter == 'all') {
        }

        $unloadheaders = SalesmanUnload::select('id', 'uuid', 'code', 'route_id', 'trip_id', 'transaction_date', 'unload_type', 'salesman_id', 'status', 'created_at', 'updated_at')
            ->with(
                'trip',
                'route:id,route_code,route_name',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code'
            )
            ->where('status', 0);
        //        if ($request->filter != 'all') {
        //            $unloadheaders->whereBetween('transaction_date', [$start_date, $end_date]);
        //        }
        $unloadheaders->orderBy('id', 'desc');
        $unloadheader = $unloadheaders->get();

        foreach ($unloadheader as $key => $trids) {
            // salesman_load_details is salesman_unload_details
            $unloadheader[$key]->salesman_unload_details = DB::table('salesman_unload_details')
                ->join('salesman_unloads', 'salesman_unloads.id', '=', 'salesman_unload_details.salesman_unload_id')
                ->join('items', 'items.id', '=', 'salesman_unload_details.item_id', 'left')
                ->join('item_uoms', 'item_uoms.id', '=', 'items.lower_unit_uom_id', 'left')
                ->join('trips', 'trips.id', '=', 'salesman_unloads.trip_id', 'left')
                ->join('users', 'trips.salesman_id', '=', 'users.id', 'left')
                ->join('routes', 'routes.id', '=', 'trips.route_id', 'left')
                ->join('salesman_infos', 'salesman_infos.id', '=', 'trips.salesman_id', 'left')
                ->select('salesman_unload_details.id', 'salesman_unload_details.uuid', 'salesman_unload_details.salesman_unload_id', 'salesman_unload_details.unload_type', 'trips.route_id as route_id', 'routes.route_code as route_code', 'routes.route_name as route_name', 'trips.salesman_id as salesman_id', 'salesman_infos.salesman_code as salesman_code', 'users.firstname as salesman_name', 'salesman_unloads.id as depo_code', 'salesman_unloads.transaction_date as load_date', 'salesman_unload_details.item_id', 'items.item_name', 'items.item_code', 'item_uoms.id as item_uom', 'item_uoms.name as item_uom_name', 'item_uoms.code as item_uom_code', 'salesman_unload_details.unload_qty as load_qty', 'salesman_unload_details.status as status', 'salesman_unload_details.created_at as created_at', 'salesman_unload_details.updated_at as updated_at', 'salesman_unload_details.deleted_at as deleted_at', 'salesman_unload_details.reason')
                ->where('salesman_unload_details.salesman_unload_id', $trids->id)
                ->get();

            foreach ($unloadheader[$key]->salesman_unload_details as $k => $salesunloaddetail) {
                $items = Item::where('id', $salesunloaddetail->item_id)
                    ->where('lower_unit_uom_id', $salesunloaddetail->item_uom)
                    ->first();
            }
        }

        // $data = array('UnloadData' => $unloadheader);
        $data = $unloadheader;

        return prepareResult(true, $data, [], "Unload header listing", $this->success);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            if ($request->unload_type != 2) {
                $unloadheader = new SalesmanUnload;
                $unloadheader->trip_id           = (!empty($request->trip_id)) ? $request->trip_id : null;
                $unloadheader->code              = $request->code;
                $unloadheader->unload_type       = (!empty($request->unload_type)) ? $request->unload_type : null;
                $unloadheader->route_id          = (!empty($request->route_id)) ? $request->route_id : null;
                $unloadheader->salesman_id       = (!empty($request->salesman_id)) ? $request->salesman_id : null;
                $unloadheader->transaction_date  = date('Y-m-d', strtotime($request->transaction_date));
                $unloadheader->source            = 1;
                $unloadheader->status            = 0;
                $unloadheader->save();

                $salesman_info = new SalesmanTripInfos;
                $salesman_info->trips_id       = $unloadheader->trip_id;
                $salesman_info->salesman_id    = $unloadheader->salesman_id;
                $salesman_info->status         = 4;
                $salesman_info->save();
            }

            // if (is_object($unloadheader) && $unloadheader->source == 1) {
            //     $user = User::find($request->user()->id);
            //     if (is_object($user)) {
            //         $salesmanInfo = $user->salesmanInfo;
            //         $smr = SalesmanNumberRange::where('salesman_id', $salesmanInfo->id)->first();
            //         $smr->unload_from = $request->code;
            //         $smr->save();
            //     }
            // }

            //--------------------
            if ($request->unload_type == '2') {
                $routelocation = Storagelocation::where('route_id', $request->route_id)
                    ->where('loc_type', 2)
                    ->first();

                $route = Route::find($request->route_id);

                $warehouse = Warehouse::where('depot_id', $route->depot_id)
                    ->whereNull('route_id')
                    ->first();

                $warehouselocation = Storagelocation::where('warehouse_id', $warehouse->id)
                    ->where('loc_type', 1)
                    ->whereNull('route_id')
                    ->first();

                $goodreceiptnote = new Goodreceiptnote;
                $goodreceiptnote->source_warehouse          = (!empty($routelocation)) ? $routelocation->id : null;
                $goodreceiptnote->destination_warehouse     = $warehouselocation->id;
                $goodreceiptnote->grn_number                =  $request->code;
                $goodreceiptnote->grn_date                  = date('Y-m-d', strtotime($request->transaction_date));
                $goodreceiptnote->save();
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    if ($request->unload_type == 2) {
                        $goodreceiptnotedetail = new Goodreceiptnotedetail;
                        $goodreceiptnotedetail->good_receipt_note_id      = $goodreceiptnote->id;
                        $goodreceiptnotedetail->item_id       = $item['item_id'];
                        $goodreceiptnotedetail->item_uom_id   = $item['item_uom'];
                        $goodreceiptnotedetail->qty           = $item['unload_qty'];
                        $goodreceiptnotedetail->reason        = (!empty($item['reason'])) ? $item['reason'] : null;
                        $goodreceiptnotedetail->save();
                    } elseif ($request->unload_type == 4) {

                        $transactionheaderde = TransactionDetail::with(
                            'Transaction',
                            'Transaction.trip',
                            'Transaction.trip.users'
                        )
                            ->where('item_id', $item['item_id'])
                            ->whereHas('Transaction.trip', function ($query) use ($request) {
                                return $query->where('id', '=', $request->trip_id);
                            })
                            ->first();

                        $reults = getItemDetails2($item['item_id'], $item['item_uom'], $item['unload_qty']);

                        $unloaddetail = new SalesmanUnloadDetail;
                        $unloaddetail->salesman_unload_id   = $unloadheader->id;
                        $unloaddetail->item_id              = $item['item_id'];
                        $unloaddetail->item_uom             = $item['item_uom'];
                        $unloaddetail->unload_qty           = $item['unload_qty'];
                        $unloaddetail->unload_date          = $item['unload_date'];
                        $unloaddetail->unload_type          = $item['unload_type'];
                        $unloaddetail->reason               = (!empty($item['reason'])) ? $item['reason'] : null;
                        $unloaddetail->save();

                        //Warehouse Start
                        if ($request->unload_type == 4 && $item['unload_type'] == 1) {
                            $routes = Route::find($request->route_id);
                            if (is_object($routes)) {
                                $depot_id = $routes->depot_id;

                                $Warehouse = Warehouse::where('depot_id', $depot_id)
                                    ->first();

                                if (is_object($Warehouse)) {
                                    $warehouselocation = Storagelocation::where('warehouse_id', $Warehouse->id)
                                        ->where('loc_type', '1')
                                        ->first();
                                    if (is_object($warehouselocation)) {
                                        $routelocation = Storagelocation::where('route_id', $request->route_id)
                                            ->where('loc_type', '1')
                                            ->first();
                                        if (is_object($routelocation)) {
                                            $routestoragelocation_id = $routelocation->id;
                                            $warehousestoragelocation_id = $warehouselocation->id;
                                            $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                                ->where('item_id', $item['item_id'])
                                                ->first();
                                            $warehouselocation_detail = StoragelocationDetail::where('storage_location_id', $warehousestoragelocation_id)
                                                ->where('item_id', $item['item_id'])
                                                ->first();

                                            if (is_object($warehouselocation_detail)) {
                                                //if (isset($$item['unload_qty']) && is_object($loaddetail_old))

                                                $warehouselocation_detail->qty = ($warehouselocation_detail->qty +  $reults['Qty']);
                                                $warehouselocation_detail->save();
                                            } else {
                                                $storagewarehousedetail = new StoragelocationDetail;
                                                $storagewarehousedetail->storage_location_id = $warehouselocation->id;
                                                $storagewarehousedetail->item_id      = $item['item_id'];
                                                $storagewarehousedetail->item_uom_id  =  $reults['UOM'];
                                                $storagewarehousedetail->qty          =  $reults['Qty'];
                                                $storagewarehousedetail->save();
                                            }
                                            if (is_object($routelocation_detail)) {
                                                //if (isset($$item['unload_qty']) && is_object($loaddetail_old))

                                                $routelocation_detail->qty = ($routelocation_detail->qty -  $reults['Qty']);
                                                $routelocation_detail->save();
                                            } else {


                                                $routestoragedetail = new StoragelocationDetail;
                                                $routestoragedetail->storage_location_id = $routelocation->id;
                                                $routestoragedetail->item_id      = $item['item_id'];
                                                $routestoragedetail->item_uom_id  =  $reults['UOM'];
                                                $routestoragedetail->qty          =  $reults['Qty'];
                                                $routestoragedetail->save();
                                            }
                                        } else {
                                            return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                        }
                                    } else {

                                        return prepareResult(false, [], ["error" => "Wherehouse Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                    }
                                } else {
                                    return prepareResult(false, [], ["error" => "Wherehouse not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                            }
                        }

                        if ($item['unload_type'] == 4) {
                            //Warehouse End
                            if (is_object($transactionheaderde)) {
                                $transactiondetail = TransactionDetail::where('id', $transactionheaderde->id)->first();
                                $transactiondetail->closing_qty = $reults['Qty'];
                                $transactiondetail->unload_qty = $reults['Qty'];
                                $transactiondetail->save();
                            }
                        }
                    } elseif ($request->unload_type == 5) {

                        $items = Item::where('id', $item['item_id'])
                            ->where('lower_unit_uom_id', $item['item_uom'])
                            ->first();

                        if (is_object($items)) {
                            $price = $items->lower_unit_item_price;
                            $itemprice =    $item['unload_qty'] * $price;
                            $vat =  $itemprice * 0.05;
                            $total = $itemprice + $vat;
                        } else {
                            $ItemMainPrice_result = ItemMainPrice::where('item_id', $item['item_id'])
                                ->where('item_uom_id', $item['item_uom'])
                                ->first();

                            $price = $ItemMainPrice_result->item_price;
                            $itemprice =    $item['unload_qty'] * $price;
                            $vat =  $itemprice * 0.05;
                            $total = $itemprice + $vat;
                        }

                        $invoice = Invoice::where('invoice_number', $request->code)
                            ->first();

                        if (is_object($invoice)) {
                            $invoiceId = $invoice->id;
                            $invoiceDetail = new InvoiceDetail;

                            $invoiceDetail->invoice_id    = $invoiceId;
                            $invoiceDetail->item_id       = $item['item_id'];
                            $invoiceDetail->item_uom_id   = $item['item_uom_id'];
                            $invoiceDetail->item_qty      = $item['unload_qty'];
                            $invoiceDetail->item_price    = $price;
                            $invoiceDetail->item_gross    = $itemprice;
                            $invoiceDetail->item_net      = $itemprice;
                            $invoiceDetail->item_vat      = $vat;
                            $invoiceDetail->item_grand_total = $total;
                            $invoiceDetail->save();
                        } else {
                            //-----------------------------------------Start unload details--------------

                            $unloaddetail = new SalesmanUnloadDetail;
                            $unloaddetail->salesman_unload_id   = $unloadheader->id;
                            $unloaddetail->item_id              = $item['item_id'];
                            $unloaddetail->item_uom             = $item['item_uom'];
                            $unloaddetail->unload_qty           = $item['unload_qty'];
                            $unloaddetail->unload_date          = $item['unload_date'];
                            $unloaddetail->unload_type          = $item['unload_type'];
                            $unloaddetail->reason               = (!empty($item['reason'])) ? $item['reason'] : null;
                            $unloaddetail->save();

                            //----------------------End unload details--------------

                            //----------------------Start Invoice Header
                            $invoice = new Invoice;
                            $invoice->customer_id           = $request->customer_id;
                            $invoice->trip_id               = $request->trip_id;
                            $invoice->salesman_id           = $request->salesman_id;
                            $invoice->invoice_type          = 4;
                            $invoice->invoice_number        = $request->code;
                            $invoice->invoice_date          = date('Y-m-d', strtotime($request->invoice_date));
                            $invoice->payment_term_id       = 1;
                            $invoice->invoice_due_date      = date('Y-m-d', strtotime($request->invoice_date));
                            $invoice->total_gross           = $invoice->total_gross + $itemprice;
                            $invoice->total_net             = $invoice->total_net + $itemprice;
                            $invoice->total_vat             = $invoice->total_vat + $vat;
                            $invoice->grand_total           = $request->grand_total + $total;
                            $invoice->pending_credit        = $invoice->grand_total;
                            $invoice->current_stage         = 'Pending';
                            $invoice->source                = $request->source;
                            $invoice->status                = 0;
                            $invoice->save();
                            //----------------------End Invoice Header
                            //-----------------------------------------Start Invoice Details--------------

                            $invoiceDetail = new InvoiceDetail;
                            $invoiceDetail->invoice_id    = $invoice->id;
                            $invoiceDetail->item_id       = $item['item_id'];
                            $invoiceDetail->item_uom_id   = $item['item_uom'];
                            $invoiceDetail->item_qty      = $item['unload_qty'];
                            $invoiceDetail->item_price    = $price;
                            $invoiceDetail->item_gross    = $itemprice;
                            $invoiceDetail->item_net      = $itemprice;
                            $invoiceDetail->item_vat      = $vat;
                            $invoiceDetail->item_grand_total = $total;
                            $invoiceDetail->save();
                        }
                        //-----------------------------------------End Invoice Details--------------
                    }
                }
            }

            \DB::commit();

            if ($request->unload_type != 2) {

                updateNextComingNumber('App\Model\SalesmanUnload', 'unload_number');

                // $unloadheader->getSaveData();
                $odooPost = $this->editData($unloadheader->id);

                $response = Curl::to('http://rfctest.dyndns.org:11214/api/create/vehicle_unloading')
                    ->withData(array('params' => $odooPost))
                    ->asJson(true)
                    ->post();

                if (isset($response['result'])) {

                    $data = json_decode($response['result']);
                    if ($data->response[0]->state == "success") {
                        $unloadheader->oddo_id = $data->response[0]->stockpicking_id;
                    } else {
                        $unloadheader->odoo_failed_response = $response['error'];
                    }
                }

                if (isset($response['error'])) {
                    $unloadheader->odoo_failed_response = $response['error'];
                }

                $unloadheader->save();


                return prepareResult(true, $unloadheader, [], "Salesman Unload added successfully", $this->created);
            } else {
                return prepareResult(true, $goodreceiptnote, [], "Salesman Unload added successfully", $this->created);
            }
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function editData($id)
    {
        $salesman_unload = SalesmanUnload::with(
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'salesmanUnloadDetail',
            'salesmanUnloadDetail.item:id,item_name,item_code',
            'salesmanUnloadDetail.itemUom:id,name',
            'route:id,depot_id,route_code,route_name',
            'route.depot:id,depot_code,depot_name',
            'trip'
        )->find($id);

        if (is_object($salesman_unload->route)) {
            if (is_object($salesman_unload->route->depot)) {
                $Warehouse = Warehouse::where('depot_id', $salesman_unload->route->depot_id)->first();

                $warehouselocation = Storagelocation::where('warehouse_id', $Warehouse->id)
                    ->where('loc_type', '1')
                    ->first();

                $salesman_unload->src_location = $warehouselocation->name;
            }
        }

        return $salesman_unload;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating unload header.", $this->unauthorized);
        }
        $UnloadHeader = SalesmanUnload::with(
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'salesmanUnloadDetail',
            'salesmanUnloadDetail.item:id,item_name,item_code',
            'salesmanUnloadDetail.itemUom:id,name',
            'route:id,depot_id,route_code,route_name',
            'route.depot:id,depot_code,depot_name',
            'trip'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($UnloadHeader)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $UnloadHeader, [], "Salesman Unload Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $unloadheader = SalesmanUnload::where('uuid', $uuid)->first();


            SalesmanUnloadDetail::where('salesman_load_id', $unloadheader->id)->delete();

            $unloadheader->trip_id         = (!empty($request->trip_id)) ? $request->trip_id : null;
            $unloadheader->unload_type            = (!empty($request->unload_type)) ? $request->unload_type : null;
            $unloadheader->code            = (!empty($request->code)) ? $request->code : null;
            $unloadheader->route_id            = (!empty($request->route_id)) ? $request->route_id : null;
            $unloadheader->salesman_id       = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $unloadheader->transaction_date        = date('Y-m-d', strtotime($request->transaction_date));
            $unloadheader->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    /* $transactionheaderde = DB::table('transaction_detail')
							  ->join('transactions', 'transactions.id', '=', 'transaction_detail.transaction_id')
							  ->join('trips', 'trips.id', '=', 'transactions.trip_id','left')
							  ->join('users', 'trips.salesmancode', '=', 'users.id','left')
							  ->select('transaction_detail.id')->where('item_id',$item['item_id'])->where('trips.id' ,$request->trip_id)->first();
							  */
                    $transactionheaderde = TransactionDetail::with(
                        'Transaction',
                        'Transaction.trip',
                        'Transaction.trip.users'
                    )
                        ->where('item_id', $item['item_id'])
                        ->where('trip.id', $request->trip_id)
                        ->first();

                    $reults = getItemDetails($item['item_id'], $item['item_uom'], $item['unload_qty']);

                    $unloaddetail = new SalesmanUnloadDetail;
                    $unloaddetail->salesman_load_id = $unloadheader->id;
                    $unloaddetail->item_id = $item['item_id'];
                    $unloaddetail->item_uom = $reults['UOM'];
                    $unloaddetail->unload_qty = $reults['Qty'];
                    $unloaddetail->unload_date = $item['unload_date'];
                    $unloaddetail->unload_type = $item['unload_type'];
                    $unloaddetail->save();

                    $transactiondetail = TransactionDetail::where('id', $transactionheaderde->id)->first();

                    $transactiondetail->closing_qty = $reults['Qty'];
                    $transactiondetail->unload_qty = $reults['Qty'];
                    $transactiondetail->save();
                }
            }

            \DB::commit();

            $unloadheader->getSaveData();

            return prepareResult(true, $unloadheader, [], "Unload header updated successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating load header.", $this->unauthorized);
        }

        $loadheader = SalesmanUnload::where('uuid', $uuid)->first();

        if (is_object($loadheader)) {
            $loadheaderId = $loadheader->id;
            $loadheader->delete();
            if ($loadheader) {
                SalesmanUnload::where('salesman_load_id', $loadheaderId)->delete();
            }
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->salesman_load_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $loadheader = SalesmanUnload::where('uuid', $uuid)->first();
                if (is_object($loadheader)) {
                    $loadheaderId = $loadheader->id;
                    $loadheader->delete();
                    if ($loadheader) {
                        SalesmanUnloadDetail::where('salesman_load_id', $loadheaderId)->delete();
                    }
                }
            }
            $loadheader = $this->index();
            return prepareResult(true, $loadheader, [], "Load header deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'trip_id' => 'required',
                'unload_type' => 'required',
                'route_id' => 'required',
                'salesman_id' => 'required',
                'transaction_date' => 'required',
            ]);
        }
        if ($type == "unloadlist") {
            $validator = \Validator::make($input, [
                'filter' => 'required',
                'unload_type' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'salesman_load_ids' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
