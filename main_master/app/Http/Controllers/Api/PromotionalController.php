<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Promotional;
use App\Model\PromotionalPost;
use App\Model\PromotionalPostItem;
use Illuminate\Http\Request;

class PromotionalController extends Controller
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

        $promotional_query = Promotional::with('item:id,item_name,item_code');
        if ($request->date) {
            $promotional_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
            $promotional = $promotional_query->orderBy('id', 'desc')->get();
        } else if ($request->all) {
            $promotional = $promotional_query->orderBy('id', 'desc')->get();
        } else {
            $promotional_query->whereDate('created_at', date('Y-m-d'));
            $promotional = $promotional_query->orderBy('id', 'desc')->get();
        }

        $promotional_array = array();
        if (is_object($promotional)) {
            foreach ($promotional as $key => $promotional1) {
                $promotional_array[] = $promotional[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($promotional_array[$offset])) {
                    $data_array[] = $promotional_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($promotional_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($promotional_array);
        } else {
            $data_array = $promotional_array;
        }

        return prepareResult(true, $data_array, [], "Promotional listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexMobile()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $promotional = Promotional::select('id', 'uuid', 'organisation_id', 'item_id', 'amount', 'start_date', 'end_date')
        ->with('item:id,item_name,item_code')
        ->whereDate('start_date', '<=', date('Y-m-d'))
        ->whereDate('end_date', '>=', date('Y-m-d'))
        ->orderBy('id', 'desc')
        ->get();

        $promotional_array = array();
        if (is_object($promotional)) {
            foreach ($promotional as $key => $promotional1) {
                $promotional_array[] = $promotional[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($promotional_array[$offset])) {
                    $data_array[] = $promotional_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($promotional_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($promotional_array);
        } else {
            $data_array = $promotional_array;
        }

        return prepareResult(true, $data_array, [], "Promotional listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Promotional", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $promotional = new Promotional;
            $promotional->item_id = $request->item_id;
            $promotional->amount = $request->amount;
            $promotional->start_date = $request->start_date;
            $promotional->end_date = $request->end_date;
            $promotional->save();

            \DB::commit();
            $promotional->item;
            return prepareResult(true, $promotional, [], "Promotional successfully", $this->created);
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
     * @param  \App\Model\Promotional  $promotional
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating distribution model stock", $this->unauthorized);
        }

        $promotional = Promotional::where('uuid', $uuid)
        ->with('item:id,item_name')
            ->first();

        if (!is_object($promotional)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $promotional, [], "Promotional Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Promotional  $promotional
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $edit)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Promotional", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $promotional = Promotional::where('uuid', $edit)->first();
            $promotional->item_id = $request->item_id;
            $promotional->amount = $request->amount;
            $promotional->start_date = $request->start_date;
            $promotional->end_date = $request->end_date;
            $promotional->save();

            \DB::commit();
            $promotional->item;
            return prepareResult(true, $promotional, [], "Promotional successfully", $this->created);
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
     * @param  \App\Model\Promotional  $promotional
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating distribution model stock.", $this->unauthorized);
        }

        $promotional = Promotional::where('uuid', $uuid)
            ->first();

        if (is_object($promotional)) {
            $promotional->delete();
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
                'item_id' => 'required|integer|exists:items,id',
                'amount' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "addPost") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer|exists:users,id',
                'cusotmer' => 'required|string',
                'invoice_code' => 'required',
                'phone' => 'required',
                'amount_spend' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexPromotionalPost(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $promotional_query = PromotionalPost::with(
            'promotional',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'promotionalPostItem.item:id,item_name'
        );
        if ($request->date) {
            $promotional_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
            $promotional = $promotional_query->orderBy('id', 'desc')->get();
        } else if ($request->all) {
            $promotional = $promotional_query->orderBy('id', 'desc')->get();
        } else {
            $promotional_query->whereDate('created_at', date('Y-m-d'));
            $promotional = $promotional_query->orderBy('id', 'desc')->get();
        }

        $promotional_array = array();
        if (is_object($promotional)) {
            foreach ($promotional as $key => $promotional1) {
                $promotional_array[] = $promotional[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($promotional_array[$offset])) {
                    $data_array[] = $promotional_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($promotional_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($promotional_array);
        } else {
            $data_array = $promotional_array;
        }

        return prepareResult(true, $data_array, [], "Promotional post listing", $this->success, $pagination);

        // return prepareResult(true, $promotional, [], "Promotional post listing", $this->success);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePormotionalPost(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "addPost");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Promotional", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one item.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $promotional_post = new PromotionalPost;
            $promotional_post->promotional_id = $request->promotional_id;
            $promotional_post->cusotmer = $request->cusotmer;
            $promotional_post->salesman_id = $request->salesman_id;
            $promotional_post->trip_id = $request->trip_id;
            $promotional_post->invoice_code = $request->invoice_code;
            $promotional_post->phone = $request->phone;
            $promotional_post->amount_spend = $request->amount_spend;
            if ($request->image) {
                $destinationPath    = 'uploads/promotional-post/';
                $image_name = \Str::slug(rand(100000000000, 99999999999999));
                $image = $request->image;
                $getBaseType = explode(',', $image);
                $getExt = explode(';', $image);
                $image = str_replace($getBaseType[0] . ',', '', $image);
                $image = str_replace(' ', '+', $image);
                $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                \File::put($destinationPath . $fileName, base64_decode($image));
                $promotional_post->image           = URL('/') . '/' . $destinationPath . $fileName;

            }
            $promotional_post->save();

            foreach ($request->items as $item) {
                $promotional_post_item = new PromotionalPostItem;
                $promotional_post_item->promotional_post_id = $promotional_post->id;
                $promotional_post_item->item_id = $item;
                $promotional_post_item->save();
            }

            \DB::commit();
            $promotional_post->promotionalPostItem;
            return prepareResult(true, $promotional_post, [], "Promotional post successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }
}
