<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Rolemenu;
use App\Model\Userrolemenu;
use App\User;

class RolemenuController extends Controller
{
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $Rolemenu = Rolemenu::select('*')
        ->get();
		
        return prepareResult(true, $Rolemenu, [], "Role menu list", $this->success,[]);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating role menu", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $Rolemenu = new Rolemenu;
            $Rolemenu->name = $request->name;
            $Rolemenu->save();

            \DB::commit();
            return prepareResult(true, $Rolemenu, [], "Role menu added successfully", $this->created);
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
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        try {
            $Rolemenu = Rolemenu::where('uuid', $uuid)
                ->first();
            return prepareResult(true, $Rolemenu, [], "Role menu Edit", $this->success);
        } catch (\Exception $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating region", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $Rolemenu = Rolemenu::where('uuid', $uuid)->first();
            $Rolemenu->name = $request->name;
            $Rolemenu->save();

            \DB::commit();
            return prepareResult(true, $Rolemenu, [], "Role menu updated successfully", $this->created);
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
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        try {
            $Rolemenu = Rolemenu::where('uuid', $uuid)->delete();
            return prepareResult(true, [], [], "Role menu deleted successfully", $this->created);
        } catch (\Exception $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }
	
	public function editrolemenu($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
		
		$Rolemenu = Rolemenu::select('id','name')
        ->get();
		if(is_object($Rolemenu)){
			foreach($Rolemenu as $key=>$menu){
				$Userrolemenu = Userrolemenu::where('role_id',$id)
				->where('menu_id',$menu->id)
				->first();
				if(is_object($Userrolemenu)){
					$Rolemenu[$key]->enable = 1;
				}else{
					$Rolemenu[$key]->enable = 0;
				}
			}
		}
		
        
		
        return prepareResult(true, $Rolemenu, [], "Role menu list", $this->success,[]);
    }
	public function updaterolemenu(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "rolemenuupdate");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating role menu", $this->unprocessableEntity);
        }
		
		if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please select atleast one menu.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

			Userrolemenu::where('role_id',$request->role_id)->delete();
			foreach($request->items as $item){
				$Userrolemenu = new Userrolemenu;
				$Userrolemenu->role_id = $request->role_id;
				$Userrolemenu->menu_id = $item['id'];
				$Userrolemenu->save();
			}

            \DB::commit();
            return prepareResult(true, $Userrolemenu, [], "Role menu updated successfully", $this->created);
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
                'name'  => 'required'
            ]);
        }

		if($type == "rolemenuupdate"){
			$validator = \Validator::make($input, [
                'role_id'  => 'required'
            ]);
		}
		
        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}