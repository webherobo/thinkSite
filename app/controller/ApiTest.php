<?php


namespace app\controller;

/**接口测试*/

use think\App;

class ApiTest extends ApiBase
{

    //filelock文件锁
    public function filelock()
    {
        $fp = fopen("logs/app.log", "a+");

        if (flock($fp, LOCK_EX)) {  // 进行排它型锁定
            fwrite($fp, "Write something here\n");
            fgets($fp);
            fflush($fp);            // flush output before releasing the lock
            flock($fp, LOCK_UN);    // 释放锁定
        } else {
            echo "文件正在被其他程序占用";
        }

        //加锁 LOCK_NB
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            sleep(10);
            echo "阻塞";
            flock($fp, LOCK_UN);// 解锁
        } else {
            echo "非阻塞";
        }

        fclose($fp);
        //事例二 fopen pcntl_fork()获取同一个打开文件句柄反之则不同

        $pid = pcntl_fork();

        $fp = fopen("demo.log", "a");

        if ($pid == 0) {
            fwrite($fp, "子进程\n");
        } else {
            fwrite($fp, "父进程\n");
        }

    }


}