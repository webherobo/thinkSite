<?php


namespace app\controller;

use app\BaseController;
use Swoole\Server;
use think\Db;
use Swoole\Coroutine;
use think\swoole\concerns\InteractsWithHttp;

//use think\swoole\concerns\InteractsWithPool;
//use think\swoole\concerns\InteractsWithPoolConnector;
use think\swoole\concerns\InteractsWithRpc;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\InteractsWithWebsocket;
use swoole_client;

class SwooleBase extends BaseController
{
    //客户端swoole
    public function swoole()
    {
        $this->app->swooleService->clinteSwoole();
        echo "处理成功";
    }
}