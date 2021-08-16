<?php

use App\Model\ActionHistory;
use App\Model\CodeSetting;
use App\Model\Currency;
use App\Model\CustomerInfo;
use App\Model\CustomFieldValueSave;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\Notifications;
use App\Model\SalesmanInfo;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleModule;
use App\Model\WorkFlowRule;
use App\Model\WorkFlowRuleApprovalRole;
use App\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function Clue\StreamFilter\fun;

function pre($array, $exit = true)
{
    echo '<pre>';
    print_r($array);
    echo '</pre>';

    if ($exit) {
        exit();
    }
}

function prepareResult($status, $data, $errors, $msg, $status_code, $pagination = array())
{
    return response()->json(['status' => $status, 'data' => $data, 'message' => $msg, 'errors' => $errors, 'pagination' => $pagination], $status_code);
}

function getUser()
{
    return auth('api')->user();
}

function checkPermission($permissionName)
{
    if (!auth('api')->user()->can($permissionName) && auth('api')->user()->hasRole('superadmin')) {
        return false;
    }
    return true;
}

function combination_key($key)
{
    if (!count($key)) {
        return;
    }


    $combine = array();

    foreach ($key as $k) {
        if ($k == 1) {
            $combine[] = 'Country';
        }
        if ($k == 2) {
            $combine[] = 'Region';
        }
        if ($k == 3) {
            $combine[] = 'Area';
        }
        if ($k == 4) {
            $combine[] = 'Sub Area';
        }
        if ($k == 5) {
            $combine[] = 'Branch/Depot';
        }
        if ($k == 6) {
            $combine[] = 'Route';
        }
        if ($k == 7) {
            $combine[] = 'Sales Organisations';
        }
        if ($k == 8) {
            $combine[] = 'Channels';
        }
        if ($k == 9) {
            $combine[] = 'Sub Channels';
        }
        if ($k == 10) {
            $combine[] = 'Customer Groups';
        }
        if ($k == 11) {
            $combine[] = 'Customer';
        }
        if ($k == 12) {
            $combine[] = 'Material';
        }

        return $combine;
    }
}

function getDay($number)
{
    if ($number == 1) {
        return "Monday";
    }
    if ($number == 2) {
        return "Tuesday";
    }
    if ($number == 3) {
        return "Wednesday";
    }
    if ($number == 4) {
        return "Thursday";
    }
    if ($number == 5) {
        return "Friday";
    }
    if ($number == 6) {
        return "Saturday";
    }
    if ($number == 7) {
        return "Sunday";
    }
}

function nextComingNumber2($model, $variableName, $feildName, $code)
{
    if (CodeSetting::where('is_code_auto_' . $variableName, true)->count() > 0) {
        $getNumber = CodeSetting::select('prefix_code_' . $variableName, 'start_code_' . $variableName, 'next_coming_number_' . $variableName)->where('is_code_auto_' . $variableName, true)->first();
        if ($getNumber) {
            return $getNumber['next_coming_number_' . $variableName];
        }
    }

    //Not found case : manual code entry
    return $code;
}

