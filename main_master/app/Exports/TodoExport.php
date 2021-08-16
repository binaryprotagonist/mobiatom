<?php

namespace App\Exports;

use App\Model\Todo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class TodoExport implements FromCollection, WithHeadings
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

        $todo_query = Todo::select('id', 'customer_id', 'supervisor_id', 'task_name', 'date', 'status', 'comment')
            ->with(
                'customer:id,firstname,lastname',
                'supervisor:id,firstname,lastname'
            );

        if ($start_date != '' && $end_date != '') {
            $todo_query->whereBetween('date', [$start_date, $end_date]);
        }
        $todo = $todo_query->get();

        if (count($todo)) {
            foreach ($todo as $key => $t) {
                $customer_name = "N/A";
                $customer_code = "N/A";
                $supervisor_name = "N/A";

                if (is_object($t->customer)) {
                    $customer_name = $t->customer->getName();
                    if (is_object($t->customer->customerInfo)) {
                        $customer_code = $t->customer->customerInfo->customer_code;
                    }
                }

                if (is_object($t->supervisor)) {
                    $supervisor_name = $t->supervisor->getName();
                }

                $todo[$key]->Date = $t->date;
                $todo[$key]->customer_name = $customer_name;
                $todo[$key]->customer_code = $customer_code;
                $todo[$key]->supervisor_name = $supervisor_name;
                $todo[$key]->taskName = $t->task_name;
                $todo[$key]->Status = $t->status;
                $todo[$key]->Comment = $t->comment;

                unset($todo[$key]->id);
                unset($todo[$key]->date);
                unset($todo[$key]->customer_id);
                unset($todo[$key]->supervisor_id);
                unset($todo[$key]->task_name);
                unset($todo[$key]->status);
                unset($todo[$key]->comment);
            }
        }

        return $todo;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Customer Name',
            'Customer Code',
            'Supervisor Name',
            'Task Name',
            'Status',
            'Comment'
        ];
    }
}
