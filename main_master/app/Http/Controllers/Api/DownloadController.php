<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\AssignTemplate;
use App\Model\Collection;
use App\Model\CreditNote;
use App\Model\DebitNote;
use App\Model\Delivery;
use App\Model\Estimation;
use App\Model\Expense;
use App\Model\Invoice;
use App\Model\Order;
use App\Model\Template;
use App\User;
use Illuminate\Http\Request;
use Meneses\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PdfMpdf;

class DownloadController extends Controller
{
    public function invoice(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "invoice");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }

        $invoice = Invoice::with('invoices', 'paymentTerm', 'orderType', 'depot', 'organisation')
            ->where('id', $request->id)
            ->first();

        $assign_template = AssignTemplate::with('template')->where('module', 'invoice')->first();

        $pdfFilePath = public_path() . "\uploads\pdf";

        $data = array(
            'invoice' => $invoice
        );

        $dataArray = array();

        if (is_object($assign_template)) {
            if ($request->status == "pdf") {
                // $pdf = PDF::loadView('html.' . $assign_template->template->file_name, $data);
                $html = view('html.' . $assign_template->template->file_name, $data)->render();

                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output(public_path() . "/uploads/pdf/" . $invoice->invoice_number . '.pdf', "F");

                $pdfFilePath = public_path() . "/uploads/pdf/" . $invoice->invoice_number . ".pdf";

                $fileURL = 'uploads/pdf/' . $invoice->invoice_number . '.pdf';
                $pdfFilePath = url($fileURL);

                $dataArray['file_url'] = $pdfFilePath;
            } else {
                $html = view('html.' . $assign_template->template->file_name, $data)->render();
                $dataArray['html_string'] = $html;
            }
        } else {
            $template = Template::where('module', 'invoice')->where('is_default', 1)->first();
            if ($request->status == "pdf") {
                $pdfFilePath = public_path() . "/uploads/pdf/" . $invoice->invoice_number . ".pdf";
                PDF::loadView('html.' . $template->file_name, $data)->save($pdfFilePath);

                $fileURL = 'uploads/pdf/' . $invoice->invoice_number . '.pdf';
                $pdfFilePath = url($fileURL);

                $dataArray['file_url'] = $pdfFilePath;
            } else {
                $html = view('html.' . $template->file_name, $data)->render();
                $dataArray['html_string'] = $html;
            }
        }

        return prepareResult(true, $dataArray, [], "Invoice Download", $this->success);
    }

    public function delivery(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "delivery");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating delivery", $this->unprocessableEntity);
        }

        $delivery = Delivery::with('deliveryDetails', 'customerInfo', 'customer', 'salesman', 'salesmanInfo', 'paymentTerm', 'orderType')
            ->where('id', $request->id)
            ->first();

        $assign_template = AssignTemplate::with('template')->where('module', 'delivery')->first();

        $data = array(
            'delivery' => $delivery
        );

        if (is_object($assign_template)) {
            if ($request->status == "pdf") {
                $pdfFilePath = public_path() . "/uploads/pdf/" . $delivery->delivery_number . ".pdf";
                PDF::loadView('html.' . $assign_template->template->file_name, $data)->save($pdfFilePath);

                $fileURL = 'uploads/pdf/' . $delivery->delivery_number . '.pdf';
                $pdfFilePath = url($fileURL);

                $dataArray['file_url'] = $pdfFilePath;
            } else {
                $html = view('html.' . $assign_template->template->file_name, $data)->render();
                $dataArray['html_string'] = $html;
            }
        } else {
            $template = Template::where('module', 'delivery')->where('is_default', 1)->first();
            if ($request->status == "pdf") {
                $pdfFilePath = public_path() . "/uploads/pdf/" . $delivery->delivery_number . ".pdf";
                PDF::loadView('html.' . $template->file_name, $data)->save($pdfFilePath);

                $fileURL = 'uploads/pdf/' . $delivery->delivery_number . '.pdf';
                $pdfFilePath = url($fileURL);

                $dataArray['file_url'] = $pdfFilePath;
            } else {
                $html = view('html.' . $template->file_name, $data)->render();
                $dataArray['html_string'] = $html;
            }
        }

        return prepareResult(true, $dataArray, [], "Delivery Download", $this->success);
    }

    public function creditNote(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "credit_note");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit note", $this->unprocessableEntity);
        }

        $credit_note = CreditNote::with('creditNoteDetails', 'organisation', 'invoice', 'customer', 'customerInfo', 'salesman')
            ->where('id', $request->id)
            ->first();

        $data = array(
            'credit_note' => $credit_note
        );

        if ($request->status == "pdf") {
            $pdfFilePath = public_path() . "/uploads/pdf/" . $credit_note->credit_note_number . ".pdf";
            $pdf = PDF::loadView('html.credit_note', $data)->save($pdfFilePath);

            $fileURL = 'uploads/pdf/' . $credit_note->credit_note_number . '.pdf';
            $pdfFilePath = url($fileURL);

            $dataArray['file_url'] = $pdfFilePath;
        } else {

            $html = view('html.credit_note', $data)->render();
            $dataArray['html_string'] = $html;
        }

        return prepareResult(true, $dataArray, [], "Credit Note Download", $this->success);
    }

    public function debitNote(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "debit_note");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating debit note", $this->unprocessableEntity);
        }

        $debit_note = DebitNote::with('debitNoteDetails', 'organisation', 'invoice', 'customer', 'salesman')
            ->where('id', $request->id)
            ->first();

        $data = array(
            'debit_note' => $debit_note
        );

        if ($request->status == "pdf") {

            $pdfFilePath = public_path() . "/uploads/pdf/" . $debit_note->debit_note_number . ".pdf";
            PDF::loadView('html.debit_note', $data)->save($pdfFilePath);

            $fileURL = 'uploads/pdf/' . $debit_note->debit_note_number . '.pdf';
            $pdfFilePath = url($fileURL);

            $dataArray['file_url'] = $pdfFilePath;
        } else {

            $html = view('html.debit_note', $data)->render();
            $dataArray['html_string'] = $html;
        }

        return prepareResult(true, $dataArray, [], "Debit Note Download", $this->success);
    }

    public function customer(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
    }

    public function order(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "order");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
        }

        $order = Order::with('orderDetails', 'organisation', 'orderType', 'customer', 'salesman', 'depot', 'paymentTerm')
            ->where('id', $request->id)
            ->first();

        $data = array(
            'order' => $order
        );

        if ($request->status == "pdf") {

            $pdfFilePath = public_path() . "/uploads/pdf/" . $order->order_number . ".pdf";
            PDF::loadView('html.order', $data)->save($pdfFilePath);

            $fileURL = 'uploads/pdf/' . $order->order_number . '.pdf';
            $pdfFilePath = url($fileURL);

            $dataArray['file_url'] = $pdfFilePath;
        } else {

            $html = view('html.order', $data)->render();
            $dataArray['html_string'] = $html;
        }

        return prepareResult(true, $dataArray, [], "Order Download", $this->success);
    }

    public function estimate(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "estimation");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating estimation", $this->unprocessableEntity);
        }

        $estimation = Estimation::with('organisation', 'customer', 'salesperson', 'estimationdetail')
            ->where('id', $request->id)
            ->first();

        $data = array(
            'estimation' => $estimation
        );

        if ($request->status == "pdf") {

            $pdfFilePath = public_path() . "/uploads/pdf/" . $estimation->estimate_code . ".pdf";
            PDF::loadView('html.estimation', $data)->save($pdfFilePath);

            $fileURL = 'uploads/pdf/' . $estimation->estimate_code . '.pdf';
            $pdfFilePath = url($fileURL);

            $dataArray['file_url'] = $pdfFilePath;
        } else {

            $html = view('html.estimation', $data)->render();
            $dataArray['html_string'] = $html;
        }

        return prepareResult(true, $dataArray, [], "Estimation Download", $this->success);
    }

    public function collection(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "collection");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating collection", $this->unprocessableEntity);
        }

        $collection = Collection::with('organisation', 'invoice', 'customer', 'salesman', 'collectiondetails')
            ->where('id', $request->id)
            ->first();

        $data = array(
            'collection' => $collection
        );

        if ($request->status == "pdf") {

            $pdfFilePath = public_path() . "/uploads/pdf/" . $collection->collection_number . ".pdf";
            PDF::loadView('html.collection', $data)->save($pdfFilePath);

            $fileURL = 'uploads/pdf/' . $collection->collection_number . '.pdf';
            $pdfFilePath = url($fileURL);

            $dataArray['file_url'] = $pdfFilePath;
        } else {

            $html = view('html.collection', $data)->render();
            $dataArray['html_string'] = $html;
        }

        return prepareResult(true, $dataArray, [], "Collection Download", $this->success);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;

        if ($type == "invoice") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:invoices,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "delivery") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:deliveries,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "credit_note") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:credit_notes,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "debit_note") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:debit_notes,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "customer") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "order") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:orders,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "estimation") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:estimation,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function logUpload(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = \Validator::make($request->all(), [
            'log_file' => 'required|mimes:txt',
            'salesman_code' => 'required|exists:salesman_infos,salesman_code'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate log uplodding", $this->unauthorized);
        }

        \DB::beginTransaction();
        try {

            $file = $request->file('log_file');

            $extension = $file->getClientOriginalExtension();
            $path = $file->getRealPath();
            $size = $file->getSize();
            $mime_type = $file->getMimeType();
            $destinationPath = "uploads/mobile-log";

            $salesman_code = $request->salesman_code;
            $file_name = $salesman_code . '-' . $file->getClientOriginalName();
            $file->move($destinationPath, $file_name);
            $url =  URL('/') . '/' . $destinationPath . '/' . $file_name;

            \DB::commit();
            return prepareResult(true, $url, [], "Log file uploded", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    
}
