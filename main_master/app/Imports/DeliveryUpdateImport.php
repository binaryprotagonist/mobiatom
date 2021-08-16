<?php

namespace App\Imports;

use App\Model\Delivery;
use App\Model\SalesmanInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;

class DeliveryUpdateImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if (isset($row[0]) && $row[0] != 'Delivery Code') {
            $delivery_exist = Delivery::where('delivery_number', 'like', "%" . $row[0] . "%")
                ->first();

            if (is_object($delivery_exist)) {
                $salemsnaInfo = SalesmanInfo::where('salesman_code', 'like', "%" . $row[1] . "%")
                    ->first();

                if (is_object($salemsnaInfo)) {
                    $delivery_exist->salesman_id    = $salemsnaInfo->user_id;
                    $delivery_exist->delivery_date  = Carbon::parse($row[2])->format('Y-m-d');
                    $delivery_exist->delivery_time  = $row[3];
                    $delivery_exist->save();
                }
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
