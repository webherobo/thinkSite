<?php


namespace app\controller;

/**接口测试*/

use think\facade\Cache;
USE app\model\User;

class ApiTest extends ApiBase
{

    //filelock文件锁
    public function filelock($type)
    {
        $pid = pcntl_fork();
        static $i = 0;
        $fp = fopen(app()->getRootPath() . "runtime/app.log", "a+");
        if ($type == 0) {
            Cache::clear();
            if (flock($fp, LOCK_EX)) {  // 进行排它型锁定
                fwrite($fp, $type . "#" . ++$i . "@" . $i++ . "CACHE:" . Cache::get("mytestnum") . "LOCK_EX Write something here\n");
                fgets($fp);
                fflush($fp);            // flush output before releasing the lock
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo "文件正在被其他程序占用";
            }
        } else {
            //加锁 LOCK_NB
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if (!Cache::get("mytestnum")) {
                    Cache::set('mytestnum', 1);
                } else {
                    Cache::inc('mytestnum');

                }
                // sleep(10);
                fwrite($fp, $type . "#" . ++$i . "@" . $i++ . "CACHE:" . Cache::get("mytestnum") . "LOCK_EX Write something here\n");
                //事例二 fopen->pcntl_fork()获取同一个打开文件句柄反之p->f则不同

                if ($pid == 0) {
                    fwrite($fp, $pid . "LOCK_EX | LOCK_NB子进程\n");
                } else {
                    fwrite($fp, $pid . "LOCK_EX | LOCK_NB父进程\n");
                }
                fwrite($fp, $pid . "LOCK_EX | LOCK_NB非阻塞获取成功\n");
                flock($fp, LOCK_UN);// 解锁
            } else {
                fwrite($fp, $pid . "LOCK_EX | LOCK_NB非阻塞获取失败\n");
            }
        }
        fclose($fp);

    }

    //REDIS锁
    public function redislock($type)
    {
        $fp = fopen(app()->getRootPath() . "runtime/redislock.log", "a+");
        $redLock =$this->app->lockService;
        static $i=10;
        while ($i>0) {
            $lock = $redLock->lock('test', 10000);
            if ($lock) {
                fwrite($fp, json_encode($lock) . $i."->$type lock进程\n");
            } else {
                fwrite($fp, json_encode($lock) . $i."Lock not acquired->$type lock进程\n");
            }
            $i--;
        }
        unset($i);
        fclose($fp);
        return "ok!";
    }
    //db锁
    public function dblock($type=false){
        $userModel=new User();
        $userdata=$userModel->where(["id"=>1])->lock($type)->find();
        $userdata->inc('score')->update();
        return $this->return(0,['message'=>"ok",'data'=>$userdata]);
    }

}