<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SessionEndorsement;
use App\Model\SessionEndorsementRequest;
use Illuminate\Http\Request;

class SessionEndorsementController extends Controller
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

        $session_endorsement_query = SessionEndorsement::with(
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'supervisor:id,firstname,lastname',
            'route:id,route_name,route_code'
        );

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $session_endorsement_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $session_endorsement_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->supervisor_name) {
            $name = $request->supervisor_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $session_endorsement_query->whereHas('supervisor', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $session_endorsement_query->whereHas('supervisor', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->salesman_code) {
            $salesman_code = $request->salesman_code;
            $session_endorsement_query->whereHas('salesman.salesmanInfo', function ($q) use ($salesman_code) {
                $q->where('salesman_code', 'like', '%' . $salesman_code . '%');
            });
        }

        if ($request->route) {
            $route = $request->route;
            $session_endorsement_query->whereHas('route', function ($q) use ($route) {
                $q->where('route_name', 'like', '%' . $route . '%');
            });
        }


        $session_endorsement = $session_endorsement_query->orderBy('id', 'desc')
            ->get();

        $session_endorsement_array = array();
        if (is_object($session_endorsement)) {
            foreach ($session_endorsement as $key => $session_endorsement1) {
                $session_endorsement_array[] = $session_endorsement[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($session_endorsement_array[$offset])) {
                    $data_array[] = $session_endorsement_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($session_endorsement_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($session_endorsement_array);
        } else {
            $data_array = $session_endorsement_array;
        }

        return prepareResult(true, $data_array, [], "Session Endorsement listing", $this->success, $pagination);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Session Endorsement", $this->unprocessableEntity);
        }

        $ser = SessionEndorsementRequest::where('salesman_id', $request->salesman_id)
            ->where('route_id', $request->route_id)
            ->whereDate('created_at', $request->date)
            ->first();

        if (!is_object($ser)) {
            return prepareResult(false, [], [], "The Salesman is not requested for endorsement", $this->not_found);
        }

        return prepareResult(true, $ser, [], "Sesstion Endorsment successfully", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Session Endorsement", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $ser = SessionEndorsementRequest::where('salesman_id', $request->salesman_id)
                ->where('route_id', $request->route_id)
                ->whereDate('created_at', $request->date)
                ->first();

            if (!is_object($ser)) {
                return prepareResult(false, [], [], "The Salesman is not requested for endorsement", $this->not_found);
            }

            $se = new SessionEndorsement;
            $se->salesman_id = $ser->salesman_id;
            $se->supervisor_id = $ser->supervisor_id;
            $se->route_id = $ser->route_id;
            $se->trip_id = $ser->trip_id;
            $se->date = date('Y-m-d');
            $se->status = "Approved";
            $se->save();

            \DB::commit();

            return prepareResult(true, $se, [], "Sesstion Endorsment successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer|exists:users,id',
                'route_id' => 'required|integer|exists:routes,id',
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
