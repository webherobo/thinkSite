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

        $client = new swoole_client(SWOOLE_SOCK_TCP);
        //连接到服务器
        if (!$client->connect('127.0.0.1', 8081, 0.5)) {
            die("connect failed.");
        }
        //向服务器发送数据
        if (!$client->send("hello world")) {
            echo ‘发送失败‘;
        }
        //从服务器接收数据
        $data = $client->recv();
        if (!$data) {
            die("recv failed.");
        }
        echo $data;
        //关闭连接
        $client->close();
    }
}