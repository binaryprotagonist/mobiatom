<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Planogram;
use App\Model\PlanogramPost;
use App\Model\PlanogramPostImage;
use App\Model\PlanogramPostAfterImage;
use App\Model\PlanogramPostBeforeImage;
use App\Model\Reason;
use Illuminate\Http\Request;

class PlanogramPostController extends Controller
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

        $planogram_id = $request->planogram_id;

        // if (!$planogram_id) {
        //     return prepareResult(false, [], [], "Error while validating planogram post", $this->unprocessableEntity);
        // }

        $planogram_post_query = PlanogramPost::with(
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'planogram:id,name',
            'distribution:id,name,start_date,end_date,height,width,depth',
            'planogramPostBeforeImage',
            'planogramPostAfterImage'
        );

        if ($planogram_id) {
            $planogram_post_query->where('planogram_id', $planogram_id);
        }

        if ($request->date) {
            $planogram_post_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $planogram_post_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $planogram_post_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $planogram_post_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $planogram_post_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $planogram_post_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->distribution_name) {
            $name = $request->distribution_name;
            $planogram_post_query->whereHas('distribution', function ($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%');
            });
        }

        $planogram_post = $planogram_post_query->orderBy('id', 'desc')->get();


        $planogram_post_array = array();
        if (is_object($planogram_post)) {
            foreach ($planogram_post as $key => $planogram_post1) {
                $planogram_post_array[] = $planogram_post[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($planogram_post_array[$offset])) {
                    $data_array[] = $planogram_post_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($planogram_post_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($planogram_post_array);
        } else {
            $data_array = $planogram_post_array;
        }

        return prepareResult(true, $data_array, [], "Planogram Post listing", $this->success, $pagination);

        // return prepareResult(true, $planogram_post, [], "Planogram Post listing", $this->success);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexByID(Request $request, $planogram_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $planogram_id = $request->planogram_id;

        $planogram_post_query = PlanogramPost::with(
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'planogram:id,name',
            'distribution:id,name,start_date,end_date,height,width,depth',
            'planogramPostBeforeImage',
            'planogramPostAfterImage'
        );

        if ($planogram_id) {
            $planogram_post_query->where('planogram_id', $planogram_id);
        }
        if ($request->date) {
            $planogram_post_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $planogram_post_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $planogram_post_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $planogram_post_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $planogram_post_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $planogram_post_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->distribution_name) {
            $name = $request->distribution_name;
            $planogram_post_query->whereHas('distribution', function ($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%');
            });
        }

        $planogram_post = $planogram_post_query->orderBy('id', 'desc')->get();

        return prepareResult(true, $planogram_post, [], "Planogram Post listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating planogram post", $this->success);
        }

        \DB::beginTransaction();
        try {

            $planogram_post = new PlanogramPost;
            $planogram_post->salesman_id = $request->salesman_id;
            $planogram_post->customer_id = $request->customer_id;
            $planogram_post->distribution_id = $request->distribution_id;
            $planogram_post->trip_id = $request->trip_id;
            $planogram_post->planogram_id = $request->planogram_id;
            $planogram_post->description = $request->description;
            $planogram_post->status = $request->status;
            $planogram_post->feedback = $request->feedback;
            $planogram_post->score = $request->score;
            $planogram_post->save();

            if (is_array($request->before_images) && sizeof($request->before_images) >= 1) {
                foreach ($request->before_images as $ppImage) {
                    $ppi = new PlanogramPostBeforeImage;

                    $destinationPath    = 'uploads/planogram-post/';
                    $image_name = \Str::slug('plan_post' . rand(100000000000, 99999999999999));
                    $image = $ppImage;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $ppi->image_string           = URL('/') . '/' . $destinationPath . $fileName;
                    $ppi->planogram_post_id = $planogram_post->id;
                    $ppi->save();
                }
            }

            if (is_array($request->after_images) && sizeof($request->after_images) >= 1) {
                foreach ($request->after_images as $ppImage) {
                    $ppi = new PlanogramPostAfterImage;

                    $destinationPath    = 'uploads/planogram-post/';
                    $image_name = \Str::slug('plan_post' . rand(100000000000, 99999999999999));
                    $image = $ppImage;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $ppi->image_string           = URL('/') . '/' . $destinationPath . $fileName;
                    $ppi->planogram_post_id = $planogram_post->id;
                    $ppi->save();
                }
            }

            \DB::commit();

            $planogram_post->getSaveData();

            return prepareResult(true, $planogram_post, [], "Planogram Post added successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating reason", $this->unauthorized);
        }

        $planogram_post = PlanogramPost::select('id', 'uuid', 'organisation_id', 'trip_id', 'salesman_id', 'customer_id', 'distribution_id', 'planogram_id', 'description', 'feedback', 'score', 'status')
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'planogram:id,name',
                'distribution:id,start_date,end_date,height,weight,depth,capacity',
                'planogramPostBeforeImage',
                'planogramPostAfterImage'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($planogram_post)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $planogram_post, [], "Planogram post Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating planogram post", $this->success);
        }

        \DB::beginTransaction();
        try {

            $planogram_post = PlanogramPost::where('uuid', $uuid)->first();
            $planogram_post->salesman_id = $request->salesman_id;
            $planogram_post->customer_id = $request->customer_id;
            $planogram_post->distribution_id = $request->distribution_id;
            $planogram_post->trip_id = $request->trip_id;
            $planogram_post->planogram_id = $request->planogram_id;
            $planogram_post->description = $request->description;
            $planogram_post->feedback = $request->feedback;
            $planogram_post->scrore = $request->scrore;
            $planogram_post->status = $request->status;
            $planogram_post->save();

            if (is_array($request->before_images) && sizeof($request->before_images) >= 1) {
                PlanogramPostBeforeImage::where('planogram_post_id', $planogram_post->id)->delete();
                foreach ($request->before_images as $ppImage) {
                    $ppi = new PlanogramPostBeforeImage;

                    $destinationPath    = 'uploads/planogram-post/';
                    $image_name = \Str::slug('plan_post' . rand(100000000000, 99999999999999));
                    $image = $ppImage;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $ppi->image_string           = URL('/') . '/' . $destinationPath . $fileName;
                    $ppi->planogram_post_id = $planogram_post->id;
                    $ppi->save();
                }
            }

            if (is_array($request->after_images) && sizeof($request->after_images) >= 1) {
                PlanogramPostAfterImage::where('planogram_post_id', $planogram_post->id)->delete();
                foreach ($request->after_images as $ppImage) {
                    $ppi = new PlanogramPostAfterImage;

                    $destinationPath    = 'uploads/planogram-post/';
                    $image_name = \Str::slug('plan_post' . rand(100000000000, 99999999999999));
                    $image = $ppImage;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $ppi->image_string           = URL('/') . '/' . $destinationPath . $fileName;
                    $ppi->planogram_post_id = $planogram_post->id;
                    $ppi->save();
                }
            }

            \DB::commit();

            $planogram_post->getSaveData();

            return prepareResult(true, $planogram_post, [], "Planogram Post added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating Reason", $this->unauthorized);
        }

        $planogram_post = PlanogramPost::where('uuid', $uuid)
            ->first();

        if (is_object($planogram_post)) {
            $planogram_post->delete();
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
                'customer_id' => 'required|integer|exists:users,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'distribution_id' => 'required|integer|exists:distributions,id',
                'planogram_id' => 'required|integer|exists:planograms,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