function updateNextComingNumber($model, $variableName)
{
    if (CodeSetting::where('is_code_auto_' . $variableName, true)->count() > 0) {
        $getNumber = CodeSetting::select('prefix_code_' . $variableName, 'start_code_' . $variableName, 'next_coming_number_' . $variableName)->where('is_code_auto_' . $variableName, true)->first();
        preg_match_all('!\d+!', $getNumber['next_coming_number_' . $variableName], $newNumber);
        if (substr_count($getNumber['next_coming_number_' . $variableName], 0) >= 1) {
            if (substr($newNumber[0][0], 0, 1) != 0) {
                $nextNumber =  $getNumber['prefix_code_' . $variableName] . ($newNumber[0][0] + 1);
            } else {
                $charCount = strlen($getNumber['start_code_' . $variableName]);
                if ($charCount > 3) {
                    $tChar = $charCount - substr_count($getNumber['start_code_' . $variableName], 0);
                } else {
                    $tChar = 1;
                }
                $count0 = substr_count($getNumber['start_code_' . $variableName], 0) + $tChar;
                $value2 = substr($getNumber['next_coming_number_' . $variableName], $charCount, $count0);
                $value2 = $value2 + 1;
                $nextNumber =  $getNumber['prefix_code_' . $variableName] . sprintf('%0' . $count0 . 's', $value2);
            }
        } else {
            if ($getNumber['prefix_code_' . $variableName]) {
                $nextNumber =  $getNumber['prefix_code_' . $variableName] . ($newNumber[0][0] + 1);
            } else {
                $nextNumber =  $getNumber['prefix_code_' . $variableName] . (0 + 1);
            }
        }

        $updateNextNumber = CodeSetting::select('prefix_code_' . $variableName, 'start_code_' . $variableName, 'next_coming_number_' . $variableName)->where('is_code_auto_' . $variableName, true)->update([
            'next_coming_number_' . $variableName => $nextNumber
        ]);

        return $updateNextNumber;
    }
}

function nextComingNumber($model, $variableName, $feildName, $code)
{
    if (CodeSetting::where('is_code_auto_' . $variableName, true)->count() > 0) {
        $getNumber = CodeSetting::select('prefix_code_' . $variableName, 'start_code_' . $variableName, 'next_coming_number_' . $variableName)->where('is_code_auto_' . $variableName, true)->first();

        if ($getNumber && $getNumber['prefix_code_' . $variableName]) {
            return $getNumber['next_coming_number_' . $variableName];
        } else {
            return $code;
        }
    }

    //Not found case : manual code entry
    return $code;
}

function updateNextComingNumber2($model, $variableName)
{
    if (CodeSetting::where('is_code_auto_' . $variableName, true)->count() > 0) {
        $getNumber = CodeSetting::select('prefix_code_' . $variableName, 'start_code_' . $variableName, 'next_coming_number_' . $variableName)->where('is_code_auto_' . $variableName, true)->first();
        preg_match_all('!\d+!', $getNumber['next_coming_number_' . $variableName], $newNumber);
        $nextNumber =  $getNumber['prefix_code_' . $variableName] . ($newNumber[0][0] + 1);

        $updateNextNumber = CodeSetting::select('prefix_code_' . $variableName, 'start_code_' . $variableName, 'next_coming_number_' . $variableName)->where('is_code_auto_' . $variableName, true)->update([
            'next_coming_number_' . $variableName => $nextNumber
        ]);
        return $updateNextNumber;
    }
}

function checkWorkFlowRule($moduleName, $eventName)
{
    $getModuleId = WorkFlowRuleModule::select('id')
        ->where('name', $moduleName)
        ->first();

    if ($getModuleId) {
        $checkActivate = WorkFlowRule::select('id')
            ->where('work_flow_rule_module_id', $getModuleId->id)
            ->where('event_trigger', 'like', "%" . $eventName . "%")
            ->where('status', 1)
            ->first();

        if ($checkActivate) {
            return $checkActivate->id;
        }
    }
    return false;
}

function codeExist($object, $code_key, $code)
{
    $obj = new $object;
    pre($obj);
    $data = $obj->where($code_key, $code)->first();
    if (is_object($data)) {
        return true;
    }
    return false;
}

function savecustomField($record_id, $module_id, $custom_field_id, $custom_field_value)
{
    $custom_field_value_save = new CustomFieldValueSave;
    $custom_field_value_save->record_id = $record_id;
    $custom_field_value_save->module_id = $module_id;
    $custom_field_value_save->custom_field_id = $custom_field_id;
    $custom_field_value_save->custom_field_value = $custom_field_value;
    $custom_field_value_save->save();
}

