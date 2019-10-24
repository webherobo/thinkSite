<?php


namespace app\service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\Service;

class RabbitmqService extends Service
{

    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        //
    }


    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //
    }

    //测试
    public function test()
    {

        //this::instance('test')->wMq(['name'=>'123']);//入队
        $rabitmqConfig = config('rabbit_mq');
        $exchangeName = $rabitmqConfig["rabbit_mq_queue"]["test"]["exchange_name"]; //交换机名
        $queueName = $rabitmqConfig["rabbit_mq_queue"]["test"]["queue_name"]; //队列名称
        $routingKey = $rabitmqConfig["rabbit_mq_queue"]["test"]["DealTest"]; //路由关键字(也可以省略)

        $conn = new AMQPStreamConnection( //建立生产者与mq之间的连接
            $rabitmqConfig['host'], $rabitmqConfig['port'], $rabitmqConfig['user'], $rabitmqConfig['pwd'], $rabitmqConfig['vhost']
        );
        $channel = $conn->channel(); //在已连接基础上建立生产者与mq之间的通道


        $channel->exchange_declare($exchangeName, 'direct', false, true, false); //声明初始化交换机
        $channel->queue_declare($queueName, false, true, false, false); //声明初始化一条队列
        $channel->queue_bind($queueName, $exchangeName, $routingKey); //将队列与某个交换机进行绑定，并使用路由关键字

        $msgBody = json_encode(["name" => "iGoo", "age" => 22]);
        $msg = new AMQPMessage($msgBody, ['content_type' => 'text/plain', 'delivery_mode' => 2]); //生成消息
        $r = $channel->basic_publish($msg, $exchangeName, $routingKey); //推送消息到某个交换机
        $channel->close();
        $conn->close();
    }


}

class producer
{


    /**
     * User: webherobo
     * @var
     * Description:
     */
    private $channel;

    private $mqConf;

    /**
     * RabbitMQTool constructor.
     * @param $mqName
     */
    public function __construct($mqName)
    {
        // 获取rabbitmq所有配置
        $rabbitMqConf = config('rabbit_mq');
        if (!isset($rabbitMqConf['rabbit_mq_queue'])) {
            die('没有定义Source.rabbit_mq');
        }
        //建立生产者与mq之间的连接
        $this->conn = new AMQPStreamConnection(
            $rabbitMqConf['host'], $rabbitMqConf['port'], $rabbitMqConf['user'], $rabbitMqConf['pwd'], $rabbitMqConf['vhost']
        );
        $channal = $this->conn->channel();
        if (!isset($rabbitMqConf['rabbit_mq_queue'][$mqName])) {
            die('没有定义' . $mqName);
        }
        // 获取具体mq信息
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$mqName];
        $this->mqConf = $mqConf;
        // 声明初始化交换机
        $channal->exchange_declare($mqConf['exchange_name'], 'direct', false, true, false);
        // 声明初始化一条队列
        $channal->queue_declare($mqConf['queue_name'], false, true, false, false);
        // 交换机队列绑定
        $channal->queue_bind($mqConf['queue_name'], $mqConf['exchange_name']);
        $this->channel = $channal;
    }

    /**
     * User: webherobo
     * @param $mqName
     * @return RabbitMQTool
     * Description: 返回当前实例
     */
    public static function instance($mqName)
    {
        return new RabbitMQTool($mqName);
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
}

class consumer
{
    /**
     * 消费者
     * @throws \Exception
     */
    private $dealPath = null;

    private $childsPid = array();

    /**
     * StartRabbitMQ constructor.
     */
    public function __construct()
    {
        // 脚本路径
        $this->dealPath = str_replace('/', '\\', "/app/daemon/deal/");
    }

    /**
     * User: webherobo
     * Description: 返回当前实例
     */
    public static function instance()
    {
        return new RabbitmqService();
    }

    /**
     * User: webherobo
     * Description: 主要处理流程
     * @throws \ErrorException
     */
    public function main()
    {
        global $argv;
        // 扩展参数
        if (isset($argv[3])) {
            switch ($argv[3]) {
                case '-d': // 守护进程启动
                    $this->daemonStart();
                    break;
                case '-s': // 杀死进程
                    $this->killEasyExport($argv[2]);
                    die();
                    break;
            }
        }
        // 判断参数
        if (count($argv) < 2) {
            die('缺少参数');
        }
        // 获取配置信息
        $rabbitMqConf = config('rabbit_mq');
        if (!isset($rabbitMqConf['rabbit_mq_queue'][$argv[2]])) {
            die('没有配置:' . $argv[2]);
        }
        // 获取mq配置
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$argv[2]];
        // 实例化处理脚本
        $dealClass = $this->dealPath . $mqConf['consumer'];
        $dealObj = new $dealClass;
        $processNum = 1;
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
                $this->downMqData($dealObj, $argv, $mqConf);
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
    private function downMqData($dealObj, $argv, $mqConf)
    {
        while (true) {
            // 下载数据
            $mqData = RabbitMQTool::instance($argv[2])->rMq($mqConf['deal_num']);
            $dealObj->deal($mqData);
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