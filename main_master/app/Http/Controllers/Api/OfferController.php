<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
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

        $offer = Offer::get();

        $offer_array = array();
        if (is_object($offer)) {
            foreach ($offer as $key => $offer1) {
                $offer_array[] = $offer[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($offer_array[$offset])) {
                    $data_array[] = $offer_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($offer_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($offer_array);
        } else {
            $data_array = $offer_array;
        }

        return prepareResult(true, $data_array, [], "Offer listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating offer", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $offer = new Offer;
            $offer->offer_name = $request->offer_name;
            $offer->offer_start_date = $request->offer_start_date;
            $offer->offer_end_date = $request->offer_end_date;
            $offer->description = $request->description;
            $offer->discount_amount = $request->discount_amount;
            $offer->discount_percentage = $request->discount_percentage;
            $offer->duration_months = $request->duration_months;
            $offer->duration_end_date = $request->duration_end_date;
            $offer->save();

            \DB::commit();
            return prepareResult(true, $offer, [], "Offer added successfully", $this->success);
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
    public function edit($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating offer", $this->unauthorized);
        }

        $offer = Offer::find($id);

        if (!is_object($offer)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $offer, [], "Offer Edit", $this->success);
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
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating offer", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $offer = Offer::find($id);
            $offer->offer_name = $request->offer_name;
            $offer->offer_start_date = $request->offer_start_date;
            $offer->offer_end_date = $request->offer_end_date;
            $offer->description = $request->description;
            $offer->discount_amount = $request->discount_amount;
            $offer->discount_percentage = $request->discount_percentage;
            $offer->duration_months = $request->duration_months;
            $offer->duration_end_date = $request->duration_end_date;
            $offer->save();

            \DB::commit();
            return prepareResult(true, $offer, [], "Offer update successfully", $this->success);
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
    public function destroy($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $offer = Offer::find($id);

        if (is_object($offer)) {
            $offer->delete();
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
                'offer_name' => 'required',
                'offer_start_date' => 'required|date',
                'discount_amount' => 'required',
                'discount_percentage' => 'required',
                'duration_months' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
