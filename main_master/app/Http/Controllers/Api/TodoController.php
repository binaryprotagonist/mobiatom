<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
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

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $todo_query = Todo::select('id', 'customer_id', 'supervisor_id', 'task_name', 'date', 'status', 'comment')
            ->with(
                'customer:id,firstname,lastname',
                'supervisor:id,firstname,lastname'
            );

        if ($start_date != '' && $end_date != '') {
            $todo_query->whereBetween('date', [$start_date, $end_date]);
        }

        $todo = $todo_query->get();

        $todo_array = array();
        if (is_object($todo)) {
            foreach ($todo as $key => $todo1) {
                $todo_array[] = $todo[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($todo_array[$offset])) {
                    $data_array[] = $todo_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($todo_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($todo_array);
        } else {
            $data_array = $todo_array;
        }

        return prepareResult(true, $data_array, [], "Todo listing", $this->success, $pagination);
    }
}
