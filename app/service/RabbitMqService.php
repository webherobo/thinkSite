<?php


namespace app\service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\Service;

class RabbitMqService extends Service
{
    /**
     * User: webherobo
     * @var
     * Description:
     */
    private $channel;

    private $mqConf;

    /**
     * 消费者
     * @throws \Exception
     */
    private $dealPath = null;

    private $childsPid = array();

    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        $this->app->bind('rabbitMqService', RabbitmqService::class);
    }


    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        // 获取rabbitmq所有配置
        $rabbitMqConf = config('rabbit_mq');
        $this->mqConf = $rabbitMqConf;
        if (!isset($rabbitMqConf['rabbit_mq_queue'])) {
            die('没有定义rabbit_mq');
        }

    }

    /**
     * User: webherobo
     * @param $mqName
     * @return RabbitMQTool
     * Description: 返回当前实例
     */
    public function instance($mqConf = [])
    {
        if (empty($mqdata)) {
            $mqConf = config('rabbit_mq')["rabbit_mq_queue"]["test"];
        }
        $this->mqConf = config('rabbit_mq');
        //建立生产者与mq之间的连接
        $this->conn = new AMQPStreamConnection(
            $this->mqConf['host'], $this->mqConf['port'], $this->mqConf['user'], $this->mqConf['pwd'], $this->mqConf['vhost']
        );
        $this->channel = $this->conn->channel();
        // 声明初始化交换机
        $this->channel->exchange_declare($mqConf['exchange_name'], 'direct', false, true, false);
        // 声明初始化一条队列
        $this->channel->queue_declare($mqConf['queue_name'], false, true, false, false);
        // 交换机队列绑定
        $this->channel->queue_bind($mqConf['queue_name'], $mqConf['exchange_name']);
        return $this;
    }

    /**
     * User: webherobo
     * @param $data
     * Description: 写mq
     * @return bool
     */
    public function wMq($data)
    {
        try {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $msg = new AMQPMessage($data, ['content_type' => 'text/plain', 'delivery_mode' => 2]);
            $this->channel->basic_publish($msg, $this->mqConf['exchange_name']);
        } catch (\Throwable $e) {
            $this->closeConn();
            return false;
        }
        $this->closeConn();
        return true;
    }

    /**
     * User: webherobo
     * @param int $num
     * @return array
     * Description:
     * @throws \ErrorException
     */
    public function rMq($num = 1)
    {
        $rData = [];
        $callBack = function ($msg) use (&$rData) {
            $rData[] = json_decode($msg->body, true);
        };
        for ($i = 0; $i < $num; $i++) {
            $this->channel->basic_consume($this->mqConf['queue_name'], '', false, true, false, false, $callBack);
        }
        $this->channel->wait();
        $this->closeConn();
        return $rData;
    }

    /**
     * User: webherobo
     * Description: 关闭连接
     */
    public function closeConn()
    {
        $this->channel->close();
        $this->conn->close();
    }

    /**
     * RabbitMqConsumer.
     * $argv=[,$queuename,$type]; 参数 $queuename默认取值config('rabbit_mq')["rabbit_mq_queue"]["test"] ;$type:'-d'||'-s';
     */
    public function rabbitMqConsumer($argv)
    {
        $argv[0] ?? $argv[0] = 'test';
        $argv[1] ?? $argv[1] = 'fire';
        $argv[2] ?? $argv[2] = '-d';
        $argv[3] ?? $argv[3]=1;
        // 脚本路径
        $this->dealPath = str_replace('/', '\\', "/app/job/");
        // 扩展参数
        if (isset($argv[2])) {
            switch ($argv[2]) {
                case '-d': // 守护进程启动
                    $this->daemonStart();
                    break;
                case '-s': // 杀死进程
                    $this->killEasyExport($argv[0]);
                    die();
                    break;
            }
        }
        // 判断参数
        if (count($argv) < 4) {
            die('缺少参数');
        }
        // 获取配置信息
        $rabbitMqConf = config('rabbit_mq');
        if (!isset($rabbitMqConf['rabbit_mq_queue'][$argv[0]])) {
            die('没有配置:' . $argv[0]);
        }
        // 获取mq配置
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$argv[0]];
        if(!empty($this->dealPath)){
            // 实例化处理脚本
            $dealClass = $this->dealPath . $mqConf['consumer'];
        }else{
            $dealClass=$mqConf['consumer'];
//            $dealReflection = new \ReflectionClass($dealClass);  // 将类名consumer作为参数，即可建立consumer类的反射类
//            $dealObj = $dealReflection->newInstance();
//            $getconfig = $dealReflection->getMethod('config')->setAccessible(true);
            $dealReflection=new \ReflectionMethod($dealClass, 'config');
            $mqConf=$dealReflection->setAccessible(true)->invoke();
        }

        $processNum = $argv[3];
        if (isset($mqConf['process_num']) || !is_numeric($mqConf['process_num']) || $mqConf['process_num'] < 1 || $mqConf['process_num'] > 10) {
            $processNum = $mqConf['process_num'];
        }
        if (!isset($mqConf['deal_num']) || !is_numeric($mqConf['deal_num'])) {
            die('处理条数设置有误');
        }
        // fork进程
        for ($i = 0; $i < $processNum; $i++) {
            $pid = pcntl_fork();
            if ($pid < 0) {
                exit();
            } else if (0 == $pid) {
                $this->downMqData($dealClass, $argv, $mqConf);
                exit();
            } else if ($pid > 0) {
                $this->childsPid[] = $pid;
            }
        }
        while (true) {
            sleep(1);
        }
    }

    /**
     * User: webherobo
     * @param $dealObj
     * @param $argv
     * @param $mqConf
     * @throws \ErrorException
     * Description:
     */
    private function downMqData($dealClass, $argv, $mqConf)
    {
        while (true) {
            // 下载数据
            $mqData = $this->instance($argv[0])->rMq($mqConf['deal_num']);
            $dealReflection=new \ReflectionMethod($dealClass, argv[1]);
            $dealReflection->invoke($mqData);
            sleep(1);
        }
    }

    private function killEasyExport($startFile)
    {
        exec("ps aux | grep $startFile | grep -v grep | awk '{print $2}'", $info);
        if (count($info) <= 1) {
            echo "not run\n";
        } else {
            echo "[$startFile] stop success";
            exec("ps aux | grep $startFile | grep -v grep | awk '{print $2}' |xargs kill -SIGINT", $info);
        }
    }

    /**
     * User: webherobo
     * Description: 守护进程模式启动
     */
    private function daemonStart()
    {
        // 守护进程需要pcntl扩展支持
        if (!function_exists('pcntl_fork')) {
            exit('Daemonize needs pcntl, the pcntl extension was not found');
        }
        umask(0);
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit('fork error.');
        } else if ($pid > 0) {
            exit();
        }
        if (!posix_setsid()) {
            exit('setsid error.');
        }
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit('fork error');
        } else if ($pid > 0) {
            // 主进程退出
            exit;
        }
        // 子进程继续，实现daemon化
    }

}