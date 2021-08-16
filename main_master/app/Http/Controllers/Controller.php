<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $isAuthorized;

    protected $user;

    function __construct()
    {
        $this->success                  = 200;
        $this->created                  = 201;
        $this->accepted                 = 202;
        $this->not_modified             = 304;
        $this->bed_request              = 400;
        $this->unauthorized             = 401;
        $this->payment_required         = 402;
        $this->forbidden                = 403;
        $this->not_found                = 404;
        $this->method_not_allowed       = 405;
        $this->unprocessableEntity      = 422;
        $this->internal_server_error    = 500;
        $this->paginate                 = 15;

        $this->user = getUser();
        if (isset($this->user->status) && $this->user->status == 1) {
            $this->isAuthorized = true;
        } else {
            $this->isAuthorized = false;
        }
    }
}