function getItemDetails($itemid, $uom, $qty)
{
    $itemDeails = Item::select('lower_unit_uom_id', 'lower_unit_item_upc')->where('id', $itemid)->first();

    if ($itemDeails['lower_unit_uom_id'] != $uom) {
        $qtys = $itemDeails['lower_unit_item_upc'] * $qty;

        $result = array('ItemId' => $itemid, 'UOM' => $itemDeails['lower_unit_uom_id'], 'Qty' => $qtys);
    } else {
        $result = array('ItemId' => $itemid, 'UOM' => $itemDeails['lower_unit_uom_id'], 'Qty' => $qty);
    }
    return $result;
}

function getItemDetails2($itemid, $uom, $qty)
{
    $itemDeails = Item::select('lower_unit_uom_id', 'lower_unit_item_upc')
        ->where('id', $itemid)
        ->first();

    if ($itemDeails['lower_unit_uom_id'] != $uom) {
        $item_main_price = ItemMainPrice::where('item_id', $itemid)
            ->where('item_uom_id', $uom)
            ->first();

        $qtys = $item_main_price->item_upc * $qty;

        $result = array('ItemId' => $itemid, 'UOM' => $itemDeails['lower_unit_uom_id'], 'Qty' => $qtys);
    } else {
        $result = array('ItemId' => $itemid, 'UOM' => $itemDeails['lower_unit_uom_id'], 'Qty' => $qty);
    }

    return $result;
}


function GetWorkFlowRuleObject($moduleName)
{
    $workFlowRules = WorkFlowObject::select(
        'work_flow_objects.id as id',
        'work_flow_objects.uuid as uuid',
        'work_flow_objects.work_flow_rule_id',
        'work_flow_objects.module_name',
        'work_flow_objects.request_object',
        'work_flow_objects.currently_approved_stage',
        'work_flow_objects.raw_id',
        'work_flow_rules.work_flow_rule_name',
        'work_flow_rules.description',
        'work_flow_rules.event_trigger'
    )
        ->withoutGlobalScope('organisation_id')
        ->join('work_flow_rules', function ($join) {
            $join->on('work_flow_objects.work_flow_rule_id', '=', 'work_flow_rules.id');
        })
        ->where('work_flow_objects.organisation_id', auth()->user()->organisation_id)
        ->where('status', '1')
        ->where('is_approved_all', '0')
        ->where('is_anyone_reject', '0')
        ->where('work_flow_objects.module_name', $moduleName)
        //->where('work_flow_objects.raw_id',$users[$key]->id)
        ->get();
    $results = [];
    foreach ($workFlowRules as $key => $obj) {
        $checkCondition = WorkFlowRuleApprovalRole::query();
        if ($obj->currently_approved_stage > 0) {
            $checkCondition->skip($obj->currently_approved_stage);
        }
        $getResult = $checkCondition->where('work_flow_rule_id', $obj->work_flow_rule_id)
            ->orderBy('id', 'ASC')
            ->first();
        $userIds = [];
        if (is_object($getResult) && $getResult->workFlowRuleApprovalUsers->count() > 0) {
            //User based approval
            foreach ($getResult->workFlowRuleApprovalUsers as $prepareUserId) {
                $WorkFlowObjectAction = WorkFlowObjectAction::where('work_flow_object_id', $obj->id)->get();
                if (is_object($WorkFlowObjectAction)) {
                    $id_arr = [];
                    foreach ($WorkFlowObjectAction as $action) {
                        $id_arr[] = $action->user_id;
                    }
                    if (!in_array($prepareUserId->user_id, $id_arr)) {
                        $userIds[] = $prepareUserId->user_id;
                    }
                    if (request()->user()->usertype == 1) {
                        $userIds[] = request()->user()->id;
                    }
                } else {
                    $userIds[] = $prepareUserId->user_id;
                }
            }

            if (in_array(auth()->id(), $userIds)) {
                $results[] = [
                    'object'    => $obj,
                    'Action'    => 'User'
                ];
            }
        } else {
            //Roles based approval
            if (is_object($getResult) && $getResult->organisation_role_id == auth()->user()->role_id)
                $results[] = [
                    'object'    => $obj,
                    'Action'    => 'Role'
                ];
        }
    }
    return $results;
}

