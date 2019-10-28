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
        $data=['token'=>"webherobo1".time(),'platform'=>"app",'data'=>'hello world'];
        $senddata=json_encode(['code'=>0,'message'=>"ok",'data'=>$data]);
        //向服务器发送数据
        if (!$client->send($senddata)) {
            echo ‘发送失败‘;
        }
        $num=10;
        $interval=3;//每隔一定时间运行
        do{
            $msg=date("Y-m-d H:i:s");
            $senddata=json_encode(['code'=>0,'message'=>"你好：现在时间戳是".time()."时间是：".$msg."\n",'data'=>$data]);
            $client->send($senddata);
            sleep($interval);//等待时间，进行下一次操作。
            $num--;
        }while($num>0);
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