<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\AssetTracking;
use App\Model\AssetTrackingPost;
use App\Model\AssetTrackingPostImage;
use App\Model\CustomerInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AssetTrackingImport;
use App\Model\Survey;
use League\OAuth2\Server\RequestEvent;
use stdClass;


class AssetTrackingController extends Controller
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

        if (!checkPermission('asset-tracking-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('asset-tracking-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $asset_tracking_query = AssetTracking::with(
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
        );

        if ($request->date) {
            $asset_tracking_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->start_date) {
            $asset_tracking_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $asset_tracking_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        if ($request->name) {
            $asset_tracking_query->where('title', $request->name);
        }

        if ($request->model_name) {
            $asset_tracking_query->where('model_name', $request->model_name);
        }

        if ($request->code) {
            $asset_tracking_query->where('code', $request->code);
        }

        $asset_tracking = $asset_tracking_query->orderBy('id', 'desc')
            ->get();

        $asset_tracking_array = array();
        if (is_object($asset_tracking)) {
            foreach ($asset_tracking as $key => $asset_tracking1) {
                $asset_tracking_array[] = $asset_tracking[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $data = customPaginate($page, $limit, $asset_tracking_array);
            $data_array = $data['data'];
            $pagination = $data['pagination'];
        } else {
            $data_array = $asset_tracking_array;
        }

        return prepareResult(true, $data_array, [], "Asset Tracking listing", $this->success, $pagination);
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

        if (!checkPermission('asset-tracking-add')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating asset tracking", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $asset_tracking = new AssetTracking;
            $asset_tracking->title = $request->title;
            $asset_tracking->code = $request->code;
            $asset_tracking->description = $request->description;
            $asset_tracking->model_name = $request->model_name;
            $asset_tracking->barcode = $request->barcode;
            $asset_tracking->category = $request->category;
            $asset_tracking->start_date = $request->start_date;
            $asset_tracking->end_date = $request->end_date;
            $asset_tracking->location = $request->location;
            $asset_tracking->lat = $request->lat;
            $asset_tracking->lng = $request->lng;
            $asset_tracking->area = $request->area;
            $asset_tracking->parent_id = $request->parent_id;
            $asset_tracking->wroker = $request->wroker;
            $asset_tracking->additional_wroker = $request->additional_wroker;
            $asset_tracking->team = $request->team;
            $asset_tracking->vendors = $request->vendors;
            $asset_tracking->customer_id = $request->customer_id;
            $asset_tracking->purchase_date = $request->purchase_date;
            $asset_tracking->placed_in_service = $request->placed_in_service;
            $asset_tracking->purchase_price = $request->purchase_price;
            $asset_tracking->warranty_expiration = $request->warranty_expiration;
            $asset_tracking->residual_price = $request->residual_price;
            $asset_tracking->additional_information = $request->additional_information;
            $asset_tracking->useful_life = $request->useful_life;

            if ($request->image) {
                $destinationPath    = 'uploads/asset-tracking/';
                $image_name = \Str::slug(substr($request->title, 0, 30));
                $image = $request->image;
                $getBaseType = explode(',', $image);
                $getExt = explode(';', $image);
                $image = str_replace($getBaseType[0] . ',', '', $image);
                $image = str_replace(' ', '+', $image);
                $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                \File::put($destinationPath . $fileName, base64_decode($image));
                $asset_tracking->image           = URL('/') . '/' . $destinationPath . $fileName;
            }

            $asset_tracking->save();

            updateNextComingNumber('App\Model\AssetTracking', 'asset_tracking');
            \DB::commit();
            $asset_tracking->customer;
            return prepareResult(true, $asset_tracking, [], "Asset Tracking added successfully", $this->created);
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
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!checkPermission('asset-tracking-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $asset_tracking = AssetTracking::where('uuid', $uuid)
            ->with('customer:id,firstname,lastname')
            ->first();

        if (!is_object($asset_tracking)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $asset_tracking, [], "Asset Tracking Edit", $this->success);
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

        if (!checkPermission('asset-tracking-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating area", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $asset_tracking = AssetTracking::where('uuid', $uuid)->first();

            $asset_tracking->title = $request->title;
            $asset_tracking->code = $request->code;
            $asset_tracking->description = $request->description;
            $asset_tracking->model_name = $request->model_name;
            $asset_tracking->barcode = $request->barcode;
            $asset_tracking->category = $request->category;
            $asset_tracking->start_date = $request->start_date;
            $asset_tracking->end_date = $request->end_date;
            $asset_tracking->location = $request->location;
            $asset_tracking->lat = $request->lat;
            $asset_tracking->lng = $request->lng;
            $asset_tracking->area = $request->area;
            $asset_tracking->parent_id = $request->parent_id;
            $asset_tracking->wroker = $request->wroker;
            $asset_tracking->additional_wroker = $request->additional_wroker;
            $asset_tracking->team = $request->team;
            $asset_tracking->vendors = $request->vendors;
            $asset_tracking->customer_id = $request->customer_id;
            $asset_tracking->purchase_date = $request->purchase_date;
            $asset_tracking->placed_in_service = $request->placed_in_service;
            $asset_tracking->purchase_price = $request->purchase_price;
            $asset_tracking->warranty_expiration = $request->warranty_expiration;
            $asset_tracking->residual_price = $request->residual_price;
            $asset_tracking->additional_information = $request->additional_information;
            $asset_tracking->useful_life = $request->useful_life;

            if ($request->image) {
                $destinationPath    = 'uploads/asset-tracking/';
                $image_name = \Str::slug(substr($request->title, 0, 30));
                $image = $request->image;
                $getBaseType = explode(',', $image);
                $getExt = explode(';', $image);
                $image = str_replace($getBaseType[0] . ',', '', $image);
                $image = str_replace(' ', '+', $image);
                $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                \File::put($destinationPath . $fileName, base64_decode($image));
                $asset_tracking->image           = URL('/') . '/' . $destinationPath . $fileName;
            }

            $asset_tracking->save();

            \DB::commit();
            $asset_tracking->customer;
            return prepareResult(true, $asset_tracking, [], "Asset Tracking updated successfully", $this->created);
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

        if (!checkPermission('asset-tracking-delete')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $asset_tracking = AssetTracking::where('uuid', $uuid)
            ->first();

        if (is_object($asset_tracking)) {
            $asset_tracking->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $validator = [];
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'customer_id' => 'required|integer|exists:users,id',
                'title' => 'required|string',
                'model_name' => 'required|string',
                'barcode' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'location' => 'required|string',
                'barcode' => 'required',
                'category' => 'required',
                'location' => 'required',
                'purchase_date' => 'required|date',
                'purchase_price' => 'required',
                'warranty_expiration' => 'required',
                'useful_life' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "addPost") {
            $validator = \Validator::make($input, [
                'asset_tracking_id' => 'required|integer|exists:asset_trackings,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'feedback' => 'required|string',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAssetInfo($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $asset_tracking = AssetTracking::where('id', $id)
            ->get();

        if (!is_object($asset_tracking)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $asset_tracking, [], "Asset Tracking information", $this->success);
    }

    public function storeAssetTrakingPost(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "addPost");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating asset tracking", $this->unprocessableEntity);
        }

        if (is_array($request->images) && sizeof($request->images) < 1) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating asset tracking", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $asset_tracking_post = new AssetTrackingPost;
            $asset_tracking_post->asset_tracking_id = $request->asset_tracking_id;
            $asset_tracking_post->salesman_id = $request->salesman_id;
            $asset_tracking_post->trip_id = $request->trip_id;
            $asset_tracking_post->feedback = $request->feedback;
            $asset_tracking_post->save();

            foreach ($request->images as $image) {
                $asset_tracking_post_image = new AssetTrackingPostImage;
                $asset_tracking_post_image->asset_tracking_id = $asset_tracking_post->asset_tracking_id;
                $asset_tracking_post_image->asset_tracking_post_id = $asset_tracking_post->id;

                $saveImage = saveImage(\Str::slug(rand(100000000000, 99999999999999)), $image, "asset-tracking-post");

                $asset_tracking_post_image->image_string = $saveImage;
                $asset_tracking_post_image->save();
            }

            \DB::commit();
            $asset_tracking_post->assetTrackingPostImage;
            return prepareResult(true, $asset_tracking_post, [], "Asset Tracking post added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function indexPostList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $asset_tracking_id = $request->asset_tracking_id;

        if (!$asset_tracking_id) {
            return prepareResult(false, [], [], "Error while validating asset tracking", $this->unprocessableEntity);
        }

        $asset_tracking_post_query = AssetTrackingPost::with(
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'assetTrackingPostImage'
        )
            ->where('asset_tracking_id', $asset_tracking_id);
        if ($request->date) {
            $asset_tracking_post_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $asset_tracking_post_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $asset_tracking_post_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->all) {
            $asset_tracking_post = $asset_tracking_post_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $asset_tracking_post_query->whereDate('created_at', date('Y-m-d'));
            }
            $asset_tracking_post = $asset_tracking_post_query->orderBy('id', 'desc')->get();
        }

        $asset_tracking_post_array = array();
        if (is_object($asset_tracking_post)) {
            foreach ($asset_tracking_post as $key => $asset_tracking_post1) {
                $asset_tracking_post_array[] = $asset_tracking_post[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($asset_tracking_post_array[$offset])) {
                    $data_array[] = $asset_tracking_post_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($asset_tracking_post_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($asset_tracking_post_array);
        } else {
            $data_array = $asset_tracking_post_array;
        }

        return prepareResult(true, $data_array, [], "Asset tracking post listing", $this->success, $pagination);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'assettracking_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate Asset Tracking import", $this->unauthorized);
        }
        $errors = array();
        try {
            $file = request()->file('assettracking_file')->store('import');
            $import = new AssetTrackingImport($request->skipduplicate);
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

            return prepareResult(true, [], $errors, "Failed to validate asset tracking import", $this->success);
        }
        return prepareResult(true, [], $errors, "Asset tracking successfully imported", $this->success);
    }

    public function assetTrackingSurveyList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $asset_tracking_id = $request->asset_tracking_id;

        if (!$asset_tracking_id) {
            return prepareResult(false, [], [], "Error while validating asset tracking", $this->unprocessableEntity);
        }

        $survey_query = Survey::with(
            'surveyCustomer',
            'surveyCustomer.assetTracking'
        )
            ->where('survey_type_id', 4)
            ->whereHas('surveyCustomer.assetTracking', function ($query) use ($asset_tracking_id) {
                $query->whereId($asset_tracking_id);
            });

        if ($request->date) {
            $survey_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->name) {
            $survey_query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->start_date) {
            $survey_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $survey_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        if ($request->all) {
            $asset_tracking_post = $survey_query->get();
        } else {
            if ($request->today) {
                $survey_query->whereDate('created_at', date('Y-m-d'));
            }
            $asset_tracking_post = $survey_query->get();
        }

        $asset_tracking_post_array = array();
        if (is_object($asset_tracking_post)) {
            foreach ($asset_tracking_post as $key => $asset_tracking_post1) {
                $asset_tracking_post_array[] = $asset_tracking_post[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($asset_tracking_post_array[$offset])) {
                    $data_array[] = $asset_tracking_post_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($asset_tracking_post_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($asset_tracking_post_array);
        } else {
            $data_array = $asset_tracking_post_array;
        }

        return prepareResult(true, $data_array, [], "Asset tracking survey listing", $this->success, $pagination);
    }
}
