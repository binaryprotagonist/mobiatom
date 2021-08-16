<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\ComplaintFeedback;
use App\Model\ComplaintFeedbackImage;
use App\Model\CustomerInfo;
use App\Model\ImportTempFile;
use App\Model\Item;
use App\Model\Route;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ComplaintfeedbackImport;
use Illuminate\Support\Str;
use League\OAuth2\Server\RequestEvent;
use stdClass;
use File;
use URL;

class ComplaintFeedbackController extends Controller
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

        $complaint_feedback_query = ComplaintFeedback::with(
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'item:id,item_name,item_code',
            'route:id,route_name',
            'complaintFeedbackImage'
        );

        if ($request->date) {
            $complaint_feedback_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->complaint_id) {
            $complaint_feedback_query->where('complaint_id', $request->complaint_id);
        }

        if ($request->title) {
            $complaint_feedback_query->where('title', '%' . $request->complaint_id .'%');
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $complaint_feedback_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $complaint_feedback_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->item_name) {
            $name = $request->item_name;
            $complaint_feedback_query->whereHas('item', function ($q) use ($name) {
                $q->where('item_name', 'like', '%' . $name . '%');
            });
        }

        if ($request->item_code) {
            $item_code = $request->item_code;
            $complaint_feedback_query->whereHas('item', function ($q) use ($item_code) {
                $q->where('item_code', $item_code);
            });
        }

        $complaint_feedback = $complaint_feedback_query->orderBy('id', 'desc')
            ->get();

        $complaint_feedback_array = array();
        if (is_object($complaint_feedback)) {
            foreach ($complaint_feedback as $key => $complaint_feedback1) {
                $complaint_feedback_array[] = $complaint_feedback[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($complaint_feedback_array[$offset])) {
                    $data_array[] = $complaint_feedback_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($complaint_feedback_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($complaint_feedback_array);
        } else {
            $data_array = $complaint_feedback_array;
        }

        return prepareResult(true, $data_array, [], "Complaint Feedback listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating complaint feedback", $this->unprocessableEntity);
        }

        if ($request->type != 'suggestion') {
            $validator = \Validator::make($input, [
                'item_id' => 'required|integer|exists:items,id',
            ]);

            if ($validator->fails()) {
                return prepareResult(false, [], $validator->errors()->first(), "Error while validating complaint feedback", $this->unprocessableEntity);
            }
        }


        \DB::beginTransaction();
        try {

            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Complaint', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Order',$request);
            }

            $complaint_feedback = new ComplaintFeedback;
            $complaint_feedback->route_id = $request->route_id;
            $complaint_feedback->salesman_id = $request->salesman_id;
            $complaint_feedback->customer_id = $request->customer_id;
            $complaint_feedback->complaint_id = $request->complaint_id;
            $complaint_feedback->title = $request->title;
            $complaint_feedback->item_id = $request->item_id;
            $complaint_feedback->trip_id = $request->trip_id;
            $complaint_feedback->type = $request->type;
            $complaint_feedback->description = $request->description;
            $complaint_feedback->status = $status;
            $complaint_feedback->current_stage = $current_stage;
            $complaint_feedback->current_stage_comment = $request->current_stage_comment;
            $complaint_feedback->save();

            if (is_array($request->images) && sizeof($request->images) >= 1) {
                foreach ($request->images as $image) {
                    if (!empty($image)) {
                        $complaint_feedback_image = new ComplaintFeedbackImage;
                        $complaint_feedback_image->complaint_feedback_id  = $complaint_feedback->id;
                        $saveImage = saveImage("cf_" . Str::slug(rand(100000000000, 99999999999999)), $image, "complaint_feedback");
                        $complaint_feedback_image->image_string           = $saveImage;
                        $complaint_feedback_image->save();
                    }
                }
            }


            \DB::commit();
            $complaint_feedback->getSaveData();

            return prepareResult(true, $complaint_feedback, [], "Complaint Feedback added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer|exists:users,id',
                // 'item_id' => 'required|integer|exists:items,id',
                // 'route_id' => 'required|integer|exists:routes,id',
                'complaint_id' => 'required',
                'title' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("Complaint Id", "Title", "Item", "Description", "Status", "Customer Name", "Salesman Name", "Route Name", "Image");

        return prepareResult(true, $mappingarray, [], "Complaint Feedback Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'complaintfeedback_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate complaint feedback import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('complaintfeedback_file')->store('import');
            $filename = storage_path("app/" . $file);
            $fp = fopen($filename, "r");
            $content = fread($fp, filesize($filename));
            $lines = explode("\n", $content);
            $heading_array_line = isset($lines[0]) ? $lines[0] : '';
            $heading_array = explode(",", trim($heading_array_line));
            fclose($fp);

            if (!$heading_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            if (!$map_key_value_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }

            $import = new ComplaintfeedbackImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

            $succussrecords = 0;
            $successfileids = 0;
            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());
                $data = json_encode($import->successAllRecords());
                $fileName = time() . '_datafile.txt';
                \File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();
                $successfileids = $importtempfiles->id;
            }
            $errorrecords = 0;
            $errror_array = array();
            if ($import->failures()) {

                foreach ($import->failures() as $failure_key => $failure) {
                    //echo $failure_key.'--------'.$failure->row().'||';
                    //print_r($failure);
                    if ($failure->row() != 1) {
                        $failure->row(); // row that went wrong
                        $failure->attribute(); // either heading key (if using heading row concern) or column index
                        $failure->errors(); // Actual error messages from Laravel validator
                        $failure->values(); // The values of the row that has failed.
                        //print_r($failure->errors());

                        $error_msg = isset($failure->errors()[0]) ? $failure->errors()[0] : '';
                        if ($error_msg != "") {
                            //$errror_array['errormessage'][] = array("There was an error on row ".$failure->row().". ".$error_msg);
                            //$errror_array['errorresult'][] = $failure->values();
                            $error_result = array();
                            $error_row_loop = 0;
                            foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
                                $error_result[$map_key_value_array_value] = isset($failure->values()[$error_row_loop]) ? $failure->values()[$error_row_loop] : '';
                                $error_row_loop++;
                            }
                            $errror_array[] = array(
                                'errormessage' => "There was an error on row " . $failure->row() . ". " . $error_msg,
                                'errorresult' => $error_result, //$failure->values(),
                                //'attribute' => $failure->attribute(),//$failure->values(),
                                //'error_result' => $error_result,
                                //'map_key_value_array' => $map_key_value_array,
                            );
                        }
                    }
                }
                $errorrecords = count($errror_array);
            }
            //echo '<pre>';
            //print_r($import->failures());
            //echo '</pre>';
            $errors = $errror_array;
            $result['successrecordscount'] = $succussrecords;
            $result['errorrcount'] = $errorrecords;
            $result['successfileids'] = $successfileids;


            //}
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                if ($failure->row() != 1) {
                    info($failure->row());
                    info($failure->attribute());
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                    $errors[] = $failure->errors();
                }
            }

            return prepareResult(true, [], $errors, "Failed to validate bank import", $this->success);
        }
        return prepareResult(true, $result, $errors, "complaint feedback successfully imported", $this->success);
    }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);
            if ($finaldata) :
                foreach ($finaldata as $row) :

                    $item = Item::where('item_code', 'LIKE', '%' . $row[2] . '%')->first();
                    $customer = CustomerInfo::where('customer_code', $row[5])->first();
                    $salesman = CustomerInfo::where('customer_code', $row[6])->first();
                    $route = Route::where('route_code', $row[7])->first();
                    $complaint = ComplaintFeedback::where('complaint_id', $row[0])->first();
                    $current_organisation_id = request()->user()->organisation_id;

                    if (is_object($complaint)) {
                        $complaint->route_id = (is_object($route)) ? $route->id : 0;
                        $complaint->salesman_id  = (is_object($salesman)) ? $salesman->user_id : 0;
                        $complaint->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $complaint->complaint_id  = $row[0];
                        $complaint->title = $row[1];
                        $complaint->item_id = (is_object($item)) ? $item->id : 0;
                        $complaint->description = $row[3];
                        $complaint->status = $row[4];
                        $complaint->save();

                        $complaint_feedback_image = ComplaintFeedbackImage::where('complaint_feedback_id', $complaint->id)->first();
                        $complaint_feedback_image->complaint_feedback_id = $complaint->id;
                        $complaint_feedback_image->image_string = $row[8];

                        $complaint_feedback_image->save();
                    } else {
                        if (!is_object($route) or !is_object($salesman) or !is_object($customer) or !is_object($item)) {
                            if (!is_object($route)) {
                                return prepareResult(false, [], [], "route not exists", $this->unauthorized);
                            }
                            if (!is_object($salesman)) {
                                return prepareResult(false, [], [], "salesman not exists", $this->unauthorized);
                            }
                            if (!is_object($customer)) {
                                return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                            }
                            if (!is_object($item)) {
                                return prepareResult(false, [], [], "item not exists", $this->unauthorized);
                            }
                        }

                        $complaint = new ComplaintFeedback;
                        $complaint->organisation_id = $current_organisation_id;
                        $complaint->route_id = (is_object($route)) ? $route->id : 0;
                        $complaint->salesman_id  = (is_object($salesman)) ? $salesman->user_id : 0;
                        $complaint->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $complaint->complaint_id  = $row[0];
                        $complaint->title = $row[1];
                        $complaint->item_id = (is_object($item)) ? $item->id : 0;
                        $complaint->description = $row[3];
                        $complaint->status = $row[4];
                        $complaint->save();


                        $complaint_feedback_image = new ComplaintFeedbackImage;
                        $complaint_feedback_image->complaint_feedback_id = $complaint->id;
                        $complaint_feedback_image->image_string = $row[8];

                        $complaint_feedback_image->save();
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "Complaint Feedback successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }
}
