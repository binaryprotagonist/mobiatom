<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Collection;
use App\Model\CreditNote;
use App\Model\CustomerVisit;
use App\Model\Invoice;
use App\Model\SalesmanInfo;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating Supervisor id", $this->unauthorized);
        }

        $salesmans = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor')
            ->where('salesman_supervisor', $id)
            ->get();

        $invoice['invoice_count'] = 0;
        $invoice['invoice_total'] = 0;
        $invoice['details'] = [];

        $credit_note['credit_note_count'] = 0;
        $credit_note['credit_note_total'] = 0;

        $collection['collection_count'] = 0;
        $collection['collection_total'] = 0;

        $cusotmer_visit['cusotmer_visit_count'] = 0;

        if (count($salesmans)) {
            $salesman_ids = $salesmans->pluck('user_id')->toArray();

            $invoices = Invoice::select('id', 'invoice_number', 'grand_total', 'created_at', 'invoice_date')
                ->whereIn('salesman_id', $salesman_ids)
                ->whereDate('created_at', date('Y-m-d'))
                ->get();

            if ($invoices->count()) {
                $grand_total_array = $invoices->pluck('grand_total')->toArray();
                $invoice_count = count($invoices);
                $invoice_total = array_sum($grand_total_array);

                $invoice['invoice_count'] = $invoice_count;
                $invoice['invoice_total'] = $invoice_total;
                $invoice['details'] = $invoices;
            }

            $credit_notes = CreditNote::whereIn('salesman_id', $salesman_ids)
                ->whereDate('created_at', date('Y-m-d'))
                ->get();
            if ($credit_notes->count()) {
                $grand_total_array = $credit_notes->pluck('grand_total')->toArray();
                $credit_note_count = count($credit_notes);
                $credit_note_total = array_sum($grand_total_array);

                $credit_note['credit_note_count'] = $credit_note_count;
                $credit_note['credit_note_total'] = $credit_note_total;
            }

            $collections = Collection::whereIn('salesman_id', $salesman_ids)
                ->whereDate('created_at', date('Y-m-d'))
                ->get();

            if ($collections->count()) {
                $grand_total_array = $collections->pluck('invoice_amount')->toArray();
                $collection_count = count($collections);
                $collection_total = array_sum($grand_total_array);

                $collection['collection_count'] = $collection_count;
                $collection['collection_total'] = $collection_total;
            }

            $cusotmer_visits = CustomerVisit::whereIn('salesman_id', $salesman_ids)
                ->whereDate('created_at', date('Y-m-d'))
                ->where('shop_status', "open")
                ->whereNull('reason')
                ->groupBy('customer_id', 'date')
                ->get();
            if ($cusotmer_visits->count()) {
                $cusotmer_visit_count = count($cusotmer_visits);
                $cusotmer_visit['cusotmer_visit_count'] = $cusotmer_visit_count;
            }
        }

        $data = array_merge($invoice, $credit_note, $collection, $cusotmer_visit);

        return prepareResult(true, collect($data), [], "Supervisor by salesman data", $this->success);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