function create_action_history($module, $module_id, $user_id, $action, $comment)
{
    $action_history = new ActionHistory;
    $action_history->module = $module;
    $action_history->module_id = $module_id;
    $action_history->user_id = $user_id;
    $action_history->action = $action;
    $action_history->comment = $comment;
    $action_history->save();
}

/**
 * output value if found in object or array
 * @param  [object/array] $model             Eloquent model, object or array
 * @param  [string] $key
 * @param  [boolean] $alternative_value
 * @return [type]
 */
function model($model, $key, $alternative_value = null, $type = 'object', $pluck = false)
{
    if ($pluck) {
        $count = $model;
        $array = array();
        if ($count && count($count)) {
            $array = $count->pluck($key)->toArray();
        }

        if (count($array)) {
            return implode(',', $array);
        }

        return $alternative_value;
    }

    if ($type == 'object') {
        if (isset($model->$key)) {
            return $model->$key;
        }
    }

    if ($type == 'array') {
        if (isset($model[$key]) && $model[$key]) {
            return $model[$key];
        }
    }

    return $alternative_value;
}

function convertToCurrency($number)
{
    $no = round($number);
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural;
        } else {
            $str[] = null;
        }
    }

    $Rupees = implode(' ', array_reverse($str));
    $paise = ($decimal) ? "And Paise " . ($words[$decimal - $decimal % 10]) . " " . ($words[$decimal % 10])  : '';
    return ($Rupees ? 'Rupees ' . $Rupees : '') . $paise . " Only";
}


function customPaginate($page, $limit, $component_array)
{
    $data_array = array();
    $offset = ($page - 1) * $limit;
    for ($i = 0; $i < $limit; $i++) {
        if (isset($component_array[$offset])) {
            $data_array[] = $component_array[$offset];
        }
        $offset++;
    }

    // $data_array = $data_array;
    $pagination['total_pages'] = ceil(count($component_array) / $limit);
    $pagination['current_page'] = (int)$page;
    $pagination['total_records'] = count($component_array);

    return array("data" => $data_array, "pagination" => $pagination);
}

function chnageCurrencyFormat($amount)
{
    $currency = Currency::where('default_currency', 1)->first();

    if ($currency->decimal_digits = 2) {
        if ($currency->format == "1,234,567.89") {
            $amount = number_format($amount, $currency->decimal_digits, ',', '.');
        } else if ($currency->format == "1.234.567,89") {
            $amount = number_format($amount, $currency->decimal_digits, '.', ',');
        } else {
            $amount = number_format($amount, $currency->decimal_digits, ' ', ',');
        }
    } else if ($currency->decimal_digits = 3) {
        if ($currency->format == "1,234,567.899") {
            $amount = number_format($amount, $currency->decimal_digits, ',', '.');
        } else if ($currency->format == "1.234.567,899") {
            $amount = number_format($amount, $currency->decimal_digits, '.', ',');
        } else {
            $amount = number_format($amount, $currency->decimal_digits, ' ', ',');
        }
    } else {
        if ($currency->format == "1,234,567") {
            $amount = number_format($amount, $currency->decimal_digits, ',', '.');
        } else if ($currency->format == "1.234.567") {
            $amount = number_format($amount, $currency->decimal_digits, '.', ',');
        } else {
            $amount = number_format($amount, $currency->decimal_digits, ' ', ',');
        }
    }
    return $amount;
}

