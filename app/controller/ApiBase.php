<?php


namespace app\controller;

use app\BaseController;

class ApiBase extends BaseController
{
    public static function success($data)
    {
        return [
            'status_code' => 200,
            'data'        => $data,
        ];
    }

    public static function error($message = '')
    {
        return [
            'status_code' => 0,
            'message'     => $message,
        ];
    }

    public static function return($statusCode, $message, $data = [])
    {
        return [
            'status_code' => $statusCode,
            'message'     => $message,
            'data'        => $data,
        ];
    }
}