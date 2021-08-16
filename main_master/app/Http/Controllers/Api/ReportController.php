<?php

namespace App\Http\Controllers\Api;

use App\Exports\AgingSummaryReportExport;
use App\Exports\CarryOverReportExport;
use App\Exports\CreditnotesReportExport;
use App\Exports\CustomerReportExport;
use App\Exports\DailyFieldActivityReportExport;
use App\Exports\CustomerStatementReportExport;
use App\Exports\DebitnotesReportExport;
use App\Exports\EstimateReportExport;
use App\Exports\InvoicesReportExport;
use App\Exports\ItemReportExport;
use App\Exports\LoadSheetReportExport;
use App\Exports\OrderReportExport;
use App\Exports\PaymentreceivedReportExport;
use App\Exports\ProductSummaryCustomerSalesReportExport;
use App\Exports\SalesAnalysisReportExport;
use App\Exports\SalesmanReportExport;
use App\Exports\VanCustomerReportExport;
use App\Http\Controllers\Controller;
use App\Model\CreditNote;
use App\Model\CustomerInfo;
use App\Model\DebitNote;
use App\Model\Invoice;
use App\Model\Item;
use App\Model\JourneyPlan;
use App\Model\LoadRequest;
use App\Model\Order;
use App\Model\Route;
use App\Model\SalesmanInfo;
use App\Model\SalesmanLob;
use App\Model\Transaction;
use App\Model\Trip;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use URL;
use Carbon\CarbonPeriod;

