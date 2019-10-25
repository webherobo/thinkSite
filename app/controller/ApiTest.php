<?php


namespace app\controller;

/**接口测试*/

use think\facade\Cache;
USE app\model\User;
use think\facade\Db;
use think\facade\Queue;

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
        $redLock = $this->app->lockService;
        static $i = 10;
        while ($i > 0) {
            $lock = $redLock->lock('test', 10000);
            if ($lock) {
                fwrite($fp, json_encode($lock) . $i . "->$type lock进程\n");
            } else {
                fwrite($fp, json_encode($lock) . $i . "Lock not acquired->$type lock进程\n");
            }
            $i--;
        }
        unset($i);
        fclose($fp);
        return "ok!";
    }

    //db锁
    public function dblock($type = false)
    {
        static $i = 1;
        Cache::set('mytestnum', ++$i);
        return $i;
        exit;


        $type = false;
        $userModel = new User();

        $fp = fopen(app()->getRootPath() . "runtime/dblockapp2.log", "a+");
        $lock = $this->app->lockService->lock('test', 10000);
        if ($lock) {
            Db::startTrans();
            $userdata = $userModel->where(["id" => 1])->lock($type)->find();
            //fwrite($fp, $userdata['score']. "LOCK_no Write something here\n");
            try {
                //     $userdata=Db::table("user")->where(["id"=>1])->inc('score')->update();
                //	sleep(1);
                //$data=Db::table("user")->where(["id"=>1])->lock(true)->select();
                //fwrite($fp, $data["0"]['score']. "newLOCK_no Write something here\n");

                if ($lock && $userdata['score'] > 0) {
                    $userdata->dec('score')->update();
                    fwrite($fp, $userdata['score'] . "newLOCK_ye Write something here\n");
                    //Db::query("update user set version=version+1 where id=1 ");
                    //sleep(1);
                    //$userdata=Db::table("user")->where(["id"=>1])->dec('score')->update();

                } else {
                    fwrite($fp, "newNoLOCK_ye Write something here\n");
                }

                Db::commit();
                sleep(3);
                $this->app->lockService->unlock($lock);
            } catch (\Exception $e) {
                Db::rollback();
                fwrite($fp, "回滚LOCK_ye Write something here\n");
            }
        }
        fwrite($fp, "outLOCK_no Write something here\n");
        fclose($fp);
        return $this->return(['code' => 0, 'message' => "ok", 'data' => []]);
    }

    //队列任务
    // 开启消息对列 php /home/wwwroot/default/gd/think queue:restart
    //监听消息的命令 php think queue:work --daemon --queue templatesend1
    public function queuejob()
    {
        $fp = fopen(app()->getRootPath() . "runtime/queuelog.log", "a+");
        $taskType = $_GET['taskType'] ?? 'taskA';
        switch ($taskType) {
            case 'taskA':
                $jobHandlerClassName = 'app\job\Hello@taskA';
                $jobDataArr = ['a' => '1'];
                $jobQueueName = "multiTaskJobQueue";
                break;
            case 'taskB':
                $jobHandlerClassName = 'app\job\Hello@taskB';
                $jobDataArr = ['b' => '2'];
                $jobQueueName = "multiTaskJobQueue";
                break;
            default:
                break;
        }
        // Queue::push( $jobHandlerObject ,null , $jobQueueName );
        //// 这时，需要在 $jobHandlerObject 中定义一个 handle() 方法，消息队列在执行到该任务时会自动反序列化该对象，并调用其 handle()方法。 该方式中, 数据需要提前挂载在 $jobHandlerObject 对象上。
        $isPushed = Queue::push($jobHandlerClassName, $jobDataArr, $jobQueueName);
        if ($isPushed !== false) {
            fwrite($fp, "the $taskType of MultiTask Job has been Pushed to " . $jobQueueName . "<br>");
        } else {
            fwrite($fp, "push a new $taskType of MultiTask Job Failed!");
        }


        // 1.当前任务将由哪个类来负责处理。
        //   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
        $jobHandlerClassName = 'app\job\Hello';

        // 2.当前任务归属的队列名称，如果为新队列，会自动创建
        $jobQueueName = "helloJobQueue";

        // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        //   ( jobData 为对象时，存储其public属性的键值对 )
        $jobData = ['ts' => time(), 'bizId' => uniqid(), 'a' => 1];

        // 4.将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);

        // database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
        if ($isPushed !== false) {
            fwrite($fp, date('Y-m-d H:i:s') . " a new Hello Job is Pushed to the MQ" . "<br>");
        } else {
            fwrite($fp, 'Oops, something went wrong.');
        }
        fclose($fp);
    }
    //生产者
    public function rabbitMqProducer()
    {
        $fp = fopen(app()->getRootPath() . "runtime/rabbitmq.log", "a+");
        $mqConf = config('rabbit_mq')["rabbit_mq_queue"]["test"];
        $rabbitMqService = $this->app->rabbitMqService->instance($mqConf);
        $data = ["name" => "webherobo"];
        $rabbitMqService->wMq($data);
        fwrite($fp, '数据入队.');
        fclose($fp);
    }
    //消费者
    public function RabbitMqConsumer(){

        $mqConf = config('rabbit_mq')["rabbit_mq_queue"]["test"];
        $rabbitMqService = $this->app->rabbitMqService->instance($mqConf);
        //队列别名 ,进程数 ,-d(守护进程) | -s (杀死进程)
        $argv=['test','fire'];
        $rabbitMqService->rabbitMqConsumer($argv);
        $rabbitMqConsumer=new \ReflectionClass("RabbitMqConsumer");
        $rabbitMqConsumer->other($argv);
        echo "ok!";
    }

}