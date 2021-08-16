<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Invoice;
use App\Model\InvoiceReminder;
use App\Model\InvoiceReminderDetail;
use Illuminate\Http\Request;

class InvoiceReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $invoice_reminder = InvoiceReminder::select('id', 'uuid', 'invoice_id', 'message')
            ->with('invoiceReminderDetails', 'invoice')
            ->whereHas('invoiceReminderDetails', function ($q) {
                $q->where('reminder_date', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        $invoice_reminder_array = array();
        if (is_object($invoice_reminder)) {
            foreach ($invoice_reminder as $key => $invoice_reminder1) {
                $invoice_reminder_array[] = $invoice_reminder[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($invoice_reminder_array[$offset])) {
                    $data_array[] = $invoice_reminder_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($invoice_reminder_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($invoice_reminder_array);
        } else {
            $data_array = $invoice_reminder_array;
        }
        return prepareResult(true, $data_array, [], "Invoices listing", $this->success, $pagination);
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

        if ($request->automatic_send_reminder) {
            if (is_array($request->reminder) && sizeof($request->reminder) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one reminder.", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $invoice = Invoice::where('id', $request->invoice_id)->first();
            $due_date = $invoice->invoice_due_date;

            $invoice_reminder = new InvoiceReminder;
            $invoice_reminder->invoice_id = $request->invoice_id;
            $invoice_reminder->is_automatically = $request->is_automatically;
            $invoice_reminder->message = $request->message;
            $invoice_reminder->save();

            foreach ($request->reminder as $reminder) {

                if ($reminder['date_prefix'] == "after") {
                    $date = date('Y-m-d', strtotime($due_date . "+" . $reminder['reminder_day'] . " days"));
                }

                if ($reminder['date_prefix'] == "before") {
                    $date = date('Y-m-d', strtotime($due_date . "-" . $reminder['reminder_day'] . " days"));
                }

                $invoice_reminder_details = new InvoiceReminderDetail;
                $invoice_reminder_details->invoice_reminder_id = $invoice_reminder->id;
                $invoice_reminder_details->reminder_day = $reminder['reminder_day'];
                $invoice_reminder_details->reminder_date = $date;
                $invoice_reminder_details->date_prefix = $reminder['date_prefix'];
                $invoice_reminder_details->save();
            }

            \DB::commit();
            $invoice_reminder->getSaveData();
            return prepareResult(true, $invoice_reminder, [], "Invoice reminder added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($invoice_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$invoice_id) {
            return prepareResult(false, [], [], "Error while validating invoice reminder.", $this->unauthorized);
        }

        $invoice_reminder = InvoiceReminder::where('invoice_id', $invoice_id)
            ->with('invoiceReminderDetails', 'invoice')
            ->get();

        return prepareResult(true, $invoice_reminder, [], "Invoice reminder edited successfully", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if ($request->automatic_send_reminder) {
            if (is_array($request->reminder) && sizeof($request->reminder) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one reminder.", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            InvoiceReminder::where('invoice_id', $request->invoice_id)->delete();

            $invoice = Invoice::where('id', $request->invoice_id)->first();
            $due_date = $invoice->invoice_due_date;

            $invoice_reminder = new InvoiceReminder;
            $invoice_reminder->invoice_id = $request->invoice_id;
            $invoice_reminder->is_automatically = $request->is_automatically;
            $invoice_reminder->message = $request->message;
            $invoice_reminder->save();

            foreach ($request->reminder as $reminder) {

                if ($reminder['date_prefix'] == "after") {
                    $date = date('Y-m-d', strtotime($due_date . "+" . $reminder['reminder_day'] . " days"));
                }

                if ($reminder['date_prefix'] == "before") {
                    $date = date('Y-m-d', strtotime($due_date . "-" . $reminder['reminder_day'] . " days"));
                }

                $invoice_reminder_details = new InvoiceReminderDetail;
                $invoice_reminder_details->invoice_reminder_id = $invoice_reminder->id;
                $invoice_reminder_details->reminder_day = $reminder['reminder_day'];
                $invoice_reminder_details->reminder_date = $date;
                $invoice_reminder_details->date_prefix = $reminder['date_prefix'];
                $invoice_reminder_details->save();
            }

            \DB::commit();
            $invoice_reminder->getSaveData();
            return prepareResult(true, $invoice_reminder, [], "Invoice reminder updated successfully", $this->created);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating invoice reminder.", $this->unauthorized);
        }

        $invoice = InvoiceReminder::where('uuid', $uuid)
            ->first();

        if (is_object($invoice)) {
            $invoice->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }
}