function saveImage($image_name, $image, $folder_name)
{
    if (!empty($image)) {
        $destinationPath    = 'uploads/' . $folder_name . '/';
        $image_name = $image_name;
        $image = $image;
        $getBaseType = explode(',', $image);
        $getExt = explode(';', $image);
        $image = str_replace($getBaseType[0] . ',', '', $image);
        $image = str_replace(' ', '+', $image);
        $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
        \File::put($destinationPath . $fileName, base64_decode($image));
        return URL('/') . '/' . $destinationPath . $fileName;
    } else {
        return NULL;
    }
}

/**
 * Calculates the great-circle distance between two points, with
 * the Haversine formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function haversineGreatCircleDistance(
    $latitudeFrom,
    $longitudeFrom,
    $latitudeTo,
    $longitudeTo,
    $earthRadius = 6371000
) {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    } else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            $miless = ($miles * 1.609344);
            return ($miless / 0.00062137);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            pre($miles, false);
            return $miles;
        }
    }
}

function timeCalculate($start_time, $end_time, $type = null)
{
    // $start_datetime = new DateTime($start_time);
    // $end_datetime = new DateTime($end_time);

    $start_datetime = Carbon::parse($start_time);
    $end_datetime = Carbon::parse($end_time);

    $time = $start_datetime->diffInSeconds($end_datetime);
    return gmdate('H:i:s', $time);
}


function getHours($sec)
{
    $seconds = $sec;
    $hours = floor($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;

    return "$hours:$minutes:$seconds";

    // return floor($minutes / 60).':'.($minutes -   floor($minutes / 60) * 60);
}

/*
* Get Percentage amount 
* Default percentage 5%
*/
function getPercentAmount($amount, $per = null)
{
    $final_amount = 0;
    $percentage = 5;
    if ($per) {
        $percentage = $per;
    }

    $final_amount = ($amount * $percentage) / 100;

    return $final_amount;
}

function getCustomerCode($user_id)
{
    $customer = CustomerInfo::select('id', 'customer_code', 'uer_id')
        ->where('user_id', $user_id)
        ->first();

    if (is_object($customer)) {
        return $customer->customer_code;
    }
    return null;
}


/**
 * Send a notification to mobile
 * @param  [string] $notification_id  access token
 * @param  [string] $title
 * @param  [string] $message
 * @param  [int] $id
 * @param  [string] $type default basic
 * @return [boolan]
 */
function send_notification_FCM($notification_id, $title, $message, $id, $type = "basic")
{

    $accesstoken = "AAAA3Jdk2Xs:APA91bGA8xUnM9-4Eo1z20FHDN-I_lfQr7kzn35nq1nsSJvq2Rhc7i0Lpt0_uNoHFZzjEtmb_io-QD_3E70nod0waqq1EQnlTn36J8ZbWAvtfL_nNn_9m0mJAxACDnRIPrQ0oVT4HYPj";

    $URL = 'https://fcm.googleapis.com/fcm/send';


    $post_data = '{
            "to" : "' . $notification_id . '",
            "data" : {
              "body" : "",
              "title" : "' . $title . '",
              "type" : "' . $type . '",
              "id" : "' . $id . '",
              "message" : "' . $message . '",
            },

            "notification" : {
                 "body" : "' . $message . '",
                 "title" : "' . $title . '",
                 "type" : "' . $type . '",
                 "id" : "' . $id . '",
                 "message" : "' . $message . '",
                 "icon" : "new",
                 "sound" : "default"
                },
          }';

    $crl = curl_init();

    $header = array();
    $header[] = 'Content-type: application/json';
    $header[] = 'Authorization: ' . $accesstoken;
    curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($crl, CURLOPT_URL, $URL);
    curl_setopt($crl, CURLOPT_HTTPHEADER, $header);

    curl_setopt($crl, CURLOPT_POST, true);
    curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);

    $rest = curl_exec($crl);
    pre($rest);
    if ($rest === false) {
        // throw new Exception('Curl error: ' . curl_error($crl));
        //pre('Curl error: ' . curl_error($crl));
        $result_noti = 0;
    } else {

        $result_noti = 1;
    }

    curl_close($crl);
    //pre($result_noti);
    return $result_noti;
}


