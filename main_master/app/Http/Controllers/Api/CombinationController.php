<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Country;
use App\Model\Depot;
use Illuminate\Http\Request;
use App\Model\Item;
use App\Model\Region;

class CombinationController extends Controller
{
    private function getListByParam($obj, $param)
    {
        // $object = json_decode($obj, true);
        $object = $obj;

        $array = [];
        $get = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($object), \RecursiveIteratorIterator::SELF_FIRST);

        foreach($get as $key => $value) {
            if($key === $param)
            {
                if (is_array($value) && sizeof($value) > 0) {
                    
                    if (is_object($value)) {
                        $value = (array) $value;
                    }
                    $array = array_merge($array, $value);
                }
            }
        }

        return $array;
        // return prepareResult(true, $array, [], "Regions listing", $this->success);
    }
}
