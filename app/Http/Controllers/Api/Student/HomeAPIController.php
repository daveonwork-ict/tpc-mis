<?php

namespace App\Http\Controllers\Api\Student;

use App\Traits\MobileMenuTrait;
use App\Traits\ReturnFormatTrait;
use App\Http\Controllers\Controller;

class HomeAPIController extends Controller
{
    use ReturnFormatTrait, MobileMenuTrait;

    public function menuList()
    {
        return $this->responseWithSuccess(___('alert.success'), $this->mobileMenus('student'));
    }
}
