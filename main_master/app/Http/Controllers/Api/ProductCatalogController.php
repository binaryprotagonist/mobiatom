<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\ProductCatalog;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
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

        $product_catalog = ProductCatalog::with('item:id,item_name')->orderBy('id', 'desc')->get();

        $product_catalog_array = array();
        if (is_object($product_catalog)) {
            foreach ($product_catalog as $key => $product_catalog1) {
                $product_catalog_array[] = $product_catalog[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($product_catalog_array[$offset])) {
                    $data_array[] = $product_catalog_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($product_catalog_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($product_catalog_array);
        } else {
            $data_array = $product_catalog_array;
        }

        return prepareResult(true, $data_array, [], "Product catalog listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating product catalog", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $product_catalog = new ProductCatalog;
            $product_catalog->barcode = $request->barcode;
            $product_catalog->item_id = $request->item_id;
            $product_catalog->net_weight = $request->net_weight;
            $product_catalog->flawer = $request->flawer;
            $product_catalog->shelf_file = $request->shelf_file;
            $product_catalog->ingredients = $request->ingredients;
            $product_catalog->energy = $request->energy;
            $product_catalog->fat = $request->fat;
            $product_catalog->protein = $request->protein;
            $product_catalog->carbohydrate = $request->carbohydrate;
            $product_catalog->calcium = $request->calcium;
            $product_catalog->sodium = $request->sodium;
            $product_catalog->potassium = $request->potassium;
            $product_catalog->crude_fibre = $request->crude_fibre;
            $product_catalog->vitamin = $request->vitamin;

            if ($request->image) {
                    $destinationPath    = 'uploads/product-catalog/';
                    $image_name = $request->barcode;
                    $image = $request->image;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $product_catalog->image_string           = URL('/') . '/' . $destinationPath . $fileName;
            }

            $product_catalog->save();

            \DB::commit();
            return prepareResult(true, $product_catalog, [], "Product Catalog added successfully", $this->created);
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
     * @param  $uuid $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $product_catalog = ProductCatalog::where('uuid', $uuid)->first();
        
        return prepareResult(true, $product_catalog, [], "Product Catalog listing", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating product catalog", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $product_catalog = ProductCatalog::where('uuid', $uuid)->first();
            $product_catalog->barcode = $request->barcode;
            $product_catalog->item_id = $request->item_id;
            $product_catalog->net_weight = $request->net_weight;
            $product_catalog->flawer = $request->flawer;
            $product_catalog->shelf_file = $request->shelf_file;
            $product_catalog->ingredients = $request->ingredients;
            $product_catalog->energy = $request->energy;
            $product_catalog->fat = $request->fat;
            $product_catalog->protein = $request->protein;
            $product_catalog->carbohydrate = $request->carbohydrate;
            $product_catalog->calcium = $request->calcium;
            $product_catalog->sodium = $request->sodium;
            $product_catalog->potassium = $request->potassium;
            $product_catalog->crude_fibre = $request->crude_fibre;
            $product_catalog->vitamin = $request->vitamin;

            if ($request->image) {
                    $destinationPath    = 'uploads/product-catalog/';
                    $image_name = $request->barcode;
                    $image = $request->image;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $product_catalog->image_string           = URL('/') . '/' . $destinationPath . $fileName;
            }

            $product_catalog->save();

            \DB::commit();
            return prepareResult(true, $product_catalog, [], "Product Catalog updated successfully", $this->created);
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
     * @param  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        // if (!checkPermission('region-delete')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating product catalog", $this->unauthorized);
        }

        $product_catalog = ProductCatalog::where('uuid', $uuid)
            ->first();

        if (is_object($product_catalog)) {
            $product_catalog->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access.", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                // 'campaign_id' => 'required',
                // 'feedback' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