class ReportController extends Controller
{
    public function sales_by_customer(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = $request->start_date;
        $end_date   =  $request->end_date;

        DB::enableQueryLog();
        $salesbycustomer = DB::table('customer_infos')
            ->where("customer_infos.organisation_id", $request->user()->organisation_id)
            ->leftjoin('users', 'customer_infos.user_id', '=', 'users.id')
            ->leftJoin('customer_types',  'customer_infos.customer_type_id', '=', 'customer_types.id')
            ->leftjoin('customer_groups', 'customer_infos.customer_group_id', '=', 'customer_groups.id',)
            ->leftjoin('invoices', 'customer_infos.user_id', '=', 'invoices.customer_id')
            ->leftJoin("customer_lobs", "customer_infos.id", '=', "customer_lobs.customer_info_id")
            ->leftJoin("salesman_infos", "users.id", "salesman_infos.user_id")
            ->select(
                'customer_infos.id',
                'customer_infos.user_id as customer_user_id',
                'customer_infos.customer_code',
                'users.firstname',
                'users.lastname',
                'users.email',
                'users.mobile',
                'customer_types.customer_type_name as customer_type',
                'customer_groups.group_name as customer_group',
                'invoices.invoice_date',
                'salesman_infos.salesman_supervisor',
                'salesman_infos.user_id',
                'customer_infos.region_id',
                'customer_infos.route_id',
                'customer_lobs.lob_id',
            );

        if ($start_date != '' && $end_date != '') {
            $salesbycustomer = $salesbycustomer->whereDate('invoices.invoice_date', '>=', $start_date)
                ->whereDate('invoices.invoice_date', '<=', $end_date);
        }
        // var_dump(count($request->region),count($request->route));die;
        if (isset($request->channel)  && count($request->channel)) {
            $salesbycustomer = $salesbycustomer->orWhereIn('customer_infos.channel_id', $request->channel);
        }

        if (isset($request->region) && count($request->region) >= 1) {
            $salesbycustomer = $salesbycustomer->whereIn('customer_infos.region_id', $request->region);
        }

        if (isset($request->route) && count($request->route) >= 1) {
            $salesbycustomer = $salesbycustomer->whereIn('customer_infos.route_id', $request->route);
        }

        if (isset($request->division) && count($request->division) >= 1) {
            $salesbycustomer = $salesbycustomer->whereIn('customer_lobs.lob_id', $request->division);
        }

        if (isset($request->supervisor) && count($request->supervisor) >= 1) {
            $salesbycustomer = $salesbycustomer->whereIn('salesman_infos.salesman_supervisor', $request->supervisor);
        }

        if (isset($request->salesman) && count($request->salesman) >= 1) {
            $salesbycustomer = $salesbycustomer->whereIn('salesman_infos.user_id', $request->salesman);
        }
        $salesbycustomer = $salesbycustomer->groupBy('customer_infos.id')
            ->groupBy('users.firstname')->groupBy('users.lastname')
            ->groupBy('users.email')
            ->groupBy('users.mobile')
            ->groupBy('customer_type')
            ->groupBy('customer_group')
            ->get();

        $columns = isset($request->columns) ? $request->columns : [];

        if (is_object($salesbycustomer)) {
            foreach ($salesbycustomer as $key => $val) {

                $invoice = DB::table("invoices")->where('customer_id', $val->customer_user_id)->get();

                $credits = DB::table("credit_notes")->where('customer_id', $val->customer_user_id)->select("grand_total")->get();

                $invoice_count       = 0;
                $total_sale          = 0;
                $total_sale_with_tax = 0;
                $total_return = 0;
                if (is_object($invoice)) {
                    foreach ($invoice as $inv) {
                        $invoice_count       = $invoice_count + 1;
                        $total_sale          = $total_sale + $inv->total_net;
                        $total_sale_with_tax = $total_sale_with_tax + $inv->grand_total;
                    }
                }
                if (is_object($credits)) {
                    foreach ($credits as $inv) {
                        $total_return = $total_return + $inv->grand_total;
                    }
                }
                $salesbycustomer[$key]->invoice_count       = $invoice_count;
                $salesbycustomer[$key]->total_sale          = $total_sale;
                $salesbycustomer[$key]->total_sale_with_tax = $total_sale_with_tax;
                $salesbycustomer[$key]->total_return       = $total_return;
                $salesbycustomer[$key]->total_return_percentage = $total_return != 0 && $total_sale_with_tax ? $total_return / $total_sale_with_tax * 100 : 0;

                if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                       unset($salesbycustomer[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($salesbycustomer[$key]->lastname);
                    }
                    if (!in_array('email', $columns)) {
                        unset($salesbycustomer[$key]->email);
                    }
                    if (!in_array('mobile', $columns)) {
                        unset($salesbycustomer[$key]->mobile);
                    }
                    if (!in_array('customer_type', $columns)) {
                        unset($salesbycustomer[$key]->customer_type);
                    }
                    if (!in_array('customer_group', $columns)) {
                        unset($salesbycustomer[$key]->customer_group);
                    }
                    if (!in_array('invoice_count', $columns)) {
                        unset($salesbycustomer[$key]->invoice_count);
                    }
                    if (!in_array('total_sale', $columns)) {
                        unset($salesbycustomer[$key]->total_sale);
                    }
                    if (!in_array('total_sale_with_tax', $columns)) {
                        unset($salesbycustomer[$key]->total_sale_with_tax);
                    }
                    if (!in_array('total_return', $columns)) {
                        unset($salesbycustomer[$key]->total_return);
                    }
                } else {
                    unset($salesbycustomer[$key]->email);
                    unset($salesbycustomer[$key]->mobile);
                    unset($salesbycustomer[$key]->customer_type);
                    unset($salesbycustomer[$key]->customer_group);
                }
                unset($salesbycustomer[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $salesbycustomer, [], "Sales by customer listing", $this->success);
        } else {
            Excel::store(new CustomerReportExport($salesbycustomer, $columns), 'sales_by_customer_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/sales_by_customer_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function sales_by_item(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $salesbyitem = DB::table('items')
            ->join('item_groups', 'item_groups.id', '=', 'items.item_group_id', 'left')
            ->join('item_major_categories', 'item_major_categories.id', '=', 'items.item_major_category_id', 'left')
            ->join('brands', 'brands.id', '=', 'items.brand_id', 'left')
            ->join('invoice_details', 'invoice_details.item_id', '=', 'items.id', 'left')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id', 'left');

        $salesbyitem = $salesbyitem->select(
            'items.id',
            'items.item_code',
            'items.item_name',
            'item_groups.name as item_group',
            'item_major_categories.name as item_major_category',
            'brands.brand_name'
        );

        if ($start_date != '' && $end_date != '') {
            $salesbyitem = $salesbyitem->whereBetween('invoices.invoice_date', [$start_date, $end_date]);
        }

        $salesbyitem = $salesbyitem->groupBy('items.id')->groupBy('items.item_code')->groupBy('items.item_name')
            ->groupBy('item_group')->groupBy('item_major_category')->groupBy('brands.brand_name')->get();

        $columns = $request->columns;
        if (is_object($salesbyitem)) {
            foreach ($salesbyitem as $key => $val) {
                $invoice = DB::table('invoices')
                    ->join('invoice_details', 'invoice_details.invoice_id', '=', 'invoices.id', 'left')
                    ->where('invoice_details.item_id', $val->id)
                    ->get();

                $invoice_count       = 0;
                $total_sale          = 0;
                $total_sale_with_tax = 0;
                if (is_object($invoice)) {
                    foreach ($invoice as $inv) {
                        $invoice_count       = $invoice_count + 1;
                        $total_sale          = $total_sale + $inv->total_net;
                        $total_sale_with_tax = $total_sale_with_tax + $inv->grand_total;
                    }
                }
                $salesbyitem[$key]->invoice_count       = $invoice_count;
                $salesbyitem[$key]->total_sale          = $total_sale;
                $salesbyitem[$key]->total_sale_with_tax = $total_sale_with_tax;
                if (count($columns) > 0) {
                    if (!in_array('item_code', $columns)) {
                        unset($salesbyitem[$key]->item_code);
                    }
                    if (!in_array('item_name', $columns)) {
                        unset($salesbyitem[$key]->item_name);
                    }
                    if (!in_array('item_group', $columns)) {
                        unset($salesbyitem[$key]->item_group);
                    }
                    if (!in_array('item_major_category', $columns)) {
                        unset($salesbyitem[$key]->item_major_category);
                    }
                    if (!in_array('brand_name', $columns)) {
                        unset($salesbyitem[$key]->brand_name);
                    }
                    if (!in_array('invoice_count', $columns)) {
                        unset($salesbyitem[$key]->invoice_count);
                    }
                    if (!in_array('total_sale', $columns)) {
                        unset($salesbyitem[$key]->total_sale);
                    }
                    if (!in_array('total_sale_with_tax', $columns)) {
                        unset($salesbyitem[$key]->total_sale_with_tax);
                    }
                } else {
                    unset($salesbyitem[$key]->item_group);
                    unset($salesbyitem[$key]->item_major_category);
                    unset($salesbyitem[$key]->brand_name);
                }
                unset($salesbyitem[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $salesbyitem, [], "Sales by item listing", $this->success);
        } else {
            Excel::store(new ItemReportExport($salesbyitem, $columns), 'item_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/item_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function sales_by_salesman(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $salesbysalesman = DB::table('salesman_infos')
            ->join('users', 'users.id', '=', 'salesman_infos.user_id', 'left')
            ->join('routes', 'routes.id', '=', 'salesman_infos.route_id', 'left')
            ->join('salesman_types', 'salesman_types.id', '=', 'salesman_infos.salesman_type_id', 'left')
            ->join('trips', 'trips.salesman_id', '=', 'salesman_infos.id', 'left')
            ->join('invoices', 'invoices.trip_id', '=', 'trips.id', 'left');
        $salesbysalesman = $salesbysalesman->select(
            'salesman_infos.id',
            'users.firstname',
            'users.lastname',
            'users.email',
            'routes.route_name',
            'salesman_types.name as salesman_type'
        );
        if ($start_date != '' && $end_date != '') {
            $salesbysalesman = $salesbysalesman->whereBetween('invoices.invoice_date', [$start_date, $end_date]);
        }
        //$salesbysalesman = $salesbysalesman->get();
        $salesbysalesman = $salesbysalesman->groupBy('salesman_infos.id')->groupBy('users.firstname')->groupBy('users.lastname')
            ->groupBy('users.email')->groupBy('routes.route_name')->groupBy('salesman_type')->get();

        $columns = $request->columns;
        if (is_object($salesbysalesman)) {
            foreach ($salesbysalesman as $key => $val) {
                $invoice_count       = 0;
                $total_sale          = 0;
                $total_sale_with_tax = 0;
                $trip                = Trip::where('salesman_id', $val->id)->get();
                if (is_object($trip)) {
                    foreach ($trip as $trp) {
                        $invoice = Invoice::where('trip_id', $trp->id)->get();
                        if (is_object($invoice)) {
                            foreach ($invoice as $inv) {
                                $invoice_count       = $invoice_count + 1;
                                $total_sale          = $total_sale + $inv->total_net;
                                $total_sale_with_tax = $total_sale_with_tax + $inv->grand_total;
                            }
                        }
                    }
                }

                $salesbysalesman[$key]->invoice_count       = $invoice_count;
                $salesbysalesman[$key]->total_sale          = $total_sale;
                $salesbysalesman[$key]->total_sale_with_tax = $total_sale_with_tax;
                if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($salesbysalesman[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($salesbysalesman[$key]->lastname);
                    }
                    if (!in_array('email', $columns)) {
                        unset($salesbysalesman[$key]->email);
                    }
                    if (!in_array('route_name', $columns)) {
                        unset($salesbysalesman[$key]->route_name);
                    }
                    if (!in_array('salesman_type', $columns)) {
                        unset($salesbysalesman[$key]->salesman_type);
                    }
                    if (!in_array('invoice_count', $columns)) {
                        unset($salesbysalesman[$key]->invoice_count);
                    }
                    if (!in_array('total_sale', $columns)) {
                        unset($salesbysalesman[$key]->total_sale);
                    }
                    if (!in_array('total_sale_with_tax', $columns)) {
                        unset($salesbysalesman[$key]->total_sale_with_tax);
                    }
                } else {
                    unset($salesbysalesman[$key]->email);
                    unset($salesbysalesman[$key]->route_name);
                    unset($salesbysalesman[$key]->salesman_type);
                }
                unset($salesbysalesman[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $salesbysalesman, [], "Sales by customer listing", $this->success);
        } else {
            Excel::store(new SalesmanReportExport($salesbysalesman, $columns), 'sales_by_salesman_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/sales_by_salesman_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function invoice_details(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $invoices = DB::table('invoices')
            ->join('customer_infos', 'customer_infos.id', '=', 'invoices.customer_id', 'left')
            ->join('users', 'users.id', '=', 'customer_infos.user_id', 'left')
            ->join('orders', 'orders.id', '=', 'invoices.order_id', 'left')
            ->join('deliveries', 'deliveries.id', '=', 'invoices.delivery_id', 'left')
            ->join('payment_terms', 'payment_terms.id', '=', 'invoices.payment_term_id', 'left');
        $invoices = $invoices->select(
            'users.firstname',
            'users.lastname',
            'users.email',
            'orders.order_number',
            'deliveries.delivery_number',
            'payment_terms.name as payment_term',
            'invoices.invoice_number',
            'invoices.invoice_date',
            'invoices.invoice_due_date',
            'invoices.total_qty',
            'invoices.total_gross',
            'invoices.total_discount_amount',
            'invoices.total_net',
            'invoices.total_vat',
            'invoices.total_excise',
            'invoices.grand_total',
            'invoices.payment_received'
        );
        if ($start_date != '' && $end_date != '') {
            $invoices = $invoices->whereBetween('invoices.invoice_date', [$start_date, $end_date]);
        }
        $invoices = $invoices->get();

        $columns = $request->columns;
        if (is_object($invoices)) {
            foreach ($invoices as $key => $val) {
                if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($invoices[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($invoices[$key]->lastname);
                    }
                    if (!in_array('email', $columns)) {
                        unset($invoices[$key]->email);
                    }
                    if (!in_array('order_number', $columns)) {
                        unset($invoices[$key]->order_number);
                    }
                    if (!in_array('delivery_number', $columns)) {
                        unset($invoices[$key]->delivery_number);
                    }
                    if (!in_array('payment_term', $columns)) {
                        unset($invoices[$key]->payment_term);
                    }
                    if (!in_array('invoice_number', $columns)) {
                        unset($invoices[$key]->invoice_number);
                    }
                    if (!in_array('invoice_date', $columns)) {
                        unset($invoices[$key]->invoice_date);
                    }
                    if (!in_array('invoice_due_date', $columns)) {
                        unset($invoices[$key]->invoice_due_date);
                    }
                    if (!in_array('total_qty', $columns)) {
                        unset($invoices[$key]->total_qty);
                    }
                    if (!in_array('total_gross', $columns)) {
                        unset($invoices[$key]->total_gross);
                    }
                    if (!in_array('total_discount_amount', $columns)) {
                        unset($invoices[$key]->total_discount_amount);
                    }
                    if (!in_array('total_net', $columns)) {
                        unset($invoices[$key]->total_net);
                    }
                    if (!in_array('total_vat', $columns)) {
                        unset($invoices[$key]->total_vat);
                    }
                    if (!in_array('total_excise', $columns)) {
                        unset($invoices[$key]->total_excise);
                    }
                    if (!in_array('grand_total', $columns)) {
                        unset($invoices[$key]->grand_total);
                    }
                    if (!in_array('payment_received', $columns)) {
                        unset($invoices[$key]->payment_received);
                    }
                } else {
                    unset($invoices[$key]->firstname);
                    unset($invoices[$key]->lastname);
                    unset($invoices[$key]->total_gross);
                    unset($invoices[$key]->total_discount_amount);
                    unset($invoices[$key]->total_net);
                    unset($invoices[$key]->total_vat);
                    unset($invoices[$key]->total_excise);
                    unset($invoices[$key]->grand_total);
                    unset($invoices[$key]->payment_received);
                }
                unset($invoices[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $invoices, [], "Sales by customer listing", $this->success);
        } else {
            Excel::store(new InvoicesReportExport($invoices, $columns), 'invoices_detail_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/invoices_detail_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function payment_received(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $collections = DB::table('collections')
            ->join('users', 'users.id', '=', 'collections.customer_id', 'left')
            ->join('collection_details', 'collection_details.collection_id', '=', 'collections.id', 'left')
            ->join('invoices', 'invoices.id', '=', 'collection_details.invoice_id', 'left');
        $collections = $collections->select(
            'users.firstname',
            'users.lastname',
            'collections.collection_number',
            'collections.payemnt_type',
            'invoices.invoice_number',
            'collection_details.amount',
            'collection_details.pending_amount',
            'collections.created_at'
        );
        if ($start_date != '' && $end_date != '') {
            $collections = $collections->whereBetween('invoices.invoice_date', [$start_date, $end_date]);
        }
        $collections = $collections->get();

        $columns = $request->columns;
        if (is_object($collections)) {
            foreach ($collections as $key => $val) {
                if (is_array($columns) && sizeof($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($collections[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($collections[$key]->lastname);
                    }
                    if (!in_array('collection_number', $columns)) {
                        unset($collections[$key]->collection_number);
                    }
                    if (!in_array('payemnt_type', $columns)) {
                        unset($collections[$key]->payemnt_type);
                    }
                    if (!in_array('invoice_number', $columns)) {
                        unset($collections[$key]->invoice_number);
                    }
                    if (!in_array('amount', $columns)) {
                        unset($collections[$key]->amount);
                    }
                    if (!in_array('pending_amount', $columns)) {
                        unset($collections[$key]->pending_amount);
                    }
                    if (!in_array('created_at', $columns)) {
                        unset($collections[$key]->created_at);
                    }
                } else {
                    unset($collections[$key]->collection_number);
                    unset($collections[$key]->pending_amount);
                    unset($collections[$key]->created_at);
                }
                unset($collections[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $collections, [], "Payment received listing", $this->success);
        } else {
            Excel::store(new PaymentreceivedReportExport($collections, $columns), 'payment_received_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/payment_received_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function creditnote_detail(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $credit_notes = DB::table('credit_notes')
            ->join('users', 'users.id', '=', 'credit_notes.customer_id', 'left')
            ->join('credit_note_details', 'credit_note_details.credit_note_id', '=', 'credit_notes.id', 'left')
            ->join('invoices', 'invoices.id', '=', 'credit_notes.invoice_id', 'left')
            ->join('items', 'items.id', '=', 'credit_note_details.item_id', 'left')
            ->join('item_uoms', 'item_uoms.id', '=', 'credit_note_details.item_uom_id', 'left');
        $credit_notes = $credit_notes->select(
            'users.firstname',
            'users.lastname',
            'credit_notes.credit_note_number',
            'credit_notes.credit_note_date',
            'invoices.invoice_number',
            'items.item_name',
            'item_uoms.name as item_uom',
            'credit_note_details.item_qty',
            'credit_note_details.item_price',
            'credit_note_details.item_gross',
            'credit_note_details.item_net',
            'credit_note_details.item_vat'
        );
        if ($start_date != '' && $end_date != '') {
            $credit_notes = $credit_notes->whereBetween('credit_notes.credit_note_date', [$start_date, $end_date]);
        }
        $credit_notes = $credit_notes->get();

        $columns = $request->columns;
        if (is_object($credit_notes)) {
            foreach ($credit_notes as $key => $val) {
                if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($credit_notes[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($credit_notes[$key]->lastname);
                    }
                    if (!in_array('credit_note_number', $columns)) {
                        unset($credit_notes[$key]->credit_note_number);
                    }
                    if (!in_array('credit_note_date', $columns)) {
                        unset($credit_notes[$key]->credit_note_date);
                    }
                    if (!in_array('invoice_number', $columns)) {
                        unset($credit_notes[$key]->invoice_number);
                    }
                    if (!in_array('item_name', $columns)) {
                        unset($credit_notes[$key]->item_name);
                    }
                    if (!in_array('item_uom', $columns)) {
                        unset($credit_notes[$key]->item_uom);
                    }
                    if (!in_array('item_qty', $columns)) {
                        unset($credit_notes[$key]->item_qty);
                    }
                    if (!in_array('item_price', $columns)) {
                        unset($credit_notes[$key]->item_price);
                    }
                    if (!in_array('item_gross', $columns)) {
                        unset($credit_notes[$key]->item_gross);
                    }
                    if (!in_array('item_net', $columns)) {
                        unset($credit_notes[$key]->item_net);
                    }
                    if (!in_array('item_vat', $columns)) {
                        unset($credit_notes[$key]->item_vat);
                    }
                } else {
                    unset($credit_notes[$key]->item_qty);
                    unset($credit_notes[$key]->item_price);
                    unset($credit_notes[$key]->item_gross);
                    unset($credit_notes[$key]->item_net);
                    unset($credit_notes[$key]->item_vat);
                    unset($credit_notes[$key]->credit_note_date);
                }
                unset($credit_notes[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $credit_notes, [], "Credate note detail listing", $this->success);
        } else {
            Excel::store(new CreditnotesReportExport($credit_notes, $columns), 'credit_notes_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/credit_notes_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function debitnote_detail(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $debit_notes = DB::table('debit_notes')
            ->join('users', 'users.id', '=', 'debit_notes.customer_id', 'left')
            ->join('debit_note_details', 'debit_note_details.debit_note_id', '=', 'debit_notes.id', 'left')
            ->join('invoices', 'invoices.id', '=', 'debit_notes.invoice_id', 'left')
            ->join('items', 'items.id', '=', 'debit_note_details.item_id', 'left')
            ->join('item_uoms', 'item_uoms.id', '=', 'debit_note_details.item_uom_id', 'left');
        $debit_notes = $debit_notes->select(
            'users.firstname',
            'users.lastname',
            'debit_notes.debit_note_number',
            'debit_notes.debit_note_date',
            'invoices.invoice_number',
            'items.item_name',
            'item_uoms.name as item_uom',
            'debit_note_details.item_qty',
            'debit_note_details.item_price',
            'debit_note_details.item_gross',
            'debit_note_details.item_net',
            'debit_note_details.item_vat'
        );
        if ($start_date != '' && $end_date != '') {
            $debit_notes = $debit_notes->whereBetween('debit_notes.debit_note_date', [$start_date, $end_date]);
        }
        $debit_notes = $debit_notes->get();

        $columns = $request->columns;
        if (is_object($debit_notes)) {
            foreach ($debit_notes as $key => $val) {
                if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($debit_notes[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($debit_notes[$key]->lastname);
                    }
                    if (!in_array('debit_note_number', $columns)) {
                        unset($debit_notes[$key]->debit_note_number);
                    }
                    if (!in_array('credit_note_date', $columns)) {
                        unset($debit_notes[$key]->credit_note_date);
                    }
                    if (!in_array('invoice_number', $columns)) {
                        unset($debit_notes[$key]->invoice_number);
                    }
                    if (!in_array('item_name', $columns)) {
                        unset($debit_notes[$key]->item_name);
                    }
                    if (!in_array('item_uom', $columns)) {
                        unset($debit_notes[$key]->item_uom);
                    }
                    if (!in_array('item_qty', $columns)) {
                        unset($debit_notes[$key]->item_qty);
                    }
                    if (!in_array('item_price', $columns)) {
                        unset($debit_notes[$key]->item_price);
                    }
                    if (!in_array('item_gross', $columns)) {
                        unset($debit_notes[$key]->item_gross);
                    }
                    if (!in_array('item_net', $columns)) {
                        unset($debit_notes[$key]->item_net);
                    }
                    if (!in_array('item_vat', $columns)) {
                        unset($debit_notes[$key]->item_vat);
                    }
                } else {
                    unset($debit_notes[$key]->item_qty);
                    unset($debit_notes[$key]->item_price);
                    unset($debit_notes[$key]->item_gross);
                    unset($debit_notes[$key]->item_net);
                    unset($debit_notes[$key]->item_vat);
                    unset($debit_notes[$key]->credit_note_date);
                }
                unset($debit_notes[$key]->id);
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $debit_notes, [], "Debit note detail listing", $this->success);
        } else {
            Excel::store(new DebitnotesReportExport($debit_notes, $columns), 'debit_notes_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/debit_notes_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function estimate_detail(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $estimation = DB::table('estimation')
            ->join('users', 'users.id', '=', 'estimation.customer_id', 'left')
            ->join('estimation_detail', 'estimation_detail.estimation_id', '=', 'estimation.id', 'left')
            ->join('items', 'items.id', '=', 'estimation_detail.item_id', 'left')
            ->join('item_uoms', 'item_uoms.id', '=', 'estimation_detail.item_uom_id', 'left');
        $estimation = $estimation->select(
            'users.firstname',
            'users.lastname',
            'estimation.reference',
            'estimation.estimate_code',
            'estimation.estimate_date',
            'estimation.expairy_date',
            'estimation.subject',
            'items.item_name',
            'item_uoms.name as item_uom',
            'estimation_detail.item_qty',
            'estimation_detail.item_price',
            'estimation_detail.item_grand_total',
            'estimation_detail.item_net',
            'estimation_detail.item_vat'
        );
        if ($start_date != '' && $end_date != '') {
            $estimation = $estimation->whereBetween('estimation.estimate_date', [$start_date, $end_date]);
        }
        $estimation = $estimation->get();

        $columns = $request->columns;
        if (is_object($estimation)) {
            foreach ($estimation as $key => $val) {
                if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($estimation[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($estimation[$key]->lastname);
                    }
                    if (!in_array('reference', $columns)) {
                        unset($estimation[$key]->reference);
                    }
                    if (!in_array('estimate_code', $columns)) {
                        unset($estimation[$key]->estimate_code);
                    }
                    if (!in_array('estimate_date', $columns)) {
                        unset($estimation[$key]->estimate_date);
                    }
                    if (!in_array('expairy_date', $columns)) {
                        unset($estimation[$key]->expairy_date);
                    }
                    if (!in_array('subject', $columns)) {
                        unset($estimation[$key]->subject);
                    }
                    if (!in_array('item_name', $columns)) {
                        unset($estimation[$key]->item_name);
                    }
                    if (!in_array('item_uom', $columns)) {
                        unset($estimation[$key]->item_uom);
                    }
                    if (!in_array('item_qty', $columns)) {
                        unset($estimation[$key]->item_qty);
                    }
                    if (!in_array('item_price', $columns)) {
                        unset($estimation[$key]->item_price);
                    }
                    if (!in_array('item_grand_total', $columns)) {
                        unset($estimation[$key]->item_grand_total);
                    }
                    if (!in_array('item_net', $columns)) {
                        unset($estimation[$key]->item_net);
                    }
                    if (!in_array('item_vat', $columns)) {
                        unset($estimation[$key]->item_vat);
                    }
                } else {
                    unset($estimation[$key]->item_qty);
                    unset($estimation[$key]->item_price);
                    unset($estimation[$key]->item_grand_total);
                    unset($estimation[$key]->item_net);
                    unset($estimation[$key]->item_vat);
                }
                unset($estimation[$key]->id);
            }
        }

        //  echo  "<pre>"; print_r($estimation); exit;

        if ($request->export == 0) {
            return prepareResult(true, $estimation, [], "Estimate detail listing", $this->success);
        } else {
            Excel::store(new EstimateReportExport($estimation, $columns), 'estimate_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/estimate_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function aging_summary(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $customers = DB::table('users');
        $customers = $customers->select('users.id', 'users.firstname', 'users.lastname', 'users.email', 'users.mobile');
        $customers = $customers->where('users.usertype', 2);
        if ($start_date != '' && $end_date != '') {
            $customers = $customers->whereBetween('users.created_at', [$start_date, $end_date]);
        }
        $customers = $customers->get();

        $columns = $request->columns;
        /* foreach($columns as $k=>$v){
        $columns[$k] = str_replace('inv_1_to_15','1-15 Days',$v);
        $columns[$k] = str_replace('inv_16_to_30','16-30 Days',$v);
        $columns[$k] = str_replace('inv_31_to_45','31-45 Days',$v);
        $columns[$k] = str_replace('inv_45_up','> 45 Days',$v);
        } */
        $CustomerCollection = new Collection();
        if (is_object($customers)) {
            foreach ($customers as $key => $val) {
                $start_date_1_15 = date('Y-m-d');
                $end_date_1_15   = date('Y-m-d', strtotime(date('Y-m-d') . ' + 15 days'));
                $inv_1_to_15     = get_invoice_sum($customers[$key]->id, $start_date_1_15, $end_date_1_15);

                $start_date_16_30 = date('Y-m-d');
                $end_date_13_30   = date('Y-m-d', strtotime($start_date_16_30 . ' + 15 days'));
                $inv_16_to_30     = get_invoice_sum($customers[$key]->id, $start_date_16_30, $end_date_13_30);

                $start_date_31_45 = date('Y-m-d');
                $end_date_31_45   = date('Y-m-d', strtotime($start_date_31_45 . ' + 15 days'));
                $inv_31_to_45     = get_invoice_sum($customers[$key]->id, $start_date_31_45, $end_date_31_45);

                $start_date_45_up = date('Y-m-d', strtotime(date('Y-m-d') . ' + 45 days'));
                $inv_45_up        = get_invoice_sum($customers[$key]->id, $start_date_45_up);

                $total = ($inv_1_to_15 + $inv_16_to_30 + $inv_31_to_45 + $inv_45_up);

                $CustomerCollection->push((object) [
                    'firstname'    => $customers[$key]->firstname,
                    'lastname'     => $customers[$key]->lastname,
                    'email'        => $customers[$key]->email,
                    'mobile'       => $customers[$key]->mobile,
                    'inv_1_to_15'  => $inv_1_to_15,
                    'inv_16_to_30' => $inv_16_to_30,
                    'inv_31_to_45' => $inv_31_to_45,
                    'inv_45_up'    => $inv_45_up,
                    'total'        => $total,
                ]);

                /* if(count($columns)>0){
                if(!in_array('firstname',$columns)){
                unset($customers[$key]->firstname);
                }
                if(!in_array('lastname',$columns)){
                unset($customers[$key]->lastname);
                }
                if(!in_array('email',$columns)){
                unset($customers[$key]->email);
                }
                if(!in_array('mobile',$columns)){
                unset($customers[$key]->mobile);
                }
                }else{
                unset($customers[$key]->email);
                unset($customers[$key]->mobile);
                } */
                //unset($customers[$key]->id);
            }
        }

        if ($request->export == 0) {
            return prepareResult(true, $CustomerCollection, [], "Aging summary detail listing", $this->success);
        } else {
            Excel::store(new AgingSummaryReportExport($CustomerCollection, $columns), 'aging_summary_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/aging_summary_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }
    public function weekly_customer_call(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }


        // $start_date = date('Y-m-d', strtotime('+1 days', strtotime($request->start_date)));
        // $end_date   = $request->end_date;
        $start_date = $request->start_date;

        $end_date = $request->end_date;
        $region = $request->region;
        $route = $request->route;
        $division = $request->division;
        $customerinfo = CustomerInfo::with('region', 'route', 'user', 'customerlob', 'customerlob.lob')->where(['region_id' => $region, 'route_id' => $route]);
        // $customerinfo=$customerinfo->whereHas('customerlob', function($q) use($division){
        //     $q->where(['lob_id'=>$division]);
        // });
        // $customerinfo = $customerinfo->get();
        $result = array();

        if ($customerinfo->count() > 0) {
            foreach ($customerinfo->get() as $customer) {
                $result[] = ['customer_info' => $customer];
            }
        }


        return prepareResult(true, $result, [], "Data successfully exported", $this->success);
    }
    public function load_sheet(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = date('Y-m-d', strtotime('+1 days', strtotime($request->start_date)));
        $end_date   = $request->end_date;

        $load_sheet_query = LoadRequest::select('id', 'route_id', 'salesman_id', 'load_number', 'load_type', 'load_date', 'status')
            ->with(
                'Route:id,route_code,route_name',
                'Salesman:id,firstname,lastname',
                'Salesman.salesmanInfo:id,user_id,salesman_code',
                'LoadRequestDetail:id,load_request_id,item_id,item_uom_id,qty,requested_qty',
                'LoadRequestDetail.Item:id,item_name,item_code',
                'LoadRequestDetail.ItemUom:id,name',
            )
            ->whereBetween('load_date', [$start_date, $end_date]);

        if ($request->salesman_id) {
            $load_sheet_query->where('salesman_id', $request->salesman_id);
        }

        if ($request->route_id) {
            $load_sheet_query->where('route_id', $request->route_id);
        }

        $load_sheet = $load_sheet_query->get();

        if ($request->export == 0) {
            return prepareResult(true, $load_sheet, [], "Load request listing", $this->success);
        } else {

            $file_name = $request->user()->organisation_id . '_load_sheet_report.' . $request->export_type;

            $columns = array(
                'Salesman',
                'Salesman Code',
                'Route Name',
                'Load Number',
                'Load Type',
                'Load Date',
                'Load Status',
                'Item Name',
                'Item Code',
                'Item Uom',
                'Approved Qty',
                'Requested Qty',
            );

            $load_collection = new Collection();
            foreach ($load_sheet as $key => $loadSheet) {
                if (count($loadSheet->LoadRequestDetail)) {
                    foreach ($loadSheet->LoadRequestDetail as $dkey => $detail) {
                        $salesman_name = "N/A";
                        $salesman_code = "N/A";
                        $route_name    = "N/A";
                        $item_name     = "N/A";
                        $item_code     = "N/A";
                        $item_uom      = "N/A";

                        if (is_object($load_sheet[$key])) {
                            if (is_object($load_sheet[$key]->Salesman)) {
                                $salesman_name = $load_sheet[$key]->Salesman->getName();
                                $salesman_code = $load_sheet[$key]->Salesman->salesmanInfo->salesman_code;
                            }

                            if (is_object($load_sheet[$key]->Route)) {
                                $route_name = $load_sheet[$key]->Route->route_name;
                            }

                            if (is_object($detail->Item)) {
                                $item_name = $detail->Item->item_name;
                                $item_code = $detail->Item->item_code;
                            }

                            if (is_object($detail->ItemUom)) {
                                $item_uom = $detail->ItemUom->name;
                            }

                            $load_collection->push((object) [
                                'salesman'           => $salesman_name,
                                'salesman_code'      => $salesman_code,
                                'route_name'         => $route_name,
                                'load_number'        => $load_sheet[$key]->load_number,
                                'load_type'          => $load_sheet[$key]->load_type,
                                'load_date'          => $load_sheet[$key]->load_date,
                                'load_status'        => $load_sheet[$key]->status,
                                'item_name'          => $item_name,
                                'item_code'          => $item_code,
                                'item_uom'           => $item_uom,
                                'item_qty'           => (!empty($detail->qty) ? $detail->qty : 0),
                                'item_requested_qty' => (!empty($detail->requested_qty) ? $detail->requested_qty : 0),
                            ]);
                        }
                    }
                }
            }

            Excel::store(new LoadSheetReportExport($load_collection, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    public function sales_analysis(Request $request)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->start_date and !$request->enddate) {
            return prepareResult(false, [], [], "Error while validating parameters.", $this->unauthorized);
        }


        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $invoice_query = DB::table('invoices')->select(
            'invoices.id as invoices_id',
            'invoices.salesman_id',
            'invoices.customer_id',
            'invoice_details.id as invoice_details_id',
            'invoice_details.item_id',
            'items.item_code',
            'items.item_name',
        )
            ->leftJoin('invoice_details', function ($join) {
                $join->on('invoice_details.invoice_id', '=', 'invoices.id');
            })
            ->leftJoin('items', function ($join) {
                $join->on('items.id', '=', 'invoice_details.item_id');
            })
            
            ->groupBy('invoice_details.item_id')

            ->selectRaw("SUM(invoice_details.item_grand_total) as Total_invoice_sales,
                                            SUM(invoice_details.item_net) as Total_invoice_net")
            ->whereBetween('invoices.invoice_date', [$start_date, $end_date]);

        if ($request->salesman) {
            $invoice_query->where('salesman_id', $request->salesman);
        }

        if ($request->supervisor) {
            $salesman_ids = getSalesmanIds("supervisor", $request->supervisor);
            $invoice_query->whereIn('invoices.salesman_id', $salesman_ids);
        }

        if ($request->region) {
            $salesman_ids = getSalesmanIds("region", $request->region);
            $invoice_query->whereIn('invoices.salesman_id', $salesman_ids);
        }

        if ($request->category) {
            $invoice_query->where('items.item_major_category_id', $request->category);
        }

        if ($request->division) {

            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->division);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $invoice_query->whereIn('invoices.salesman_id', $ids);
        }

        $invoices = $invoice_query->get();
       
        $credit_note_query = DB::table('credit_notes')->select(
            'credit_notes.id as credit_notes_id',
            'credit_notes.salesman_id',
            'credit_notes.customer_id',
            'credit_note_details.id as credit_note_details_id',
            'credit_note_details.item_id',
            'items.item_code',
            'items.item_name'
        )
            ->leftJoin('credit_note_details', function ($join) {
                $join->on('credit_note_details.credit_note_id', '=', 'credit_notes.id');
            })
            ->leftJoin('items', function ($join) {
                $join->on('items.id', '=', 'credit_note_details.item_id');
            })
            ->groupBy('credit_note_details.item_id')

            ->selectRaw("SUM(credit_note_details.item_gross) as Total_creditnote")
            ->whereBetween('credit_notes.credit_note_date', [$start_date, $end_date]);

        if ($request->salesman) {
            $credit_note_query->where('salesman_id', $request->salesman);
        }
        if ($request->supervisor) {
            $salesman_ids = getSalesmanIds("supervisor", $request->supervisor);
            $credit_note_query->whereIn('credit_notes.salesman_id', $salesman_ids);
        }

        if ($request->region) {
            $salesman_ids = getSalesmanIds("region", $request->region);
            $credit_note_query->whereIn('credit_notes.salesman_id', $salesman_ids);
        }
        if ($request->category) {
            $credit_note_query->where('items.item_major_category_id', $request->category);
        }
        if ($request->lob) {
            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->lob);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $credit_note_query->whereIn('credit_notes.salesman_id', $ids);
        }

        $credit_note = $credit_note_query->get()->toArray();

        $invoices = json_decode(json_encode($invoices), true);
        $usable_credit_note_id = [];
        foreach ($invoices as $key => $invoice_value) {

            $i        = array_search($invoice_value['item_id'], array_column($credit_note, 'item_id'));
            $return_val = ($i !== false ? $credit_note[$i] : null);
            /*if($i !== false){
                $usable_credit_note_id[] = $i;
            }*/

            if ($return_val) {
                $retrurn_percent = ($return_val->Total_creditnote / $invoice_value['Total_invoice_sales']) * 100;
                $invoices[$key]['Total_retrurn']      = $return_val->Total_creditnote;
            } else{
                $retrurn_percent = "";
                $invoices[$key]['Total_retrurn']      = "";     
            }
            $invoices[$key]['Retrurn_percentage'] = $retrurn_percent;

            unset($invoices[$key]['item_id']);
            unset($invoices[$key]['invoices_id']);
            unset($invoices[$key]['salesman_id']);
            unset($invoices[$key]['customer_id']);
            unset($invoices[$key]['invoice_details_id']);
        }


        
        /*foreach ($credit_note as $key => $credit_note_value) {
            if (!in_array($key, $usable_credit_note_id)) {
                print_r($credit_note_value);
                die();
            }
        }
        print_r($usable_credit_note_id);
        die();*/

        if ($request->export == 0) {
            return prepareResult(true, $invoices, [], "Sales Analysis listing", $this->success);
        } else {

            $file_name = $request->user()->organisation_id . '_sales_analysis_report.xlsx';

            $columns = array(
                'Item Name',
                'Item Code',
                'Total Sale',
                'Total Net',
                'Total Return',
                'Return %',
            );

            $sales_collection = new Collection();
            foreach ($invoices as $key => $invoice_val) {
                $item_name           = "N/A";
                $item_code           = "N/A";
                $total_invoice_sales = "N/A";
                $total_invoice_net   = "N/A";
                $total_return        = "N/A";
                $retrurn_percentage  = "N/A";

                $sales_collection->push((object) [
                    'item_name'           => $invoice_val['item_name'],
                    'item_code'           => $invoice_val['item_code'],
                    'Total_invoice_sales' => (!empty($invoice_val['Total_invoice_sales']) ? $invoice_val['Total_invoice_sales'] : 0),
                    'Total_invoice_net'   => (!empty($invoice_val['Total_invoice_net']) ? $invoice_val['Total_invoice_net'] : 0),
                    'Total_retrurn'       => (!empty($invoice_val['Total_retrurn']) ? $invoice_val['Total_retrurn'] : 0),
                    'Retrurn_percentage'  => (!empty($invoice_val['Retrurn_percentage']) ? $invoice_val['Retrurn_percentage'] : 0),
                ]);
            }

            Excel::store(new SalesAnalysisReportExport($sales_collection, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }


    /**
     * customer statment report.
     *
     * @return \Illuminate\Http\Response
     */

    public function customerBalanceStatementReport(Request $request)
    {
        $input       = $request->json()->all();
        $customer_id = $input['customer_id'];
        $startdate   = Carbon::parse($input['startdate'])->format('Y-m-d');
        $enddate     = Carbon::parse($input['enddate'])->format('Y-m-d');
        $status      = (isset($input['status']) ? $input['status'] : '');

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$customer_id and !$startdate and !$enddate) {
            return prepareResult(false, [], [], "Error while validating parameters.", $this->unauthorized);
        }

        $lastDateOfPre    = Carbon::parse($startdate)->subDays(1);
        $startDateCurrent = Carbon::parse($startdate)->format("d/m/Y");
        $endDateCurrent   = Carbon::parse($enddate)->format("d/m/Y");
        //Customer Invoices
        $userDetails = User::Select('*')
            ->with(
                'organisation',
                'organisation.countryInfo:id,name',
                'customerInfo'
            )
            ->where('id', $customer_id)
            ->first();

        $previousBalance_results = Invoice::select(
            DB::raw('SUM(collection_details.pending_amount) as opening_balance')
        )
            ->leftJoin('collection_details', function ($join) {
                $join->on('collection_details.invoice_id', '=', 'invoices.id');
                $join->on(DB::raw('collection_details.id'), DB::raw('(SELECT MAX(id) from collection_details where invoice_id=invoices.id)'));
            });

        if ($request->lob_id) {
            $previousBalance_results->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->where('invoice_date', '<=', $lastDateOfPre);
        } else {
            $previousBalance_results->where('customer_id', $customer_id)->where('invoice_date', '<=', $lastDateOfPre);
        }
        $previousBalance = $previousBalance_results->first()->toArray();

        $openBalance = 0.00;
        if (!empty($previousBalance['opening_balance'])) {
            $openBalance = $previousBalance['opening_balance'];
        }

        $openingBalance['date']        = $startDateCurrent;
        $openingBalance['transaction'] = '***Opening Balance***';
        $openingBalance['detail']      = '';
        $openingBalance['amount']      = $openBalance;
        $openingBalance['payment']     = '';
        $openingBalance['status']      = '0';
        $openingBalance['created_at']  = '';

        //Customer Invoices
        $invoices_result = Invoice::select(DB::raw("DATE_FORMAT(invoice_date,'%d/%m/%Y') as date,'Bill of Supply' as transaction,CONCAT(invoice_number,' - due on ',DATE_FORMAT(invoice_due_date,'%d/%m/%y')) as detail,grand_total as amount,'0.00' as payment,1 as status, created_at"));
        if ($request->lob_id) {
            $invoices_result->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('invoice_date', [$startdate, $enddate]);
        } else {
            $invoices_result->where('customer_id', $customer_id)->whereBetween('invoice_date', [$startdate, $enddate]);
        }
        $invoices = $invoices_result->orderBy('created_at', 'ASC'); //orderBy('invoice_date', 'ASC');

        $collections_result = DB::table('collections')->select(DB::raw("DATE_FORMAT(cheque_date,'%d/%m/%Y') as date,'Payment Received' as transaction,CONCAT(invoice_amount,' for payment of ',collection_number) as detail,'0.00' as amount,invoice_amount as payment,2 as status, created_at"));
        if ($request->lob_id) {
            $collections_result->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('cheque_date', [$startdate, $enddate]);
        } else {
            $collections_result->where('customer_id', $customer_id)->whereBetween('cheque_date', [$startdate, $enddate]);
        }
        $collections = $collections_result->orderBy('created_at', 'ASC'); //orderBy('cheque_date', 'ASC');

        $credit_note_result = CreditNote::select(DB::raw("DATE_FORMAT(credit_note_date,'%d/%m/%Y') as date,'Credit Note' as transaction,credit_note_number as detail, '0.00' as amount, grand_total as payment,3 as status, created_at"));

        if ($request->lob_id) {
            $credit_note_result->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('credit_note_date', [$startdate, $enddate]);
        } else {
            $credit_note_result->where('customer_id', $customer_id)->whereBetween('credit_note_date', [$startdate, $enddate]);
        }
        $credit_note = $credit_note_result->orderBy('created_at', 'ASC'); //orderBy('credit_note_date', 'ASC');

        $balanceStatement_result = DebitNote::select(DB::raw("DATE_FORMAT(debit_note_date,'%d/%m/%Y') as date,
                                                            (CASE
                                                                WHEN is_debit_note=1  THEN 'Debit Note'
                                                                WHEN is_debit_note=0  THEN
                                                                    (select t2.item_name from debit_note_listingfee_shelfrent_rebatediscount_details t2 where t2.debit_note_id = debit_notes.id)
                                                            END ) as transaction ,
                                                            debit_note_number as detail,
                                                            '0.00' as amount,
                                                            grand_total as payment,
                                                            4 as status, created_at"));

        if ($request->lob_id) {
            $balanceStatement_result->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('debit_note_date', [$startdate, $enddate]);
        } else {
            $balanceStatement_result->where('customer_id', $customer_id)->whereBetween('debit_note_date', [$startdate, $enddate]);
        }

        $balanceStatement_report = $balanceStatement_result->orderBy('debit_note_date', 'ASC')
            ->union($invoices)
            ->union($collections)
            ->union($credit_note)
            ->orderBy('created_at', 'ASC') //orderBy('c_date', 'ASC')
            ->get();

        //convert normal array into object array
        $openingBalance = json_decode(json_encode($openingBalance), false);

        $balanceStatement_report->splice(0, 0, [$openingBalance]);

        if (!is_object($balanceStatement_report)) {
            return prepareResult(false, [], "Report is empty", "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        $dataArray['CustomerStatement'] = $balanceStatement_report;

        $dataArray['userDetails'] = $userDetails;

        $columns = $request->columns;
        if (is_object($balanceStatement_report)) {
            foreach ($balanceStatement_report as $key => $val) {
                $balanceStatement_report[$key]->firstname = $userDetails->firstname;
                $balanceStatement_report[$key]->lastname  = $userDetails->lastname;
                if (count($columns) > 0) {

                    if (!in_array('firstname', $columns)) {
                        unset($balanceStatement_report[$key]->firstname);
                    }
                    if (!in_array('lastname', $columns)) {
                        unset($balanceStatement_report[$key]->lastname);
                    }

                    if (!in_array('date', $columns)) {
                        unset($balanceStatement_report[$key]->date);
                    }
                    if (!in_array('transaction', $columns)) {
                        unset($balanceStatement_report[$key]->transaction);
                    }
                    if (!in_array('detail', $columns)) {
                        unset($balanceStatement_report[$key]->detail);
                    }
                    if (!in_array('amount', $columns)) {
                        unset($balanceStatement_report[$key]->amount);
                    }
                    if (!in_array('payment', $columns)) {
                        unset($balanceStatement_report[$key]->payment);
                    }
                } else {
                    unset($balanceStatement_report[$key]->date);
                    unset($balanceStatement_report[$key]->transaction);
                    unset($balanceStatement_report[$key]->detail);
                    unset($balanceStatement_report[$key]->amount);
                    unset($balanceStatement_report[$key]->payment);
                }
                unset($balanceStatement_report[$key]->status);
                unset($balanceStatement_report[$key]->created_at);
            }
        }

        if ($request->export == 0) {
            return prepareResult(true, $balanceStatement_report, [], "Customer Statement Report", $this->success);
        } else {
            Excel::store(new CustomerStatementReportExport($balanceStatement_report, $columns), 'customer_statement_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/customer_statement_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    /**
     * order report.
     *
     * @return \Illuminate\Http\Response
     */

    public function order_report(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $load_sheet_query = Order::select(
            'id',
            'customer_id',
            'depot_id',
            'order_type_id',
            'salesman_id',
            'order_number',
            'order_date',
            'due_date',
            'delivery_date',
            'payment_term_id',
            'total_qty',
            'total_gross',
            'total_discount_amount',
            'total_net',
            'total_vat',
            'total_excise',
            'grand_total',
            'current_stage',
            'lob_id'
        )
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'orderType:id,name,description',
                'paymentTerm:id,name,number_of_days',
                'depot:id,depot_name,depot_code',
                'lob:id,name',
                'orderDetails:id,order_id,item_id,item_uom_id,discount_id,is_free,is_item_poi,promotion_id,item_qty,item_price,item_gross,item_discount_amount,item_net,item_vat,item_excise,item_grand_total,delivered_qty,open_qty,order_status',
                'orderDetails.item:id,item_name,item_code',
                'orderDetails.itemUom:id,name,code',
                'orderDetails.pricediscopromoplan:id,name',
                'orderDetails.pricediscopromoplan_discount:id,name'
            )
            ->whereBetween('order_date', [$start_date, $end_date]);

        $load_sheet = $load_sheet_query->get();

        $columns = $request->columns;

        if ($request->export == 0) {
            return prepareResult(true, $load_sheet, [], "order report listing", $this->success);
        } else {

            $file_name = "order_report.xlsx";

            $columns = array(
                'Order Number',
                'Order Date',
                'Due Date',
                'Delivery Date',
                'Total Qty',
                'Total Gross',
                'Total Discount Amount',
                'Total Net',
                'Total Vat',
                'Total Excise',
                'Grand Total',
                'Current Stage',
                'Customer First Name Last Name',
                'Customer Code',
                'Salesman First Name Last Name',
                'Salesman Code',
                'Order Type',
                'Payment Term',
                'Depot',
                'Lob',
                'Item',
                'Item Code',
                'Item Uom ',
                'Item Uom Code',
                'Price Disco Promo Plan',
                'Price Disco Promo Plan Discount',
                'Item Qty',
                'Item Price',
                'Item Gross',
                'Item Discount Amount',
                'Item Net',
                'Item Vat',
                'Item Excise',
                'Item Grand Total',
                'Delivered Qty',
                'Open Qty',
                'Order Status',
            );

            $load_collection = new Collection();
            foreach ($load_sheet as $key => $loadSheet) {
                if (count($loadSheet->orderDetails)) {
                    foreach ($loadSheet->orderDetails as $dkey => $detail) {
                        $customer_name                = "N/A";
                        $customer_code                = "N/A";
                        $salesman_name                = "N/A";
                        $salesman_code                = "N/A";
                        $order_type                   = "N/A";
                        $payment_term                 = "N/A";
                        $depot                        = "N/A";
                        $lob                          = "N/A";
                        $item_name                    = "N/A";
                        $item_code                    = "N/A";
                        $item_uom_name                = "N/A";
                        $item_uom_code                = "N/A";
                        $pricediscopromoplan          = "N/A";
                        $pricediscopromoplan_discount = "N/A";

                        if (is_object($load_sheet[$key])) {

                            if (is_object($load_sheet[$key]->customer)) {
                                $customer_name = $load_sheet[$key]->customer->firstname . " " . $load_sheet[$key]->customer->lastname;
                                $customer_code = (!empty($load_sheet[$key]->customer->customerInfo->customer_code) ? $load_sheet[$key]->customer->customerInfo->customer_code : 'N/A');
                            }

                            if (is_object($load_sheet[$key]->salesman)) {
                                $salesman_name = $load_sheet[$key]->salesman->firstname . " " . $load_sheet[$key]->salesman->lastname;
                                $salesman_code = (!empty($load_sheet[$key]->salesman->salesmanInfo->salesman_code) ? $load_sheet[$key]->salesman->salesmanInfo->salesman_code : 'N/A');
                            }

                            if (is_object($load_sheet[$key]->orderType)) {
                                $order_type = $load_sheet[$key]->orderType->name;
                            }

                            if (is_object($load_sheet[$key]->paymentTerm)) {
                                $payment_term = $load_sheet[$key]->paymentTerm->name;
                            }

                            if (is_object($load_sheet[$key]->depot)) {
                                $depot = $load_sheet[$key]->depot->depot_name;
                            }

                            if (is_object($load_sheet[$key]->lob)) {
                                $lob = $load_sheet[$key]->lob->name;
                            }

                            if (is_object($detail->Item)) {
                                $item_name = $detail->Item->item_name;
                                $item_code = $detail->Item->item_code;
                            }

                            if (is_object($detail->itemUom)) {
                                $item_uom_name = $detail->itemUom->name;
                                $item_uom_code = $detail->itemUom->code;
                            }

                            if (is_object($detail->pricediscopromoplan)) {
                                $pricediscopromoplan = $detail->pricediscopromoplan->name;
                            }
                            if (is_object($detail->pricediscopromoplan_discount)) {
                                $pricediscopromoplan_discount = $detail->pricediscopromoplan_discount->name;
                            }

                            $load_collection->push((object) [
                                'order_number'                 => $load_sheet[$key]->order_number,
                                'order_date'                   => $load_sheet[$key]->order_date,
                                'due_date'                     => $load_sheet[$key]->due_date,
                                'delivery_date'                => $load_sheet[$key]->delivery_date,
                                'total_qty'                    => $load_sheet[$key]->total_qty,
                                'total_gross'                  => $load_sheet[$key]->total_gross,
                                'total_discount_amount'        => $load_sheet[$key]->total_discount_amount,
                                'total_net'                    => $load_sheet[$key]->total_net,
                                'total_vat'                    => $load_sheet[$key]->total_vat,
                                'total_excise'                 => $load_sheet[$key]->total_excise,
                                'grand_total'                  => $load_sheet[$key]->grand_total,
                                'current_stage'                => (!empty($load_sheet[$key]->current_stage) ? $load_sheet[$key]->current_stage : 'N/A'),
                                'customer'                     => $customer_name,
                                'customer_code'                => $customer_code,
                                'salesman'                     => $salesman_name,
                                'salesman_code'                => $salesman_code,
                                'order_type'                   => $order_type,
                                'payment_term'                 => $payment_term,
                                'depot'                        => $depot,
                                'lob'                          => $lob,
                                'item_name'                    => $item_name,
                                'item_code'                    => $item_code,
                                'item_uom_name'                => $item_uom_name,
                                'item_uom_code'                => $item_uom_code,
                                'pricediscopromoplan'          => $pricediscopromoplan,
                                'pricediscopromoplan_discount' => $pricediscopromoplan_discount,
                                'item_qty'                     => (!empty($detail->item_qty) ? $detail->item_qty : 0),
                                'item_price'                   => (!empty($detail->item_price) ? $detail->item_price : 0),
                                'item_gross'                   => (!empty($detail->item_gross) ? $detail->item_gross : 0),
                                'item_discount_amount'         => (!empty($detail->item_discount_amount) ? $detail->item_discount_amount : 0),
                                'item_net'                     => (!empty($detail->item_net) ? $detail->item_net : 0),
                                'item_vat'                     => (!empty($detail->item_vat) ? $detail->item_vat : 0),
                                'item_excise'                  => (!empty($detail->item_excise) ? $detail->item_excise : 0),
                                'item_grand_total'             => (!empty($detail->item_grand_total) ? $detail->item_grand_total : 0),
                                'delivered_qty'                => (!empty($detail->delivered_qty) ? $detail->delivered_qty : 0),
                                'open_qty'                     => (!empty($detail->open_qty) ? $detail->open_qty : 0),
                                'order_status'                 => (!empty($detail->order_status) ? $detail->order_status : 'N/A'),
                            ]);
                        }
                    }
                }
            }

            Excel::store(new OrderReportExport($load_collection, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    /**
     * product Summary Customer Sales report.
     *
     * @return \Illuminate\Http\Response
     */

    public function productSummaryCustomerSales(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $invoices_result = Invoice::select('invoices.id', 'invoices.salesman_id', 'invoices.customer_id', 'invoices.invoice_date')
            ->with([
                'invoicedetail',
                'customerInfoDetails:id,user_id,customer_code',
                'customerInfoDetails.user:id,firstname,lastname',
            ]);
        $invoices_result->whereBetween('invoices.invoice_date', [$start_date, $end_date]);

        if ($request->customer_id) {
            $invoices_result->where('customer_id', $request->customer_id);
        }

        if ($request->lob) {
            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->lob);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $invoices_result->whereIn('customer_id', $ids);
        }

        if ($request->supervisor_id) {
            $salesman_info_query = SalesmanInfo::select('user_id')->where('salesman_supervisor', $request->supervisor_id)->get();
            if (count($salesman_info_query)) {
                $supervisor_ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $invoices_result->whereIn('customer_id', $supervisor_ids);
        }

        if ($request->region_id) {
            $region_info_query = SalesmanInfo::select('user_id')->where('region_id', $request->region_id)->get();
            if (count($region_info_query)) {
                $user_ids = $region_info_query->pluck('user_id')->toArray();
            }
            $invoices_result->whereIn('customer_id', $user_ids);
        }

        /* if ($request->item_category) {
            $item_info_query = Item::select('id')->where('item_major_category_id', $request->item_category)->get();
            if (count($item_info_query)) {
            $item_ids = $item_info_query->pluck('id')->toArray();
            }
            $invoices_result->whereIn('item_id', $item_ids);
            }  */
        if ($request->salesman_id) {
            $invoices_result->where('salesman_id', $request->salesman_id);
        }

        $productSummary = $invoices_result->get();

        $credit_note_result = CreditNote::select('credit_notes.id', 'credit_notes.salesman_id', 'credit_notes.customer_id', 'credit_notes.credit_note_date')
            ->with([
                'creditnotedetail',
                'customerInfoDetails:id,user_id,customer_code',
                'customerInfoDetails.user:id,firstname,lastname',
            ]);
        $credit_note_result->whereBetween('credit_notes.credit_note_date', [$start_date, $end_date]);

        if ($request->customer_id) {
            $credit_note_result->where('customer_id', $request->customer_id);
        }

        if ($request->lob) {
            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->lob);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $credit_note_result->whereIn('customer_id', $ids);
        }

        if ($request->supervisor_id) {
            $salesman_info_query = SalesmanInfo::select('user_id')->where('salesman_supervisor', $request->supervisor_id)->get();
            if (count($salesman_info_query)) {
                $supervisor_ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $credit_note_result->whereIn('customer_id', $supervisor_ids);
        }

        if ($request->region_id) {
            $region_info_query = SalesmanInfo::select('user_id')->where('region_id', $request->region_id)->get();
            if (count($region_info_query)) {
                $user_ids = $region_info_query->pluck('user_id')->toArray();
            }
            $credit_note_result->whereIn('customer_id', $user_ids);
        }

        /* if ($request->item_category) {
            $item_info_query = Item::select('id')->where('item_major_category_id', $request->item_category)->get();
            if (count($item_info_query)) {
            $item_ids = $item_info_query->pluck('id')->toArray();
            }
            $credit_note_result->whereIn('item_id', $item_ids);
            } */

        if ($request->salesman_id) {
            $credit_note_result->where('salesman_id', $request->salesman_id);
        }
        $credire_note = $credit_note_result->get();

        $merge_array = array_merge($productSummary->toArray(), $credire_note->toArray());

        $tot_creditnote_amount    = 0;
        $tot_sales_amount         = 0;
        $tot_invoice_net_amount   = 0;
        $cat_total_sales          = 0;
        $cat_total_retuen         = 0;
        $cat_total_return_percent = 0;
        $cat_total_net            = 0;

        foreach ($merge_array as $key => $Detail_array) {

            if (!empty($Detail_array['invoicedetail']) || !empty($Detail_array['creditnotedetail'])) {
                if (!empty($Detail_array['invoicedetail'])) {
                    foreach ($Detail_array['invoicedetail'] as $dkey => $detail) {
                        $tot_sales_amount       = $detail['Total_invoice_sales'];
                        $tot_invoice_net_amount = $detail['Total_invoice_net'];
                        $cat_total_sales        = $cat_total_sales + $tot_sales_amount;
                        $cat_total_net          = $cat_total_net + $detail['Total_invoice_net'];
                    }
                } else {
                    foreach ($Detail_array['creditnotedetail'] as $cre_key => $cre_detail) {
                        $tot_creditnote_amount = $cre_detail['Total_creditnote'];
                        $cat_total_retuen      = $cat_total_retuen + $tot_creditnote_amount;
                    }
                }

                $return_percent           = ($tot_creditnote_amount / $tot_sales_amount) * 100;
                $cat_total_return_percent = ($cat_total_retuen / $cat_total_sales) * 100;

                if (!empty($Detail_array['invoicedetail']) || !empty($Detail_array['creditnotedetail'])) {
                    if (!empty($Detail_array['invoicedetail'])) {
                        foreach ($Detail_array['invoicedetail'] as $dkey => $detail) {
                            $merge_array[$key]['invoicedetail'][$dkey]['return_percent']   = $return_percent;
                            $merge_array[$key]['invoicedetail'][$dkey]['tot_sales_amount'] = $tot_sales_amount;
                            $merge_array[$key]['invoicedetail'][$dkey]['tot_net_amount']   = $tot_invoice_net_amount;
                            $merge_array[$key]['invoicedetail'][$dkey]['cat_total_sales']  = $cat_total_sales;
                            $merge_array[$key]['invoicedetail'][$dkey]['cat_total_net']    = $cat_total_net;
                        }
                    } else {
                        foreach ($Detail_array['creditnotedetail'] as $cre_key => $cre_detail) {
                            $merge_array[$key]['creditnotedetail'][$cre_key]['tot_return']            = $tot_creditnote_amount;
                            $merge_array[$key]['creditnotedetail'][$cre_key]['return_percent']        = $return_percent;
                            $merge_array[$key]['creditnotedetail'][$dkey]['cat_total_retuen']         = $cat_total_retuen;
                            $merge_array[$key]['creditnotedetail'][$dkey]['cat_total_return_percent'] = $cat_total_return_percent;
                        }
                    }
                }
            }
        }

        $columns = $request->columns;

        if ($request->export == 0) {
            return prepareResult(true, $merge_array, [], "Product Summary By Customer Sales report", $this->success);
        } else {
            $columns = array(
                'Customer First Name',
                'Customer Last Name',
                'Customer Code',
                'Item Major Category',
                'Category Total Sales',
                'Category Total Net',
                'Category Total Retrun',
                'Category Total Retrun_%',
                'Item Name',
                'Total Sales',
                'Total Net',
                'Total Retrun',
                'Retrun_%',
            );

            $product_summary_collection = new Collection();
            foreach ($merge_array as $key => $Detail_array) {

                $user_first_name = 'N/A';
                $user_last_name  = 'N/A';
                $customer_code   = 'N/A';

                if (!empty($Detail_array['invoicedetail'])) {

                    if (!empty($Detail_array['customer_info_details'])) {
                        $customer_code = $Detail_array['customer_info_details']['customer_code'];
                    }

                    if (!empty($Detail_array['customer_info_details']['user'])) {
                        $user_first_name = $Detail_array['customer_info_details']['user']['firstname'];
                    }
                    if (!empty($Detail_array['customer_info_details']['user'])) {
                        $user_last_name = $Detail_array['customer_info_details']['user']['lastname'];
                    }
                    if (!empty($Detail_array['invoicedetail'])) {
                        $item_major_category = $Detail_array['invoicedetail'][0]['item']['item_major_category']['name'];
                        $item_name           = $Detail_array['invoicedetail'][0]['item']['item_name'];
                    }

                    $product_summary_collection->push((object) [
                        'user_first_name'               => $user_first_name,
                        'user_last_name'                => $user_last_name,
                        'customer_code'                 => $customer_code,
                        'item_major_category'           => $item_major_category,
                        'categories_tot_sales'          => $cat_total_sales,
                        'categories_tot_net'            => $cat_total_net,
                        'categories_tot_return'         => $cat_total_retuen,
                        'categories_tot_return_percent' => $cat_total_return_percent,
                        'item_name'                     => $item_name,
                        'Total_invoice_sales'           => $tot_sales_amount,
                        'Total_invoice_net'             => $tot_invoice_net_amount,
                        'Total_creditnote'              => $tot_creditnote_amount,
                        'return_percesent'              => $return_percent,
                    ]);
                }
            }

            Excel::store(new ProductSummaryCustomerSalesReportExport($product_summary_collection, $columns), 'Product_Summary_By_Customer_Sales_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . 'Product_Summary_By_Customer_Sales_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }

        /* end of the code  ---------------------  */
    }

    /**
     * Van Customer Report.
     *
     * @return \Illuminate\Http\Response
     */

    public function vanCustomerReport(Request $request)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $customer_query = CustomerInfo::select('id', 'user_id', 'route_id', 'region_id', 'customer_type_id', 'customer_code', 'customer_phone', 'customer_address_1')
            ->with([
                'user:id,firstname,lastname',
                'customerType:id,customer_type_name',
            ]);
        $customer_query->whereBetween('created_at', [$start_date, $end_date]);

        if ($request->customer_id) {
            $customer_query->where('user_id', $request->customer_id);
        }

        if ($request->lob) {
            $lob = $request->lob;
            $customer_query->whereHas('customerlob', function ($query) use ($lob) {
                $query->where('lob_id', $lob);
            });
        }

        if ($request->supervisor_id) {
            $salesman_info_query = SalesmanInfo::select('user_id')->where('salesman_supervisor', $request->supervisor_id)->get();
            if (count($salesman_info_query)) {
                $supervisor_ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $customer_query->whereIn('user_id', $supervisor_ids);
        }

        if ($request->route_id) {
            $customer_query->where('route_id', $request->route_id);
        }

        if ($request->region_id) {
            $customer_query->where('region_id', $request->region_id);
        }

        $customer_result = $customer_query->get();

        $columns = $request->columns;

        if ($request->export == 0) {
            return prepareResult(true, $customer_result, [], "Van Customer Sales report", $this->success);
        } else {

            $file_name = 'Van_Customer_report.xlsx';

            $columns = array(
                'Customer Code',
                'Customer Name',
                'Customer Type',
                'Address',
                'Contact',
            );

            $load_collection = new Collection();
            foreach ($customer_result as $key => $loadSheet) {
                $customer_code = "N/A";
                $customer_name = "N/A";
                $customer_type = "N/A";
                $address       = "N/A";
                $contact       = "N/A";

                if (is_object($customer_result[$key])) {
                    if (is_object($customer_result[$key]->user)) {
                        $customer_name = $customer_result[$key]->user->firstname . " " . $customer_result[$key]->user->lastname;
                    }

                    if (is_object($customer_result[$key]->customerType)) {
                        $customer_type = $customer_result[$key]->customerType->customer_type_name;
                    }

                    $load_collection->push((object) [

                        'customer_code' => $customer_result[$key]->customer_code,
                        'customer_name' => $customer_name,
                        'customer_type' => $customer_type,
                        'Address'       => $customer_result[$key]->customer_address_1,
                        'Contact'       => $customer_result[$key]->customer_phone,
                    ]);
                }
            }

            Excel::store(new VanCustomerReportExport($load_collection, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function carryOverReport(Request $request)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $Carryover = Transaction::with(array('salesman' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'salesman:id,firstname,lastname',
                'salesman.salesmaninfo:id,user_id,salesman_code',
                'route:id,route_code,route_name',
            )
            ->with(['transactiondetail' => function ($query) {
                $query->where('closing_qty', '<>', '0.0');
            }, 'transactiondetail.item'])
            ->whereBetween('transaction_date', [$start_date, $end_date]);

        if ($request->supervisor_id) {
            $salesman_info_query = SalesmanInfo::select('user_id')->where('salesman_supervisor', $request->supervisor_id)->get();
            if (count($salesman_info_query)) {
                $supervisor_ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $Carryover->whereIn('salesman_id', $supervisor_ids);
        }

        if ($request->route_id) {
            $Carryover->where('route_id', $request->route_id);
        }

        if ($request->region_id) {
            $region_info_query = SalesmanInfo::select('user_id')->where('region_id', $request->region_id)->get();
            if (count($region_info_query)) {
                $user_ids = $region_info_query->pluck('user_id')->toArray();
            }
            $Carryover->whereIn('salesman_id', $user_ids);
        }

        if ($request->lob) {
            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->lob);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $Carryover->whereIn('salesman_id', $ids);
        }

        $Carryover_result = $Carryover->get();

        $columns = $request->columns;

        if ($request->export == 0) {
            return prepareResult(true, $Carryover_result, [], "Carry over report", $this->success);
        } else {
            $file_name = "Carry_over_report.xlsx";

            $columns = array(
                'Carry over No',
                'Route Code',
                'Route Name',
                'Item Code',
                'Item Name',
                'Qty',
            );

            $load_collection = new Collection();
            foreach ($Carryover_result as $key => $loadSheet) {
                if (count($loadSheet->transactiondetail)) {
                    foreach ($loadSheet->transactiondetail as $dkey => $detail) {
                        $carry_over_no = "N/A";
                        $route_code    = "N/A";
                        $route_name    = "N/A";
                        $item_name     = "N/A";
                        $item_code     = "N/A";
                        $qty           = "N/A";

                        if (is_object($Carryover_result[$key])) {

                            $carry_over_no = $Carryover_result[$key]->id;

                            if (is_object($Carryover_result[$key]->route)) {
                                $route_code = $Carryover_result[$key]->route->route_code;
                                $route_name = $Carryover_result[$key]->route->route_name;
                            }

                            if (is_object($detail->item)) {
                                $item_name = $detail->Item->item_name;
                                $item_code = $detail->Item->item_code;
                            }

                            $qty = $detail->closing_qty;

                            $load_collection->push((object) [
                                'carry_over_no' => $carry_over_no,
                                'route_code'    => $route_code,
                                'route_name'    => $route_name,
                                'item_name'     => $item_name,
                                'item_code'     => $item_code,
                                'qty'           => (!empty($qty) ? $qty : 0),
                            ]);
                        }
                    }
                }
            }

            Excel::store(new CarryOverReportExport($load_collection, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function dailyFieldActivityReport(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $salesData = SalesmanInfo::select('id', 'created_at', 'user_id', 'salesman_code')->with('customerVisits', 'salesmanInvoices', 'user', 'user.customerVisitBySalesman', 'user.salesmanInvoices', 'user.creditNoteSalesman', 'user.collectionSalesmans');

        if ($request->has('region') && $request->region != null) {
            $region = $request->region;
            $salesData  = $salesData->where('region_id', $region);
        }

        if ($request->has('division') && $request->division != null) {
            $division  = $request->division;
            $salesData = $salesData->whereHas('salesmanlob', function ($q) use ($division) {
                $q->where('lob_id', $division);
            });
        }
        if ($request->has('supervisor') && $request->supervisor != null) {
            $salesData = $salesData->where('salesman_supervisor', $request->supervisor);
        }

        if ($request->has('salesman') && $request->salesman != null) {
            $salesman  = $request->salesman;
            $salesData = $salesData->where('user_id', $salesman);
        }
        $salesData = $salesData->get();

        $dateRange           = CarbonPeriod::create($start_date, $end_date);
        $i                   = 1;
        $get_new_data        = [];
        $getDailyVisitReport = new Collection();
        foreach ($dateRange as $key => $date) {
            $get_date = $date->format(\DateTime::ATOM);
            $get_date = Carbon::parse($get_date)->format('Y-m-d');
            foreach ($salesData as $s_key => $_salesman) {
                $getDailyVisitReport->push((object) [
                    'date'                => $get_date,
                    'salesman_name'       => $_salesman->user->firstname . ' ' . $_salesman->user->lastname,
                    'salesman_code'       => $_salesman->salesman_code,
                    'Total_visited_shop'  => $_salesman->customerVisits()->whereDate('created_at', '=', $get_date)->count(),
                    'Total_invoice_shop'  => $_salesman->salesmanInvoices()->whereDate('created_at', '=', $get_date)->groupBy('customer_id')->count(),
                    'total_cash_sales'    => $_salesman->salesmanInvoices()->whereDate('created_at', '=', $get_date)->where('order_type_id', 1)->sum('grand_total'),
                    'total_credit_sales'  => $_salesman->salesmanInvoices()->whereDate('created_at', '=', $get_date)->where('order_type_id', 2)->sum('grand_total'),
                    'total_credit_return' => $_salesman->creditNoteSalesman()->whereDate('created_at', '=', $get_date)->sum('grand_total'),
                    'cash_collection'     => $_salesman->collectionSalesmans()->whereDate('created_at', '=', $get_date)->where('payemnt_type', 1)->sum('invoice_amount'),
                    'cheque_collection'   => $_salesman->collectionSalesmans()->where('payemnt_type', 2)->sum('invoice_amount'),
                ]);
            }
        }
        $columns = ['Date', 'Salesman name', 'Salesman code', 'Total_visited_shop', 'Total_invoice_shop', 'Total cash sales', 'Total credit sales', 'Total Credit return', 'Cash collection', 'Cheque collection'];

        if ($request->export == 0) {
            return prepareResult(true, $getDailyVisitReport, [], "Salesman daily field activity data listing", $this->success);
        } else {
            Excel::store(new DailyFieldActivityReportExport($getDailyVisitReport, $columns), 'daily_field_activity_report.' . $request->export_type);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/daily_field_activity_report.' . $request->export_type));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }

        // old code comment by mahesh
        // if (!$this->isAuthorized) {
        //     return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        // }

        // $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        // $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        // $invoice_query = DB::table('salesman_infos')->select(
        //     'salesman_infos.id as salesman_infos_id',
        //     'salesman_infos.user_id',
        //     'salesman_infos.salesman_code',
        //     'customer_visits.date',
        //     'invoices.id as invoices_id',
        //     'invoices.salesman_id as invoice_salesman_id',
        //     'collections.id as collections_id'
        // )
        //     ->leftJoin('users', 'users.id', '=', 'salesman_infos.user_id')
        //     ->leftJoin('customer_visits', 'customer_visits.salesman_id', '=', 'salesman_infos.user_id')
        //     ->leftJoin('invoices', 'invoices.salesman_id', '=', 'customer_visits.salesman_id')

        //     ->leftJoin('collections', 'collections.salesman_id', '=', 'invoices.salesman_id')

        //     ->selectRaw("COUNT(distinct customer_visits.id) as Total_visited_shop,
        //                         CONCAT(users.firstname, ' ', users.lastname) as salesman_name,
        //                         COUNT(distinct invoices.id) as Total_invoice_shop,

        //                         COUNT( distinct (CASE WHEN collections.payemnt_type=1 THEN 1 ELSE 0 END)) as  cash_collection,
        //                         COUNT( distinct (CASE WHEN collections.payemnt_type=2 THEN 1 ELSE 0 END)) as  cheque_collection

        //                         ")
        //     ->whereNotNull('invoices.salesman_id')
        //     ->whereNull('invoices.deleted_at')
        //     ->whereNull('collections.deleted_at')

        //     ->groupBy('customer_visits.salesman_id')
        //     ->groupBy('customer_visits.customer_id')

        //     ->groupBy('invoices.invoice_date')
        //     ->groupBy('collections.created_at')

        //     ->groupBy('customer_visits.date');

        // $invoices = $invoice_query->get();

        // return prepareResult(true, $invoices, [], "Carry over report", $this->success);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function customerPaymentReport(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');
        
        $customer = DB::table('customer_infos')
            ->leftjoin('users', 'customer_infos.user_id', '=', 'users.id')
            ->leftjoin('invoices', 'customer_infos.user_id', '=', 'invoices.customer_id')
            ->leftJoin("customer_lobs", "customer_infos.id", '=', "customer_lobs.customer_info_id")
            ->leftJoin("credit_notes", "users.id", '=', "credit_notes.customer_id")
            ->where('invoices.invoice_date','!=',null)
            ->where('invoices.pending_credit','!=',null)
            ->where('credit_notes.pending_credit','!=',null)
            ->select(
                'customer_infos.customer_code',
                DB::raw("concat(users.firstname,' ',users.lastname) AS 'customer_fullname'"),
                'customer_infos.id',
                'invoices.invoice_date',
                'invoices.pending_credit as invoice_pending_credit',
                'credit_notes.pending_credit as credit_notes_pending_credit',
                'customer_infos.route_id',
                'customer_lobs.lob_id',
                DB::raw("YEAR(invoices.invoice_date) as year"),
                DB::raw("MONTH(invoices.invoice_date) as month"),
                DB::raw("MONTHNAME(invoices.invoice_date) as monthname"),
                DB::raw("sum(invoices.pending_credit) as invoice_total"),
                DB::raw("sum(credit_notes.pending_credit) as credit_notes_total"),
            );

        if ($start_date != '' && $end_date != '') {
            $customer = $customer->whereDate('invoices.invoice_date', '>=', $start_date)
                ->whereDate('invoices.invoice_date', '<=', $end_date);
        }
        
        if (isset($request->route) && count($request->route) >= 1) {
            $customer = $customer->whereIn('customer_lobs.route_id', $request->route);
        }
        
        if (isset($request->division) && count($request->division) >= 1) {
            $customer = $customer
                ->whereIn('customer_lobs.lob_id', $request->division);
        }
        $customer = $customer->groupBy(['customer_infos.id','month','year'])->orderBy('customer_infos.id', 'ASC');
        $customer = $customer->get();

        $columns = isset($request->columns)?$request->column:[];
        $record = [];
        $final_record = [];
        $count = 0;
        $on_account = 0.00;
        $prior = 0.00;
        $start_string_date = strtotime($start_date);
        $end_string_date = strtotime($end_date);
        $name_of_current_month = date('F', strtotime(date('Y-m-d', $end_string_date)));
        $name_of_current_year = date('Y', strtotime(date('Y-m-d', $end_string_date)));

        if ($start_string_date <= strtotime(date('Y-m-d', strtotime('first day of -1 month',$end_string_date)))) {
           $name_of_last_month = date('F', strtotime(date('Y-m-d', strtotime('first day of -1 month',$end_string_date))));
           $name_of_last_year = date('Y', strtotime(date('Y-m-d', strtotime('first day of -1 month',$end_string_date))));
        }

        if($start_string_date <= strtotime(date('Y-m-d', strtotime('first day of -2 month',$end_string_date)))){
            $name_of_second_last_month = date('F', strtotime(date('Y-m-d', strtotime('first day of -2 month',$end_string_date))));
            $name_of_second_last_year = date('Y', strtotime(date('Y-m-d', strtotime('first day of -2 month',$end_string_date))));
        }

        if($start_string_date <= strtotime(date('Y-m-d', strtotime('first day of -3 month',$end_string_date)))){
            $name_of_third_last_month = date('F', strtotime(date('Y-m-d', strtotime('first day of -3 month',$end_string_date))));
            $name_of_third_last_year = date('Y', strtotime(date('Y-m-d', strtotime('first day of -3 month',$end_string_date))));
        }
        
        if (is_object($customer)) {
            foreach ($customer as $key => $val) {
                if ($key != 0 && $customer[$key]->id != $customer[$key-1]->id) {
                    $count++;
                    $on_account = 0.00;
                    $prior = 0.00;
                }else{
                    $on_account = $on_account + $val->credit_notes_total;
                }
                $record[$count]['customer_code'] = $val->customer_code;
                $record[$count]['customer_name'] = $val->customer_fullname;
                $record[$count]['On_Account'] = $on_account;
                $record[$count]['year'] = $val->year;

                if ($val->monthname == $name_of_current_month) {
                    $record[$count][$name_of_current_month] =  $val->invoice_total;
                } elseif(isset($name_of_last_month) && $val->monthname == $name_of_last_month){
                    $record[$count][$name_of_last_month] = $val->invoice_total;
                } elseif(isset($name_of_second_last_month) && $val->monthname == $name_of_second_last_month){
                    $record[$count][$name_of_second_last_month] = $val->invoice_total;
                } elseif(isset($name_of_third_last_month) && $val->monthname == $name_of_third_last_month){
                    $record[$count][$name_of_third_last_month] = $val->invoice_total;
                }else {
                    $prior = $prior + $val->invoice_total;
                    $record[$count]['prior'] = $prior;
                }
            }

            foreach ($record as $key => $data) {
                $total_of_record = 0; 
                $final_record[$key]['customer_code'] = $data['customer_code'];
                $final_record[$key]['customer_name'] = $data['customer_name'];

                $final_record[$key]['month'][]['key'] = $name_of_current_month.' '.$name_of_current_year;
                $final_record[$key]['month'][0]['data'] = (!empty($data[$name_of_current_month])) ? $data[$name_of_current_month] :'0';
                $total_of_record = $total_of_record + $final_record[$key]['month'][0]['data'];

                if (isset($name_of_last_month)) {
                    $final_record[$key]['month'][]['key'] = $name_of_last_month.' '.$name_of_last_year;
                    $final_record[$key]['month'][1]['data'] = (!empty($data[$name_of_last_month])) ? $data[$name_of_last_month] :'0';
                    $total_of_record = $total_of_record + $final_record[$key]['month'][1]['data'];
                }

                if (isset($name_of_second_last_month)) {
                    $final_record[$key]['month'][]['key'] = $name_of_second_last_month.' '.$name_of_second_last_year;
                    $final_record[$key]['month'][2]['data'] = (!empty($data[$name_of_second_last_month])) ? $data[$name_of_second_last_month] :'0';
                    $total_of_record = $total_of_record + $final_record[$key]['month'][2]['data'];
                }

                if (isset($name_of_third_last_month)) {
                    $final_record[$key]['month'][]['key'] = $name_of_third_last_month.' '.$name_of_third_last_year;
                    $final_record[$key]['month'][3]['data'] = (!empty($data[$name_of_third_last_month])) ? $data[$name_of_third_last_month] :'0';
                    $total_of_record = $total_of_record + $final_record[$key]['month'][3]['data'];
                }

                $final_record[$key]['prior'] = (!empty($data[$prior])) ? $data[$prior] :'0';
                $final_record[$key]['on_account'] = (!empty($data['On_Account'])) ? $data['On_Account'] :'0';

                $final_record[$key]['total'] = $total_of_record + $final_record[$key]['prior'] - $final_record[$key]['on_account'];

                if (count($columns) > 0) {
                    if (!in_array('customer_code', $columns)) {
                        unset($final_record[$key]['customer_code']);
                    }
                    if (!in_array('customer_name', $columns)) {
                        unset($final_record[$key]['customer_name']);
                    }
                    if (!in_array('prior', $columns)) {
                        unset($final_record[$key]['prior']);
                    } 
                    if (!in_array('on_account', $columns)) {
                        unset($final_record[$key]['on_account']);
                    }
                    if (!in_array('total', $columns)) {
                        unset($final_record[$key]['total']);
                    }
                }
            }
        }
        if ($request->export == 0) {
            return prepareResult(true, $final_record, [], "Customer Payment Report", $this->success);
        } else {
            $file_name = 'customer_payment_report.' . $request->export_type;
            Excel::store(new CustomerReportExport($final_record, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/'.$file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }
    }


    /**
     * Display a unload report of the salesman.
     *
     * @return \Illuminate\Http\Response
     */

    public function unloadReport(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $date = Carbon::parse($request->start_date)->format('Y-m-d');
        $salsemanId = $request->salseman_id;

        $record = DB::table('transactions')
            ->leftjoin('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->leftjoin('items', 'items.id', '=', 'transaction_details.item_id')
            ->leftjoin('users', 'users.id', '=', 'transactions.salesman_id')
            ->select(
                'items.id',
                'items.item_code',
                'items.item_name',
                'items.item_description',
                DB::raw("sum(transaction_details.load_qty) as total_load_qty"),
                DB::raw("sum(transaction_details.sales_qty) as total_sales_qty"),
                DB::raw("sum(transaction_details.return_qty) as total_return_qty"),
                DB::raw("sum(transaction_details.bad_retun_qty) as total_bad_retun_qty"),
                DB::raw("sum(transaction_details.unload_qty) as total_unload_qty"),
                DB::raw("concat(users.firstname,' ',users.lastname) AS 'salesman_fullname'"),
                'users.id as salesman_id'
            );
        $date = ($date != '') ? $date : date('Y-m-d');
        $record = $record->whereDate('transactions.transaction_date', '=', $date);

        if (isset($request->salseman_id) && count($request->salseman_id) >= 1) {
            $record = $record->whereIn('transactions.salesman_id', $request->salseman_id);
        }

        $record = $record->groupBy(['items.id']);
        $record = $record->get();

        //$columns = $request->columns;
        if (is_object($record)) {
            foreach ($record as $key => $val) {
                /*if (count($columns) > 0) {
                    if (!in_array('firstname', $columns)) {
                        unset($debit_notes[$key]->firstname);
                    }
                } else {
                    unset($record[$key]->item_qty);
                }*/
                unset($record[$key]->id);
            }
        }
        /*variance*/
        if ($request->export == 0) {
            return prepareResult(true, $record, [], "Unload Report", $this->success);
        } else {
            Excel::store(new CustomerReportExport($record, $columns), 'unload_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/unload_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }   
    }



    /**
     * Display a tirp report of the salesman.
     *
     * @return \Illuminate\Http\Response
     */

    public function salsemanTripReport(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date   = Carbon::parse($request->end_date)->format('Y-m-d');

        $record = DB::table('salesman_trip_infos')
            ->leftjoin('trips', function ($join) {
                $join->on('salesman_trip_infos.trips_id', '=', 'trips.id')
                ->where('salesman_trip_infos.trips_id', '!=', 0);
            })
            ->leftjoin('users', 'users.id', '=', 'salesman_trip_infos.salesman_id')
            ->leftjoin('salesman_infos', 'salesman_infos.user_id', '=', 'salesman_trip_infos.salesman_id')
            ->leftjoin('routes', 'routes.id', '=', 'salesman_infos.route_id')
            ->where("users.organisation_id", $request->user()->organisation_id)
            ->select(
                'routes.id as route_id',
                'routes.route_code',
                'routes.route_name',
                'users.id as salesman_id',
                DB::raw("concat(users.firstname,' ',users.lastname) AS 'salesman_fullname'"),
                'salesman_trip_infos.trips_id as trip_id',
                'trips.trip_start_date as Date_begin',
                'trips.trip_end_date as Date_end',
                'trips.trip_end',
                'trips.trip_start',
                'salesman_trip_infos.status',
                'salesman_trip_infos.created_at'
            );

        if (isset($request->salseman_id) && count($request->salseman_id) >= 1) {
            $record = $record->whereIn('salesman_trip_infos.salesman_id', $request->salseman_id);
        }

        if ($start_date != '' && $end_date != '') {
            $record = $record->whereDate('salesman_trip_infos.created_at', '>=', $start_date)
                ->whereDate('salesman_trip_infos.created_at', '<=', $end_date);
        }

        if (isset($request->route) && count($request->route) >= 1) {
            $record = $record->whereIn('routes.id', $request->route);
        }

        $record = $record->get();

        //$columns = $request->columns; 

        if (is_object($record)) {
            foreach ($record as $key => $val) {

                if ($val->trip_end && $val->trip_start) {
                    $datetime1 = new DateTime($val->created_at);
                    $datetime2 = new DateTime($val->trip_start);
                    $interval = $datetime1->diff($datetime2);
                    $record[$key]->total_time = $interval->format('%a D, %h H. %i min');
                } else {
                    $record[$key]->total_time = 0;
                }

                
                switch ($val->status) {
                    case "0":
                        $record[$key]->status = "Logged In";
                        break;
                    case "1":
                        $record[$key]->status = "Day Begin";
                        $record[$key]->Date_end = "";
                        break;
                    case "2":
                        $record[$key]->status = "Load Confirmed";
                        break;
                    case "3":
                        $record[$key]->status = "On Route";
                        break;
                    case "4":
                        $record[$key]->status = "Unloaded";
                        break;
                    case "5":
                        $record[$key]->status = "Day End";
                        break;
                }

                unset($record[$key]->trip_end);
                unset($record[$key]->trip_start);
                //unset($record[$key]->trip_status);
            }
        }

        if ($request->export == 0) {
            return prepareResult(true, $record, [], "Salseman Trip Report", $this->success);
        } else {
            Excel::store(new CustomerReportExport($record, $columns), 'salseman_trip_report.xlsx');
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/salseman_trip_report.xlsx'));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }   
    }




    /**
     * Display a sales Quantity Analysis report of the salesman.
     *
     * @return \Illuminate\Http\Response
     */

    public function salesQuantityAnalysis(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $invoice_query = DB::table('invoices')->select(
            'invoices.id as invoices_id',
            'invoices.salesman_id',
            'invoices.customer_id',
            'invoice_details.id as invoice_details_id',
            'invoice_details.item_id',
            'items.item_code',
            'items.item_name',
            'item_uoms.name as uoms_name',
        )
            ->leftJoin('invoice_details', function ($join) {
                $join->on('invoice_details.invoice_id', '=', 'invoices.id');
            })
            ->leftJoin('items', function ($join) {
                $join->on('items.id', '=', 'invoice_details.item_id');
            })
            ->leftJoin('item_uoms', function ($join) {
                $join->on('item_uoms.id', '=', 'items.lower_unit_uom_id')
                ->where('items.lower_unit_uom_id', '!=', 0);
            })
            ->groupBy('invoice_details.item_id')
            ->selectRaw("SUM(invoice_details.lower_unit_qty) as Total_sales_qty");
            $invoice_query->where('items.organisation_id', $request->user()->organisation_id);
        if ($request->salesman) {
            $invoice_query->where('salesman_id', $request->salesman);
        }

        if ($request->supervisor) {
            $salesman_ids = getSalesmanIds("supervisor", $request->supervisor);
            $invoice_query->whereIn('invoices.salesman_id', $salesman_ids);
        }

        if ($request->region) {
            $salesman_ids = getSalesmanIds("region", $request->region);
            $invoice_query->whereIn('invoices.salesman_id', $salesman_ids);
        }

        if ($request->category) {
            $invoice_query->where('items.item_major_category_id', $request->category);
        }

        if ($request->division) {

            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->division);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $invoice_query->whereIn('invoices.salesman_id', $ids);
        }

        $invoices = $invoice_query->get();

        $credit_note_query = DB::table('credit_notes')->select(
            'credit_notes.id as credit_notes_id',
            'credit_notes.salesman_id',
            'credit_notes.customer_id',
            'credit_note_details.id as credit_note_details_id',
            'credit_note_details.item_id',
            'items.item_code',
            'items.item_name'
        )
            ->leftJoin('credit_note_details', function ($join) {
                $join->on('credit_note_details.credit_note_id', '=', 'credit_notes.id');
            })
            ->leftJoin('items', function ($join) {
                $join->on('items.id', '=', 'credit_note_details.item_id');
            })
            ->groupBy('credit_note_details.item_id')

            ->selectRaw("SUM(credit_note_details.lower_unit_qty) as Total_return_qty");
            $credit_note_query->where('items.organisation_id', $request->user()->organisation_id);
        if ($request->salesman) {
            $credit_note_query->where('salesman_id', $request->salesman);
        }
        if ($request->supervisor) {
            $salesman_ids = getSalesmanIds("supervisor", $request->supervisor);
            $credit_note_query->whereIn('credit_notes.salesman_id', $salesman_ids);
        }

        if ($request->region) {
            $salesman_ids = getSalesmanIds("region", $request->region);
            $credit_note_query->whereIn('credit_notes.salesman_id', $salesman_ids);
        }
        if ($request->category) {
            $credit_note_query->where('items.item_major_category_id', $request->category);
        }
        if ($request->division) {
            $salesmen_lob_query = SalesmanLob::where('lob_id', $request->division);
            $salesmen_lob       = $salesmen_lob_query->get();
            if (count($salesmen_lob)) {
                $salesmen_lob_ids = $salesmen_lob->pluck('salesman_info_id')->toArray();
            }
            $salesman_info_query = SalesmanInfo::select('user_id')->whereIn('id', $salesmen_lob_ids)->get();
            if (count($salesman_info_query)) {
                $ids = $salesman_info_query->pluck('user_id')->toArray();
            }
            $credit_note_query->whereIn('credit_notes.salesman_id', $ids);
        }

        $credit_note = $credit_note_query->get()->toArray();

        $invoices = json_decode(json_encode($invoices), true);
        $usable_credit_note_id = [];
        foreach ($invoices as $key => $invoice_value) {

            $i = array_search($invoice_value['item_id'], array_column($credit_note, 'item_id'));
            $return_val = ($i !== false ? $credit_note[$i] : null);

            if ($return_val) {
                $invoices[$key]['Total_return']      = $return_val->Total_return_qty;
            } else {
                $invoices[$key]['Total_return']      = '0.00';
            }

            unset($invoices[$key]['item_id']);
            unset($invoices[$key]['invoices_id']);
            unset($invoices[$key]['salesman_id']);
            unset($invoices[$key]['customer_id']);
            unset($invoices[$key]['invoice_details_id']);
        }


        if ($request->export == 0) {
            return prepareResult(true, $invoices, [], "Sales Quantity Analysis listing", $this->success);
        } else {

            $file_name = $request->user()->organisation_id . '_sales_quantity_analysis_report.xlsx';

            $columns = array(
                'Item Name',
                'Item Code',
                'Total Sale Quantity',
                'Total Return Quantity',
                'Uoms Name'
            );

            $sales_collection = new Collection();
            foreach ($invoices as $key => $invoice_val) {
                $item_name           = "N/A";
                $item_code           = "N/A";
                $total_invoice_sales = "N/A";
                $total_return        = "N/A";
                $uoms_name        = "N/A";
                

                $sales_collection->push((object) [
                    'item_name'           => $invoice_val['item_name'],
                    'item_code'           => $invoice_val['item_code'],
                    'Total_sales_qty'     => (!empty($invoice_val['Total_sales_qty']) ? $invoice_val['Total_sales_qty'] : 0),
                    'Total_return'    => (!empty($invoice_val['Total_return']) ? $invoice_val['Total_return'] : 0),
                    'uoms_name'  => (!empty($invoice_val['uoms_name']) ? $invoice_val['uoms_name'] : 0),
                ]);
            }

            Excel::store(new SalesAnalysisReportExport($sales_collection, $columns), $file_name);
            $result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $file_name));
            return prepareResult(true, $result, [], "Data successfully exported", $this->success);
        }   
    }
}