function sendNotificationAndroid($data, $reg_id)
{
    $fcmMsg = array(
        'body' => $data['message'],
        'title' => $data['title'],
        'noti_type' => $data['noti_type'],
        'message' => $data['message'],
        'uuid' => (!empty($data['uuid'])) ? $data['uuid'] : null,
        'sound' => "default",
        'color' => "#203E78",
    );

    $fcmFields = array(
        'to' => $reg_id,
        'priority' => 'high',
        'notification' => $fcmMsg,
        'data' => $fcmMsg
    );

    $headers = array(
        'Authorization: key=AAAA3Jdk2Xs:APA91bGA8xUnM9-4Eo1z20FHDN-I_lfQr7kzn35nq1nsSJvq2Rhc7i0Lpt0_uNoHFZzjEtmb_io-QD_3E70nod0waqq1EQnlTn36J8ZbWAvtfL_nNn_9m0mJAxACDnRIPrQ0oVT4HYPj',
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result . "\n\n";
}

/**
 * data is array
 * @param  [string] user_id  logged in user id
 * @param  [string] $url
 * @param  [string] $status
 *
 */
function saveNotificaiton($data)
{
    $nofitication = new Notifications;
    $nofitication->uuid = $data['uuid'];
    $nofitication->user_id = $data['user_id'];
    $nofitication->type = $data['type'];
    $nofitication->message = $data['message'];
    $nofitication->status = $data['status'];
    $nofitication->save();

    return true;
}

function get_invoice_sum($customer_id, $start_date, $end_date = "")
{
    if ($start_date != '' && $end_date != '') {
        $inv_total = DB::table('invoices')
            ->where('customer_id', $customer_id)
            ->whereBetween('invoices.invoice_due_date', [$start_date, $end_date])
            ->sum('invoices.grand_total');
        return $inv_total;
    } else if ($start_date != '' && $end_date == '') {
        $inv_total = DB::table('invoices')
            ->where('customer_id', $customer_id)
            ->whereDate('invoices.invoice_due_date', '>', $start_date)
            ->sum('invoices.grand_total');
        return $inv_total;
    } else {
        return 0;
    }
}

/**
 * get the salesman ids
 * @param type is string
 * @param ids is supervisor, or other ids
 *  
 */
function getSalesmanIds($type, $ids)
{
    $ids = array();
    $salesman_info_query = SalesmanInfo::select('id', 'user_id', 'region_id', 'salesman_supervisor');
    if ($type == "supervisor") {
        $salesman_info_query->where('salesman_supervisor', $ids);
    }

    if ($type == "region") {
        $salesman_info_query->where('region_id', $ids);
    }

    $salesman_infos = $salesman_info_query->get();

    if (count($salesman_infos)) {
        $ids = $salesman_infos->pluck('user_id')->toArray();
    }

    return $ids;
}

function getUserName($id)
{
    $user = User::find($id);
    return $user->getName();
}


function getRouteBySalesman($salesman_id)
{
    if (empty($salesman_id)) {
        return null;
    }

    $salesmanInfo = SalesmanInfo::where('user_id', $salesman_id)->first();
    if (is_object($salesmanInfo)) {
        return $salesmanInfo->route_id;
    }

    return null;
}

function ps($data)
{
    return preg_replace('/([0-9]{2,}[\- ]?[0-9]{5,})/', '', $data);
}

function setSalesmanNumberRange($code)
{
    $lenth = Str::length($code);

    $zero = '';
    if ($lenth < 7) {
        $sub_lenth = 6 - $lenth;
        for ($i = 1; $i <= $sub_lenth; $i++) {
            $zero .= "0";
        }
    }
   
    return $zero . $code;
}

function sr($data) {
    $data = str_replace('[', ' ', $data);
    $data = str_replace(']', '', $data);
    return $data;
}