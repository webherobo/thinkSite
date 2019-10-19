<?php


namespace app\controller;

/**接口测试*/

use think\facade\Cache;
use think\App;

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
    public function redislock()
    {
        $fp = fopen(app()->getRootPath() . "runtime/redislock.log", "a+");
        $servers = [
            ['127.0.0.1', 6379, 0.01],
            //   ['127.0.0.1', 6389, 0.01],
            //  ['127.0.0.1', 6399, 0.01],
        ];
       // $redLock = new app\service\LockService($servers);
        $redLock =new LockService();
        while (true) {
            $lock = $redLock->lock('test', 10000);
            if ($lock) {
                fwrite($fp, $lock . "->lock进程\n");
                print_r($lock);
            } else {
                fwrite($fp, $lock . "Lock not acquired->lock进程\n");
            }
        }
        fclose($fp);
    }

}