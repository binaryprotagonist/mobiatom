<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\CompetitorinfoImport;
use App\Model\CompetitorInfo;
use App\Model\CompetitorInfoImage;
use App\Model\CompetitorInfoOurBrand;
use App\Model\CustomerInfo;
use App\Model\ImportTempFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use File;
use URL;

class CompetitorInfoController extends Controller
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

        $competitor_info_query = CompetitorInfo::with(
            'competitorInfoImage',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'competitorInfoOurBrand',
            'competitorInfoOurBrand.brand:id,brand_name'

        );
        if ($request->date) {
            $competitor_info_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $competitor_info_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $competitor_info_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->company) {
            $competitor_info_query->whereDate('company', 'like', '%' . $request->company . '%');
        }

        if ($request->item) {
            $competitor_info_query->whereDate('item', 'like', '%' . $request->item . '%');
        }

        if ($request->brand) {
            $competitor_info_query->whereDate('brand', 'like', '%' . $request->brand . '%');
        }

        if ($request->all) {
            $competitor_info = $competitor_info_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $competitor_info_query->whereDate('created_at', date('Y-m-d'));
            }
            $competitor_info = $competitor_info_query->orderBy('id', 'desc')->get();
            // $competitor_info = $competitor_info_query->get();
        }

        $competitor_info_array = array();
        if (is_object($competitor_info)) {
            foreach ($competitor_info as $key => $competitor_info1) {
                $competitor_info_array[] = $competitor_info[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($competitor_info_array[$offset])) {
                    $data_array[] = $competitor_info_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($competitor_info_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($competitor_info_array);
        } else {
            $data_array = $competitor_info_array;
        }

        return prepareResult(true, $data_array, [], "Competitor info listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating planogram post", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $competitor_info = new CompetitorInfo;
            $competitor_info->salesman_id = $request->salesman_id;
            $competitor_info->company = $request->company;
            $competitor_info->trip_id = $request->trip_id;
            $competitor_info->brand = $request->brand;
            $competitor_info->item = $request->item;
            $competitor_info->price = $request->price;
            $competitor_info->note = $request->note;
            $competitor_info->promotion = $request->promotion;
            $competitor_info->save();

            if (is_array($request->compare_brands) && sizeof($request->compare_brands) >= 1) {
                foreach ($request->compare_brands as $our_brand) {
                    $ciob = new CompetitorInfoOurBrand;
                    $ciob->competitor_info_id = $competitor_info->id;
                    $ciob->brand_id = $our_brand;
                    $ciob->save();
                }
            }

            if (is_array($request->competitor_info_images) && sizeof($request->competitor_info_images) >= 1) {
                foreach ($request->competitor_info_images as $ciImage) {
                    $competitor_info_image = new CompetitorInfoImage;
                    $competitor_info_image->competitor_info_id = $competitor_info->id;
                    $saveImage = saveImage("cp_" . Str::slug(rand(100000000000, 99999999999999)), $ciImage, "competitor-info");
                    $competitor_info_image->image_string = $saveImage;
                    $competitor_info_image->save();
                }
            }

            \DB::commit();
            $competitor_info->getSaveData();

            return prepareResult(true, $competitor_info, [], "Competitor info added successfully", $this->created);
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
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Competitor Info", $this->unauthorized);
        }

        $ci = CompetitorInfo::with(
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'competitorInfoImage'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($ci)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $ci, [], "Competitor Info Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating competitor info.", $this->unprocessableEntity);
        }

        $dms = CompetitorInfo::where('uuid', $uuid)
            ->first();

        if (is_object($dms)) {
            $dms->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer|exists:users,id',
                'company' => 'required',
                'brand' => 'required',
                'item' => 'required',
                'price' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("Company", "Brand", "Item", "Price", "Note", "Salesman Code", "Image");

        return prepareResult(true, $mappingarray, [], "Assign inventory Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'competitorinfo_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate assign inventory import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('competitorinfo_file')->store('import');
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


            $import = new CompetitorinfoImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

            //print_r($import);
            //exit;
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
        return prepareResult(true, $result, $errors, "assign inventory successfully imported", $this->success);
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

                    $salesman = CustomerInfo::where('customer_code', $row[5])->first();
                    $competitorInfo = CompetitorInfo::where('company', $row[0])
                        ->where('brand', $row[1])
                        ->where('item', $row[2])
                        ->first();
                    $current_organisation_id = request()->user()->organisation_id;

                    if (is_object($competitorInfo)) {
                        $competitorInfo->salesman_id = (is_object($salesman)) ? $salesman->user_id : 0;
                        $competitorInfo->company = $row[0];
                        $competitorInfo->brand = $row[1];
                        $competitorInfo->item = $row[2];
                        $competitorInfo->price = $row[3];
                        $competitorInfo->note = $row[4];
                        $competitorInfo->save();

                        $competitor_info_images = CompetitorInfoImage::where('competitor_info_id', $competitorInfo->id)->first();
                        $competitor_info_images->competitor_info_id = $competitorInfo->id;
                        $competitor_info_images->image_string = $row[6];
                        $competitor_info_images->save();
                    } else {
                        if (!is_object($salesman)) {
                            return prepareResult(false, [], [], "salesman not exists", $this->unauthorized);
                        }
                        $competitorInfo = new CompetitorInfo;
                        $competitorInfo->organisation_id = $current_organisation_id;
                        $competitorInfo->salesman_id = (is_object($salesman)) ? $salesman->user_id : 0;
                        $competitorInfo->company = $row[0];
                        $competitorInfo->brand = $row[1];
                        $competitorInfo->item = $row[2];
                        $competitorInfo->price = $row[3];
                        $competitorInfo->note = $row[4];
                        $competitorInfo->save();

                        $competitor_info_images = new CompetitorInfoImage;
                        $competitor_info_images->competitor_info_id = $competitorInfo->id;
                        $competitor_info_images->image_string = $row[6];
                        $competitor_info_images->save();
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "assign inventory successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    public function competitorBrand()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $competitor_info_query = CompetitorInfo::select('id', 'brand')
            ->groupBy('brand')
            ->orderBy('brand', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $competitor_info_query, [], "competitor info.", $this->success);
    }
}
