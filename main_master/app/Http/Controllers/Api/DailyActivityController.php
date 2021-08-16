<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DailyActivity;
use App\Model\DailyActivityCustomer;
use App\Model\DailyActivityDetail;
use App\Model\Todo;
use Illuminate\Http\Request;

class DailyActivityController extends Controller
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

        $da = DailyActivity::select('id', 'uuid', 'organisation_id', 'supervisor_id', 'lob_id', 'date', 'status')
            ->with(
                'supervisor:id,firstname,lastname',
                'lob:id,name,user_id',
                'dailyActivityCustomer:id,daily_activity_id,customer_id',
                'dailyActivityCustomer.customer:id,firstname,lastname',
                'dailyActivityCustomer.dailyActivityDetails',
            )
            ->get();

        $da_array = array();
        if (is_object($da)) {
            foreach ($da as $key => $da1) {
                $da_array[] = $da[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($da_array[$offset])) {
                    $data_array[] = $da_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($da_array) / $limit);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $da_array;
        }
        return prepareResult(true, $data_array, [], "Daily Activities listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating daily activities", $this->unprocessableEntity);
        }

        if (is_array($request->daily_activity_details) && sizeof($request->daily_activity_details) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one details.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $da = new DailyActivity;
            $da->supervisor_id = $request->supervisor_id;
            $da->lob_id = $request->lob_id;
            $da->date = $request->date;
            $da->status = $request->status;
            $da->save();

            collect($request->customers)->each(function ($customer, $key) use ($da) {
                $dac = new DailyActivityCustomer;
                $dac->daily_activity_id = $da->id;
                $dac->customer_id = $customer['customer_id'];
                $dac->shelf_display = $customer['shelf_display'];
                $dac->off_shelf_display = $customer['off_shelf_display'];
                $dac->opportunity = $customer['opportunity'];
                $dac->out_of_stock = $customer['out_of_stock'];
                $dac->remarks = $customer['remarks'];
                $dac->save();

                if ($dac->opportunity) {
                    $this->saveTodo($dac, $da, 'opportunity');
                }

                if ($dac->out_of_stock) {
                    $this->saveTodo($dac, $da, 'out_of_stock');
                }

                collect($customer['daily_activity_details'])->each(function ($detail, $dkey) use ($da, $dac) {
                    $dad = new DailyActivityDetail;
                    $dad->daily_activity_id = $da->id;
                    $dad->daily_activity_customer_id = $dac->id;
                    $dad->supervisor_category_id = $detail['supervisor_category_id'];
                    $dad->supervisor_category_status = $detail['supervisor_category_status'];
                    $dad->save();
                });
            });

            \DB::commit();
            return prepareResult(true, $da, [], "Daily Activity successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function saveTodo($dac, $da, $type)
    {
        $todo = new Todo;
        $todo->customer_id = $dac->customer_id;
        $todo->supervisor_id = $da->supervisor_id;
        if ($type == "out_of_stock") {
            $todo->task_name = $dac->out_of_stock;
        } else {
            $todo->task_name = $dac->opportunity;
        }
        $todo->date = $da->date;
        $todo->status = "in-progress";
        $todo->comment = $dac->remarks;
        $todo->save();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\DailyActivity  $dailyActivity
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Daily Activity.", $this->unauthorized);
        }

        $da = DailyActivity::select('id', 'uuid', 'organisation_id', 'customer_id', 'supervisor_id', 'lob_id', 'date', 'status')
            ->with(
                'customer:id,firstname,lastname',
                'supervisor:id,firstname,lastname',
                'dailyActivityDetails',
                'lob:id,name,user_id'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($da)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $da, [], "Daily Activity Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\DailyActivity  $dailyActivity
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DailyActivity $dailyActivity)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\DailyActivity  $dailyActivity
     * @return \Illuminate\Http\Response
     */
    public function destroy(DailyActivity $dailyActivity)
    {
        //
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'supervisor_id' => 'required|integer|exists:users,id',
                'lob_id' => 'required|integer|exists:lobs,id',
                'date' => 'required|date'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
