<?php


namespace app\controller;

/**接口测试*/

use think\App;

class ApiTest extends ApiBase
{

    //filelock文件锁
    public function filelock($type)
    {
        $fp = fopen("logs/app.log", "a+");
        if ($type) {
            if (flock($fp, LOCK_EX)) {  // 进行排它型锁定
                fwrite($fp, "Write something here\n");
                fgets($fp);
                fflush($fp);            // flush output before releasing the lock
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo "文件正在被其他程序占用";
            }
        } else {
            //加锁 LOCK_NB
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                sleep(10);
                //事例二 fopen->pcntl_fork()获取同一个打开文件句柄反之p->f则不同
                $pid = pcntl_fork();
                if ($pid == 0) {
                    fwrite($fp, "子进程\n");
                } else {
                    fwrite($fp, "父进程\n");
                }
                echo "非阻塞获取成功";
                flock($fp, LOCK_UN);// 解锁
            } else {
                echo "非阻塞获取失败";
            }
        }
        fclose($fp);

    }


}