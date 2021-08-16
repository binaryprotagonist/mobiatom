<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SupervisorCategory;
use Illuminate\Http\Request;

class SupervistoCategoryController extends Controller
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

        $supervisor_category = SupervisorCategory::select('id', 'uuid', 'name', 'status')
            ->orderBy('name', 'asc')
            ->get();

        $supervisor_category_array = array();
        if (is_object($supervisor_category)) {
            foreach ($supervisor_category as $key => $supervisor_category1) {
                $supervisor_category_array[] = $supervisor_category[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($supervisor_category_array[$offset])) {
                    $data_array[] = $supervisor_category_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($supervisor_category_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($supervisor_category_array);
        } else {
            $data_array = $supervisor_category_array;
        }

        return prepareResult(true, $data_array, [], "Supervisor Category listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating supervisor category", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $sc = new SupervisorCategory;
            $sc->name = $request->name;
            $sc->status = $request->status;
            $sc->save();

            \DB::commit();

            return prepareResult(true, $sc, [], "Supervisor Category added successfully", $this->success);
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
     * @param  \App\Model\SupervisorCategory  $supervisorCategory
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $sc = SupervisorCategory::where('uuid', $uuid)
            ->first();

        if (!is_object($sc)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $sc, [], "Sueprvisor category Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\SupervisorCategory  $supervisorCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating supervisor category", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $sc = SupervisorCategory::where('uuid', $uuid)->first();
            $sc->name = $request->name;
            $sc->status = $request->status;
            $sc->save();

            \DB::commit();

            return prepareResult(true, $sc, [], "Supervisor Category updated successfully", $this->success);
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
     * @param  \App\Model\SupervisorCategory  $supervisorCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating supervisor category", $this->unauthorized);
        }

        $sc = SupervisorCategory::where('uuid', $uuid)
            ->first();

        if (is_object($sc)) {
            $sc->delete();
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
                'name' => 'required|string',
                'status' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
