<?php

namespace App\Exports;

use App\Model\DailyActivity;
use App\Model\SupervisorCategory;
use App\Model\Todo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class DailyActivityExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $StartDate, $EndDate;

    public function __construct(String  $StartDate, String $EndDate)
    {
        $this->StartDate = $StartDate;
        $this->EndDate = $EndDate;
    }
    public function collection()
    {
        $start_date = date('Y-m-d', strtotime('-1 days', strtotime($this->StartDate)));
        $end_date = $this->EndDate;

        $daily_activity = DailyActivity::select('id', 'customer_id', 'supervisor_id', 'lob_id', 'date', 'status')
            ->with(
                'customer:id,firstname,lastname',
                'supervisor:id,firstname,lastname',
                'lob:id,name',
                'dailyActivityDetails'
            )
            ->whereDate('date', [$start_date, $end_date])
            ->get();



        if (count($daily_activity)) {
            foreach ($daily_activity as $key => $daily) {
                $customer_name = "N/A";
                $customer_code = "N/A";
                $supervisor_name = "N/A";
                $lob_name = "N/A";

                if (is_object($daily->customer)) {
                    $customer_name = $daily->customer->getName();
                    if (is_object($daily->customer->customerInfo)) {
                        $customer_code = $daily->customer->customerInfo->customer_code;
                    }
                }

                if (is_object($daily->supervisor)) {
                    $supervisor_name = $daily->supervisor->getName();
                }
                if (is_object($daily->lob)) {
                    $lob_name = $daily->lob->name;
                }

                $daily_activity[$key]->Date = $daily->date;
                $daily_activity[$key]->customer_name = $customer_name;
                $daily_activity[$key]->customer_code = $customer_code;
                $daily_activity[$key]->supervisor_name = $supervisor_name;
                $daily_activity[$key]->lob_name = $lob_name;
                $daily_activity[$key]->Status = $daily->status;

                unset($daily_activity[$key]->id);
                unset($daily_activity[$key]->date);
                unset($daily_activity[$key]->customer_id);
                unset($daily_activity[$key]->supervisor_id);
                unset($daily_activity[$key]->lob_id);
                unset($daily_activity[$key]->staus);
            }
        }

        return $daily_activity;
    }

    public function headings(): array
    {
        $sc = SupervisorCategory::orderBy('name', 'asc')->get();

        $array1 = array(
            'Date',
            'Customer Name',
            'Customer Code',
            'Supervisor Name',
            'Lob Name',
            'Status',
            'Comment'
        );

        $array2 = array();
        foreach ($sc as $s) {
            $array2[] = $s->name;
        }

        $merge_array = array_merge($array1, $array2);

        return $merge_array;
    }
}
