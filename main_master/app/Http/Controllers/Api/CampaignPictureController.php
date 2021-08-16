<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\CampaignPictureImport;
use App\Model\CampaignPicture;
use App\Model\CampaignPictureImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignPictureController extends Controller
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

        if (!checkPermission('campaign-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('campaign-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $campaign_picture_query = CampaignPicture::with(
            'campaignPictureImage',
            'customer:id,firstname,lastname',
            'customer.customerinfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmaninfo:id,user_id,salesman_code'
        );

        if ($request->date) {
            $campaign_picture_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->campaign_id) {
            $campaign_picture_query->where('campaign_id', $request->campaign_id);
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $campaign_picture_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                    ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $campaign_picture_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                        ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $campaign_picture_query->whereHas('customer.customerinfo', function ($q) use ($customer_code) {
                $q->where('customer_code', $customer_code);
            });
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $campaign_picture_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                    ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $campaign_picture_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                        ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }


        $campaign_picture = $campaign_picture_query->orderBy('id', 'desc')
            ->get();

        $campaign_picture_array = array();
        if (is_object($campaign_picture)) {
            foreach ($campaign_picture as $key => $campaign_picture1) {
                $campaign_picture_array[] = $campaign_picture[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($campaign_picture_array[$offset])) {
                    $data_array[] = $campaign_picture_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($campaign_picture_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($campaign_picture_array);
        } else {
            $data_array = $campaign_picture_array;
        }

        return prepareResult(true, $data_array, [], "Campaign Picture listing", $this->success, $pagination);
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

        if (!checkPermission('campaign-add')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Campaign Picture", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $campaign_picture = new CampaignPicture;
            $campaign_picture->campaign_id = $request->campaign_id;
            $campaign_picture->trip_id = $request->trip_id;
            $campaign_picture->salesman_id = $request->salesman_id;
            $campaign_picture->customer_id = $request->customer_id;
            $campaign_picture->feedback = $request->feedback;
            $campaign_picture->save();

            if (is_array($request->images) && sizeof($request->images) >= 1)
                foreach ($request->images as $cpImage) {
                    if (!empty($cpImage)) {
                        $cpi = new CampaignPictureImage;
                        $saveImage = saveImage(Str::slug(rand(100000000000, 99999999999999)), $cpImage, "campaign-picture");

                        $cpi->id_campaign_picture = $campaign_picture->id;
                        $cpi->image_string        = $saveImage;
                        $cpi->save();
                    }
                }

            \DB::commit();
            $campaign_picture->getSaveData();
            return prepareResult(true, $campaign_picture, [], "Campaign Picture added successfully", $this->success);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!checkPermission('brand-detail')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $campaign_picture = CampaignPicture::select('id', 'uuid', 'organisation_id', 'campaign_id', 'salesman_id', 'customer_id', 'feedback', 'trip_id')
            ->with('campaignPictureImage', 'customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
            ->where('uuid', $uuid)
            ->first();

        return prepareResult(true, $campaign_picture, [], "Campaign Picture listing", $this->success);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer|exists:users,id',
                'customer_id' => 'required|integer|exists:users,id',
                'campaign_id' => 'required',
                'feedback' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'campaignpicture_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate Campaign Picture import", $this->unauthorized);
        }
        $errors = array();
        try {
            $file = request()->file('campaignpicture_file')->store('import');
            $import = new CampaignPictureImport($request->skipduplicate);
            $import->import($file);
            if (count($import->failures()) > 58) {
                $errors[] = $import->failures();
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                info($failure->row());
                info($failure->attribute());
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
                $errors[] = $failure->errors();
            }

            return prepareResult(true, [], $errors, "Failed to validate campaign picture import", $this->success);
        }

        return prepareResult(true, [], $errors, "Campaign picture successfully imported", $this->success);
    }
}
