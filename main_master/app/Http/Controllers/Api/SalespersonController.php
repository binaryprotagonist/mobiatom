<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\SalesPerson;

class SalespersonController extends Controller
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

        $salesperson = SalesPerson::orderBy('id', 'desc')->get();

        $salesperson_array = array();
        if (is_object($salesperson)) {
            foreach ($salesperson as $key => $salesperson1) {
                $salesperson_array[] = $salesperson[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($salesperson_array[$offset])) {
                    $data_array[] = $salesperson_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($salesperson_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($salesperson_array);
        } else {
            $data_array = $salesperson_array;
        }

        return prepareResult(true, $data_array, [], "Sales person listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating sales person", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $salesperson = new SalesPerson;
            $salesperson->name         = (!empty($request->name)) ? $request->name : null;
            $salesperson->email         = (!empty($request->email)) ? $request->email : null;
            $salesperson->save();

            \DB::commit();
            return prepareResult(true, $salesperson, [], "Sales Person added successfully", $this->success);
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
                'name' => 'required',
                'email' => 'required'
            ]);
        }

        return ["error" => $error, "errors" => $errors];
    }
}
