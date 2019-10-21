<?php


namespace app\controller;

use app\BaseController;

class ApiBase extends BaseController
{

    public static function return($statusCode, $data = [])
    {
        return json($data,$statusCode);
    }
}