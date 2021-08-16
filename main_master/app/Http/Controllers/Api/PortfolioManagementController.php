<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use App\Model\PortfolioManagement;
use App\Model\PortfolioManagementCustomer;
use App\Model\PortfolioManagementItem;
use Illuminate\Http\Request;

class PortfolioManagementController extends Controller
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

        $portfolio_management_query = PortfolioManagement::select('id', 'uuid', 'organisation_id', 'name', 'code', 'start_date', 'end_date')
            ->with(
                'portfolioManagementCustomer:id,portfolio_management_id,user_id',
                'portfolioManagementCustomer.user:id,firstname,lastname',
                'portfolioManagementCustomer.user.customerInfo:id,user_id,customer_code',
                'portfolioManagementItem:id,portfolio_management_id,item_id,listing_fees,store_price',
                'portfolioManagementItem.item:id,item_name,item_code'
            );

            if ($request->code) {
                $portfolio_management_query->where('code', $request->code);
            }

            if ($request->name) {
                $portfolio_management_query->where('name', 'like', '%' . $request->name . '%');
            }

            if ($request->start_date) {
                $portfolio_management_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
            }

            if ($request->end_date) {
                $portfolio_management_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
            }

            $portfolio_management = $portfolio_management_query->orderBy('id', 'desc')
            ->get();

        $portfolio_management_array = array();
        if (is_object($portfolio_management)) {
            foreach ($portfolio_management as $key => $portfolio_management1) {
                $portfolio_management_array[] = $portfolio_management[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($portfolio_management_array[$offset])) {
                    $data_array[] = $portfolio_management_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($portfolio_management_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($portfolio_management_array);
        } else {
            $data_array = $portfolio_management_array;
        }

        return prepareResult(true, $portfolio_management, [], "Portfolio Management listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Portfolio management", $this->success);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        if (is_array($request->customers) && sizeof($request->customers) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $portfolio_management = new PortfolioManagement;
            $portfolio_management->name = $request->name;
            $portfolio_management->code = nextComingNumber('App\Model\PortfolioManagement', 'portfolio', 'code', $request->code);
            $portfolio_management->start_date = $request->start_date;
            $portfolio_management->end_date = $request->end_date;
            $portfolio_management->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //save PortfolioManagementItem
                    $portfolio_management_item = new PortfolioManagementItem;
                    $portfolio_management_item->portfolio_management_id = $portfolio_management->id;
                    $portfolio_management_item->item_id = $item['item_id'];
                    $portfolio_management_item->store_price = $item['store_price'];
                    $portfolio_management_item->listing_fees = $item['listing_fees'];
                    $portfolio_management_item->save();
                }
            }

            if (is_array($request->customers)) {
                foreach ($request->customers as $user) {
                    //save PortfolioManagementCustomer
                    $portfolio_management_customer = new PortfolioManagementCustomer;
                    $portfolio_management_customer->portfolio_management_id = $portfolio_management->id;
                    $portfolio_management_customer->user_id = $user['customer_id'];
                    $portfolio_management_customer->save();
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\PortfolioManagement', 'portfolio');

            $portfolio_management->getSaveData();

            return prepareResult(true, $portfolio_management, [], "Portfolio management added successfully", $this->success);
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
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Portfolio Management", $this->unauthorized);
        }

        $portfolio_management = PortfolioManagement::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'name', 'code', 'start_date', 'end_date')
            ->with(
                'portfolioManagementCustomer:id,portfolio_management_id,user_id',
                'portfolioManagementCustomer.user:id,firstname,lastname',
                'portfolioManagementCustomer.user.customerInfo:id,user_id,customer_code',
                'portfolioManagementItem:id,portfolio_management_id,item_id,listing_fees,store_price',
                'portfolioManagementItem.item:id,item_name'
            )
            ->first();

        if (!is_object($portfolio_management)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $portfolio_management, [], "Portfolio Management Edit", $this->success);
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

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Portfolio Management", $this->success);
        }

        \DB::beginTransaction();
        try {
            $portfolio_management = PortfolioManagement::where('uuid', $uuid)
                ->first();

            if (!is_object($portfolio_management)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            PortfolioManagementItem::where('portfolio_management_id', $portfolio_management->id)
                ->delete();
            PortfolioManagementCustomer::where('portfolio_management_id', $portfolio_management->id)
                ->delete();

            $portfolio_management->name = $request->name;
            $portfolio_management->code = $request->code;
            $portfolio_management->start_date = $request->start_date;
            $portfolio_management->end_date = $request->end_date;
            $portfolio_management->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //save PortfolioManagementItem
                    $portfolio_management_item = new PortfolioManagementItem;
                    $portfolio_management_item->portfolio_management_id = $portfolio_management->id;
                    $portfolio_management_item->item_id = $item['item_id'];
                    $portfolio_management_item->store_price = $item['store_price'];
                    $portfolio_management_item->listing_fees = $item['listing_fees'];
                    $portfolio_management_item->save();
                }
            }

            if (is_array($request->customers)) {
                foreach ($request->customers as $user) {
                    //save PortfolioManagementCustomer
                    $portfolio_management_customer = new PortfolioManagementCustomer;
                    $portfolio_management_customer->portfolio_management_id = $portfolio_management->id;
                    $portfolio_management_customer->user_id = $user['customer_id'];
                    $portfolio_management_customer->save();
                }
            }

            \DB::commit();

            $portfolio_management->getSaveData();

            return prepareResult(true, $portfolio_management, [], "Portfolio Management updated successfully", $this->success);
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
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Portfolio Management", $this->unauthorized);
        }

        $portfolio_management = PortfolioManagement::where('uuid', $uuid)
            ->first();

        if (is_object($portfolio_management)) {
            $portfolio_management->delete();
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
                'name' => 'required',
                'code' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
