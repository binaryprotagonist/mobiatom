<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomerInfo;
use App\Model\Distribution;
use App\Model\DistributionCustomer;
use App\Model\ImportTempFile;
use App\Model\Planogram;
use App\Model\PlanogramImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use stdClass;
use App\Imports\PlanogramImport;
use App\Model\PlanogramCustomer;
use App\Model\PlanogramDistribution;
use App\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use File;
use URL;

class PlanogramController extends Controller
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

        if (!checkPermission('planogram-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('planogram-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $planogram_query = Planogram::select('id', 'uuid', 'organisation_id', 'name', 'start_date', 'end_date', 'status')
            ->with(
                'planogramCustomer:id,planogram_id,customer_id',
                'planogramCustomer.customer:id,firstname,lastname',
                'planogramCustomer.customer.customerInfo:id,user_id,customer_code',
                'planogramCustomer.planogramDistribution',
                'planogramCustomer.planogramDistribution.distribution:id,name',
                'planogramCustomer.planogramDistribution.planogramImages'

            );

            if ($request->name) {
                $planogram_query->where('name', $request->name);
            }

            if ($request->start_date) {
                $planogram_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
            }

            if ($request->end_date) {
                $planogram_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
            }

            $planogram = $planogram_query->orderBy('id', 'desc')
            ->get();

        // if (count($planogram)) {
        //     foreach ($planogram as $key => $p) {
        //         if (count($p->planogramCustomer)) {
        //             foreach ($p->planogramCustomer as $key => $customer) {
        //                 $p->planogramCustomer[$key]->distribution = $p->planogramDistribution;
        //             }
        //         }
        //     }
        // }

        $planogram_array = array();
        if (is_object($planogram)) {
            foreach ($planogram as $key => $planogram1) {
                $planogram_array[] = $planogram[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($planogram_array[$offset])) {
                    $data_array[] = $planogram_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($planogram_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($planogram_array);
        } else {
            $data_array = $planogram_array;
        }

        return prepareResult(true, $data_array, [], "Planogram listing", $this->success, $pagination);

        // return prepareResult(true, $planogram, [], "Planogram listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating planogram", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $planogram = new Planogram;
            $planogram->name = $request->name;
            $planogram->start_date = $request->start_date;
            $planogram->end_date = $request->end_date;
            $planogram->status = $request->status;
            $planogram->save();

            if (is_array($request->customer_distribution) && sizeof($request->customer_distribution) >= 1) {
                foreach ($request->customer_distribution as $customer) {
                    $pc = new PlanogramCustomer;
                    $pc->planogram_id = $planogram->id;
                    $pc->customer_id = $customer['customer_id'];
                    $pc->save();

                    if (is_array($customer['distribution']) && sizeof($customer['distribution']) >= 1) {
                        foreach ($customer['distribution'] as $distribution) {
                            $pd = new PlanogramDistribution;
                            $pd->planogram_id = $planogram->id;
                            $pd->distribution_id = $distribution['distribution_id'];
                            $pd->customer_id = $pc->customer_id;
                            $pd->planogram_customer_id = $pc->id;
                            $pd->save();

                            if (is_array($distribution['images']) && sizeof($distribution['images']) >= 1) {
                                foreach ($distribution['images'] as $image) {
                                    $pi = new PlanogramImage;
                                    $image_string = saveImage(Str::slug(rand(100000000000, 99999999999999)), $image, 'planogram-image');
                                    $pi->planogram_id = $planogram->id;
                                    $pi->planogram_distribution_id = $pd->id;
                                    $pi->image_string           = $image_string;
                                    $pi->save();
                                }
                            }
                        }
                    }
                }
            }

            // if (is_array($request->customer_ids) && sizeof($request->customer_ids) >= 1) {
            //     foreach ($request->customer_ids as $customer) {
            //     }
            // }

            // if (is_array($request->customer_distribution) && sizeof($request->customer_distribution) >= 1) {
            //     foreach ($request->customer_distribution as $cdKey => $cd) {
            //         if (is_array($cd['distribution']) && sizeof($cd['distribution']) >= 1) {
            //             foreach ($cd['distribution'] as $dKey => $distribution) {
            //                 $pd = new PlanogramDistribution;
            //                 $pd->planogram_id = $planogram->id;
            //                 $pd->distribution_id = $distribution['distribution_id'];
            //                 $pd->save();
            //                 if (is_array($distribution['images']) && sizeof($distribution['images']) >= 1) {
            //                     foreach ($distribution['images'] as $image) {
            //                         $pi = new PlanogramImage;
            //                         $image_string = saveImage(Str::slug(rand(100000000000, 99999999999999)), $image, 'planogram-image');
            //                         $pi->planogram_id = $planogram->id;
            //                         $pi->planogram_distribution_id = $pd->id;
            //                         $pi->image_string           = $image_string;
            //                         $pi->save();
            //                     }
            //                 }
            //             }
            //         }
            //     }
            // }

            \DB::commit();

            $planogram->getData();

            return prepareResult(true, $planogram, [], "Planogram added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating planogram", $this->unauthorized);
        }

        $planogram = Planogram::select('id', 'uuid', 'organisation_id', 'name', 'start_date', 'end_date', 'status')
            ->with(
                'planogramCustomer:id,planogram_id,customer_id',
                'planogramCustomer.customer:id,firstname,lastname',
                'planogramCustomer.customer.customerInfo:id,user_id,customer_code',
                'planogramCustomer.planogramDistribution',
                'planogramCustomer.planogramDistribution.planogramImages'

            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($planogram)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $planogram, [], "Planogram Edit", $this->success);
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

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating planogram", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $planogram = Planogram::where('uuid', $uuid)->first();
            PlanogramDistribution::where('planogram_id', $planogram->id)->forceDelete();
            PlanogramCustomer::where('planogram_id', $planogram->id)->forceDelete();
            // $PlanogramImage = PlanogramImage::where('planogram_id', $planogram->id)->get();
            // if (count($PlanogramImage)) {
            //     foreach ($PlanogramImage as $image) {
            //         unlink($image);
            //     }
            // }

            $planogram->name = $request->name;
            $planogram->start_date = $request->start_date;
            $planogram->end_date = $request->end_date;
            $planogram->status = $request->status;
            $planogram->save();

            if (is_array($request->customer_distribution) && sizeof($request->customer_distribution) >= 1) {
                foreach ($request->customer_distribution as $customer) {
                    $pc = new PlanogramCustomer;
                    $pc->planogram_id = $planogram->id;
                    $pc->customer_id = $customer['customer_id'];
                    $pc->save();

                    if (is_array($customer['distribution']) && sizeof($customer['distribution']) >= 1) {
                        foreach ($customer['distribution'] as $distribution) {

                            $pd = new PlanogramDistribution;
                            $pd->planogram_id = $planogram->id;
                            $pd->distribution_id = $distribution['distribution_id'];
                            $pd->customer_id = $customer['customer_id'];
                            $pd->planogram_customer_id = $pc->id;
                            $pd->save();

                            if (is_array($distribution['images']) && sizeof($distribution['images']) >= 1) {
                                foreach ($distribution['images'] as $image) {
                                    if ($image) {
                                        $pi = new PlanogramImage;
                                        $image_string = saveImage(Str::slug(rand(100000000000, 99999999999999)), $image, 'planogram-image');
                                        $pi->planogram_id = $planogram->id;
                                        $pi->planogram_distribution_id = $pd->id;
                                        $pi->image_string           = $image_string;
                                        $pi->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            \DB::commit();

            $planogram->getData();

            return prepareResult(true, $planogram, [], "Planogram update successfully", $this->success);
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

        $planogram = Planogram::where('uuid', $uuid)
            ->first();

        if (is_object($planogram)) {
            $PlanogramImage = PlanogramImage::where('planogram_id', $planogram->id)->orderBy('id', 'desc')->get();
            // if (count($PlanogramImage)) {
            //     foreach ($PlanogramImage as $image) {
            //         unlink($image);
            //     }
            // }
            PlanogramDistribution::where('planogram_id', $planogram->id)->delete();
            PlanogramCustomer::where('planogram_id', $planogram->id)->delete();

            $planogram->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    public function planogramCustomerList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $customer_ids = $request->customer_ids;

        $user = User::select('id', 'firstname', 'lastname')
            ->with(
                'disctributionCustomer:id,customer_id,distribution_id',
                'disctributionCustomer.distribution:id,name,start_date,end_date'
            )
            ->whereIn('id', $customer_ids)
            ->orderBy('id', 'desc')
            ->get();
        // pre($user);

        // $disctribution_customer = DistributionCustomer::select('id', 'distribution_id', 'customer_id')
        //     // ->groupBy('customer_id')
        //     ->with('distribution:id,name', 'customer:id,firstname,lastname')
        //     ->whereIn('customer_id', $customer_ids)
        //     ->get();

        // $disctribution_customer = Distribution::select('id', 'name')
        // // ->groupBy('customer_id')
        // ->with('distributionCustomer:id,customer_id,distribution_id')
        // ->whereHas('distributionCustomer', function ($q) use ($customer_ids) {
        //     $q->whereIn('customer_id', $customer_ids);
        // })->get();
        // ->with('distribution:id,name', 'customer:id,firstname,lastname')
        // ->get();

        // $planograCustomer = PlanogramCustomer::select('id', 'customer_id')
        //     ->with(
        //         'customer:id,firstname,lastname',
        //         'disctributionCustomer:id,customer_id,distribution_id',
        //         'disctributionCustomer.distribution:id,name'
        //     )
        //     ->whereIn('customer_id', $customer_ids)
        //     ->get();

        if (count($user)) {
            foreach ($user as $key => $u) {
                if ($u->disctributionCustomer->count()) {
                    $discCustomers = array();
                    foreach ($u->disctributionCustomer as $discCustomer) {
                        if ($discCustomer->distribution->start_date <= date('Y-m-d') && $discCustomer->distribution->end_date >= date('Y-m-d')) {
                            $discCustomers[] = $discCustomer->distribution;
                            // if (count($discCustomers)) {
                            //     // pre($u->id, false);
                            //     foreach ($discCustomers as $dks => $dic) {
                            //         // pre($user_id, false);
                            //         $discCustomers[$dks]->id_customer = $discCustomer->customer_id;
                            //     }
                            // }
                        }
                    }
                    $user[$key]->distribution = $discCustomers;
                }
                unset($user[$key]->disctributionCustomer);
            }
        }

        if (count($user)) {
            return prepareResult(true, $user, [], "Destination Customer listing", $this->success);
        }

        return prepareResult(true, [], [], "Destination Customer listing", $this->success);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                // 'customer_id' => 'required|integer|exists:users,id',
                'name' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function planogramImage($planogram_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$planogram_id) {
            return prepareResult(false, [], [], "Error while validating planogram", $this->unauthorized);
        }

        $planogram = PlanogramImage::where('planogram_id', $planogram_id)
            ->orderBy('id', 'desc')
            ->get();

        if (is_object($planogram)) {
            return prepareResult(true, $planogram, [], "Planogram Image listing", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    public function planogramMerchandiserbyCustomer($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating planogram", $this->unauthorized);
        }

        // 'planogramCustomer:id,planogram_id,customer_id',
        // 'planogramCustomer.customer:id,firstname,lastname',
        // 'planogramCustomer.planogramDistribution',
        // 'planogramCustomer.planogramDistribution.distribution:id,name',
        // 'planogramCustomer.planogramDistribution.planogramImages'

        // $planogramCustomer = PlanogramCustomer::select('id', 'planogram_id', 'customer_id')
        //     ->with(
        //         'customer:id,firstname,lastname',
        //         'customer.customerInfo:id,merchandiser_id,user_id',
        //         'customer.customerInfo.merchandiser:id,firstname,lastname',
        //         'planogram',
        //         'planogramDistribution:id,planogram_id,distribution_id',
        //         'planogramDistribution.distribution:id,name,start_date,end_date',
        //         'planogramDistribution.planogramImages'
        //     )
        //     ->whereHas('customer.customerInfo', function ($q) use ($merchandiser_id) {
        //         $q->where('merchandiser_id', $merchandiser_id);
        //     })
        //     ->whereHas('planogram', function ($q) {
        //         $q->where('start_date', '<=', date('Y-m-d'));
        //         $q->where('end_date', '>=', date('Y-m-d'));
        //     })
        //     ->get();

        $customer_info = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                'planogramCustomer',
                'planogramCustomer.planogram',
                'planogramCustomer.planogramDistribution',
                'planogramCustomer.planogramDistribution.distribution'
                // 'planogramCustomer.planogramDistribution.planogramImages'
            )
            ->where('merchandiser_id', $merchandiser_id)
            ->whereHas('planogramCustomer.planogram', function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        // $customer_info = Planogram::select('id', 'uuid', 'organisation_id', 'name', 'start_date', 'end_date', 'status')
        // ->with(
        //     'planogramCustomer:id,planogram_id,customer_id',
        //     'planogramCustomer.customer:id,firstname,lastname',
        //     'planogramCustomer.customer.customerInfo:id,user_id,merchandiser_id',
        //     'planogramCustomer.planogramDistribution',
        //     'planogramCustomer.planogramDistribution.distribution:id,name'

        // )
        // ->where('start_date', '<=', date('Y-m-d'))
        // ->where('end_date', '>=', date('Y-m-d'))
        // ->whereHas('planogramCustomer.customer.customerInfo', function ($q) use ($merchandiser_id) {
        //     $q->where('merchandiser_id', $merchandiser_id);
        // })
        // ->get();

        $customer_info_array = array();
        if (is_object($customer_info)) {
            foreach ($customer_info as $key => $customerInfo) {
                if (count($customerInfo->planogramCustomer)) {
                    foreach ($customerInfo->planogramCustomer as $pc => $planogram_customer) {
                        if (count($planogram_customer->planogramDistribution)) {
                            foreach ($planogram_customer->planogramDistribution as $pldKey => $planogram_distribution) {
                                $planograImage = PlanogramImage::where('planogram_id', $planogram_distribution->planogram_id)
                                    ->where('planogram_distribution_id', $planogram_distribution->id)
                                    ->get();
                                $customer_info[$key]->planogramCustomer[$pc]->planogramDistribution[$pldKey]->images = $planograImage;
                            }
                        }
                    }
                }
                $customer_info_array[] = $customer_info[$key];
            }
        }
        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($customer_info_array[$offset])) {
                    $data_array[] = $customer_info_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($customer_info_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($customer_info_array);
        } else {
            $data_array = $customer_info_array;
        }

        return prepareResult(true, $data_array, [], "Planogram customer listing", $this->success, $pagination);

        // $merge_all_data = array();
        // foreach ($customer_info as $custKey => $customer) {
        //     // 1 customer
        //     $merge_data = new stdClass;
        //     $merge_data->cusotmer_id = $customer->user_id;
        //     $merge_data->user = $customer->user;
        //     $merge_data->merchandiser = $customer->merchandiser;
        //     $planograImage = array();
        //     foreach ($customer->planogram as $aicKey => $planogram) {
        //         // 2 planogram
        //         $distribution_image = array();
        //         $merge_data->planogram = $planogram;
        //         $planogram_array = $planogram->planogramImage()->groupBy('distribution_id')->pluck('distribution_id')->toArray();

        //         if (count($planogram->planogramImage) > 0) {
        //             // 3 planogram Images
        //             foreach ($planogram->planogramImage as $pikey => $image) {
        //                 if (in_array($image->distribution_id, $planogram_array)) {

        //                     $distribution_image['distribution'] = $image->distribution;
        //                     $distribution_image['distribution']->images = array();
        //                     $distribution_image['distribution']['images'] = $image;

        //                     unset($image->distribution);
        //                 }
        //             }
        //             $planograImage[] = $distribution_image;
        //         }
        //         $merge_data->planogram->planograImages = $planograImage;
        //         unset($merge_data->planogram->planogram_image);
        //         // $merge_data->planograImages = $planograImage;
        //     }
        //     $merge_all_data[] = $merge_data;
        // }

        // $data_array = array();
        // $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        // $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        // $pagination = array();

        // if ($page && $limit) {
        //     $offset = ($page - 1) * $limit;
        //     for ($i = 0; $i < $limit; $i++) {
        //         if (isset($merge_all_data[$offset])) {
        //             $data_array[] = $merge_all_data[$offset];
        //         }
        //         $offset++;
        //     }

        //     $pagination['total_pages'] = ceil(count($merge_all_data) / $limit);
        //     $pagination['current_page'] = (int)$page;
        //     $pagination['total_records'] = count($merge_all_data);
        // } else {
        //     $data_array = $merge_all_data;
        // }

        // return prepareResult(true, $data_array, [], "Planogram customer listing", $this->success, $pagination);
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("Name", "Start date", "End date", "Customer code", "Status", "Distribution name", "Image", "Image2", "Image3", "Image4");

        return prepareResult(true, $mappingarray, [], "Planogram Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'planogram_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate region import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('planogram_file')->store('import');
            $filename = storage_path("app/" . $file);
            $fp = fopen($filename, "r");
            $content = fread($fp, filesize($filename));
            $lines = explode("\n", $content);
            $heading_array_line = isset($lines[0]) ? $lines[0] : '';
            $heading_array = explode(",", trim($heading_array_line));
            fclose($fp);

            if (!$heading_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            if (!$map_key_value_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            /*$file_data = fopen(storage_path("app/".$file), "r");
            $row_counter = 1;
            while(!feof($file_data)) {
               if($row_counter == 1){
                    echo fgets($file_data). "<br>";
               }
               $row_counter++;
            }
            fclose($file_data);
            */
            //exit;

            $import = new PlanogramImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

            //print_r($import);
            //exit;
            $succussrecords = 0;
            $successfileids = 0;
            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());
                $data = json_encode($import->successAllRecords());
                $fileName = time() . '_datafile.txt';
                File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();
                $successfileids = $importtempfiles->id;
            }
            $errorrecords = 0;
            $errror_array = array();
            if ($import->failures()) {

                foreach ($import->failures() as $failure_key => $failure) {
                    //echo $failure_key.'--------'.$failure->row().'||';
                    //print_r($failure);
                    if ($failure->row() != 1) {
                        $failure->row(); // row that went wrong
                        $failure->attribute(); // either heading key (if using heading row concern) or column index
                        $failure->errors(); // Actual error messages from Laravel validator
                        $failure->values(); // The values of the row that has failed.
                        //print_r($failure->errors());

                        $error_msg = isset($failure->errors()[0]) ? $failure->errors()[0] : '';
                        if ($error_msg != "") {
                            //$errror_array['errormessage'][] = array("There was an error on row ".$failure->row().". ".$error_msg);
                            //$errror_array['errorresult'][] = $failure->values();
                            $error_result = array();
                            $error_row_loop = 0;
                            foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
                                $error_result[$map_key_value_array_value] = isset($failure->values()[$error_row_loop]) ? $failure->values()[$error_row_loop] : '';
                                $error_row_loop++;
                            }
                            $errror_array[] = array(
                                'errormessage' => "There was an error on row " . $failure->row() . ". " . $error_msg,
                                'errorresult' => $error_result, //$failure->values(),
                                //'attribute' => $failure->attribute(),//$failure->values(),
                                //'error_result' => $error_result,
                                //'map_key_value_array' => $map_key_value_array,
                            );
                        }
                    }
                }
                $errorrecords = count($errror_array);
            }
            //echo '<pre>';
            //print_r($import->failures());
            //echo '</pre>';
            $errors = $errror_array;
            $result['successrecordscount'] = $succussrecords;
            $result['errorrcount'] = $errorrecords;
            $result['successfileids'] = $successfileids;


            //}
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                if ($failure->row() != 1) {
                    info($failure->row());
                    info($failure->attribute());
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                    $errors[] = $failure->errors();
                }
            }

            return prepareResult(true, [], $errors, "Failed to validate bank import", $this->success);
        }
        return prepareResult(true, $result, $errors, "Customer successfully imported", $this->success);
    }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);
            if ($finaldata) :
                foreach ($finaldata as $row) :

                    $customer = CustomerInfo::where('customer_code', $row[3])->first();
                    $distribution = Distribution::where('name', $row[5])->first();
                    $planogram = Planogram::where('name', $row[0])->first();
                    $current_organisation_id = request()->user()->organisation_id;

                    if (is_object($planogram)) {
                        $planogram->name = $row[0];
                        $planogram->start_date  = Carbon::createFromFormat('d/m/Y', $row[1])->format('Y-m-d');
                        $planogram->end_date = Carbon::createFromFormat('d/m/Y', $row[2])->format('Y-m-d');
                        $planogram->status  = $row[4];
                        $planogram->save();

                        $planogram_customer = PlanogramCustomer::where('planogram_id', $planogram->id)->first();
                        $planogram_customer->planogram_id = $planogram->id;
                        $planogram_customer->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $planogram_customer->save();

                        $rowCount = 6;
                        for ($i = 0; $i < 4; $i++) {
                            if (isset($row[$rowCount])) {
                                $planogram_image = PlanogramImage::where('planogram_id', $planogram->id)->first();
                                $planogram_image->planogram_id = $planogram->id;
                                $planogram_image->planogram_distribution_id = (is_object($distribution)) ? $distribution->id : 0;
                                $planogram_image->image_string = $row[$rowCount];

                                $planogram_image->save();
                                $rowCount++;
                            }
                        }
                    } else {

                        if (!is_object($customer) or !is_object($distribution)) {
                            if (!is_object($customer)) {
                                return prepareResult(false, [], [], "customer not exist", $this->unauthorized);
                            }
                            if (!is_object($distribution)) {
                                return prepareResult(false, [], [], "distribution not exists", $this->unauthorized);
                            }
                        } else {
                            $planogram = new Planogram;
                            $planogram->organisation_id = $current_organisation_id;
                            $planogram->name = $row[0];
                            $planogram->start_date  = Carbon::createFromFormat('d/m/Y', $row[1])->format('Y-m-d');
                            $planogram->end_date = Carbon::createFromFormat('d/m/Y', $row[2])->format('Y-m-d');
                            $planogram->status  = $row[4];
                            $planogram->save();

                            $planogram_customer = new PlanogramCustomer;
                            $planogram_customer->planogram_id = $planogram->id;
                            $planogram_customer->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                            $planogram_customer->save();

                            $rowCount = 6;
                            for ($i = 0; $i < 4; $i++) {
                                if (isset($row[$rowCount])) {
                                    $planogram_image = new PlanogramImage;
                                    $planogram_image->planogram_id = $planogram->id;
                                    $planogram_image->planogram_distribution_id = (is_object($distribution)) ? $distribution->id : 0;
                                    $planogram_image->image_string = $row[$rowCount];

                                    $planogram_image->save();
                                    $rowCount++;
                                }
                            }
                        }
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "Planogram successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }
}
