<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SalesmanActivityProfile;
use App\Model\SalesmanActivityProfileDetail;
use Illuminate\Http\Request;

class SalesmanActivityProfileController extends Controller
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

        $sap = SalesmanActivityProfile::with(
            'salesmanActivityProfileDetail:id,salesman_activity_profile_id,module_name,status,priority',
            "customer:id,firstname,lastname",
            "customer.customerInfo:id,user_id,customer_code",
            "salesman:id,firstname,lastname",
            "salesman.salesmanInfo:id,user_id,salesman_code"
        )
            // ->whereDate('valid_from', '>=', date('Y-m-d'))
            // ->whereDate('valid_to', '<=', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        $sap_array = array();
        if (is_object($sap)) {
            foreach ($sap as $key => $sap1) {
                $sap_array[] = $sap[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($sap_array[$offset])) {
                    $data_array[] = $sap_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($sap_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($sap_array);
        } else {
            $data_array = $sap_array;
        }

        return prepareResult(true, $data_array, [], "Salesman Activity Profile listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  id customer id / merchandiser id
     * @return \Illuminate\Http\Response
     */
    public function indexBymc($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $sap = SalesmanActivityProfile::with(
            'salesmanActivityProfileDetail:id,salesman_activity_profile_id,module_name,status,priority',
            "customer:id,firstname,lastname",
            "customer.customerInfo:id,user_id,customer_code",
            "salesman:id,firstname,lastname",
            "salesman.salesmanInfo:id,user_id,salesman_code"
        )
            ->whereBetween('valid_from', [date('Y-m-d'), date('Y-m-d')])
            // ->whereDate('valid_from', '>=', date('Y-m-d'))
            // ->whereDate('valid_to', '<=', date('Y-m-d'))
            ->where('customer_id', $id)
            ->orWhere('merchandiser_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $sap_array = array();
        if (is_object($sap)) {
            foreach ($sap as $key => $sap1) {
                $sap_array[] = $sap[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($sap_array[$offset])) {
                    $data_array[] = $sap_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($sap_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($sap_array);
        } else {
            $data_array = $sap_array;
        }

        return prepareResult(true, $data_array, [], "Salesman Activity Profile listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating salesman activity profile", $this->unprocessableEntity);
        }

        if (is_array($request->details) && sizeof($request->details)  < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one salesman activity profile detail", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $sap = new SalesmanActivityProfile;
            $sap->merchandiser_id = $request->merchandiser_id;
            $sap->customer_id = $request->customer_id;
            $sap->activity_name = $request->activity_name;
            $sap->valid_from = $request->valid_from;
            $sap->valid_to = $request->valid_to;
            $sap->save();

            foreach ($request->details as $detail) {
                $sapd = new SalesmanActivityProfileDetail;
                $sapd->salesman_activity_profile_id = $sap->id;
                $sapd->module_name = $detail['module_name'];
                $sapd->status = $detail['status'];
                $sapd->priority = $detail['priority'];
                $sapd->save();
            }

            \DB::commit();
            $sap->salesmanActivityProfileDetail;

            return prepareResult(true, $sap, [], "Salesman Activity Profile added successfully", $this->created);
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
     * @param  uuid  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating salesman activity profile.", $this->unprocessableEntity);
        }

        $spa = SalesmanActivityProfile::with(
            'salesmanActivityProfileDetail:id,salesman_activity_profile_id,module_name,status,priority',
            "customer:id,firstname,lastname",
            "customer.customerInfo:id,user_id,customer_code",
            "salesman:id,firstname,lastname",
            "salesman.salesmanInfo:id,user_id,salesman_code"
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($spa)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $spa, [], "Salesman activity profile Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating salesman activity profile.", $this->unprocessableEntity);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating salesman activity profile", $this->unprocessableEntity);
        }

        if (is_array($request->details) && sizeof($request->details)  < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one salesman activity profile detail", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $sap = SalesmanActivityProfile::where('uuid', $uuid)->first();
            SalesmanActivityProfileDetail::where('salesman_activity_profile_id', $sap->id)->delete();

            $sap->merchandiser_id = $request->merchandiser_id;
            $sap->customer_id = $request->customer_id;
            $sap->activity_name = $request->activity_name;
            $sap->valid_from = $request->valid_from;
            $sap->valid_to = $request->valid_to;
            $sap->save();

            foreach ($request->details as $detail) {
                $sapd = new SalesmanActivityProfileDetail;
                $sapd->salesman_activity_profile_id = $sap->id;
                $sapd->module_name = $detail['module_name'];
                $sapd->status = $detail['status'];
                $sapd->priority = $detail['priority'];
                $sapd->save();
            }

            \DB::commit();
            $sap->salesmanActivityProfileDetail;

            return prepareResult(true, $sap, [], "Salesman Activity Profile updated successfully", $this->success);
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

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating salesman activity profile.", $this->unprocessableEntity);
        }

        $sap = SalesmanActivityProfile::where('uuid', $uuid)->first();

        if (is_object($sap)) {
            $sap->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'activity_name' => 'required',
                'valid_from' => 'required|date',
                'valid_to' => 'required|date|after:valid_from'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
