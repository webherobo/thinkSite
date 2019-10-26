<?php

namespace app\job;
class RabbitMqConsumer
{
    private function config(){
        return [
            'exchange_name' => 'ex_test', // 交换机名称
            'queue_name' => 'que_test', // 队列名称
            'process_num' => 3, // 默认单台机器的进程数量
            'deal_num' => '50', // 单次处理数量
            'consumer' => 'RabbitMqConsumer' // 消费地址
        ];
    }
    public function fire($mqData)
    {
        $fp = fopen(app()->getRootPath() . "runtime/rabbitmqSuccess.log", "a+");
        fwrite($fp, '数据出队处理完成.数据为：'.json_encode($mqData)."\n");
        fclose($fp);
    }
    public function other($mqData){
        $fp = fopen(app()->getRootPath() . "runtime/rabbitmqSuccess.log", "a+");
        fwrite($fp, '其他任务示例数据出队处理完成.数据为：'.json_encode($mqData)."\n");
        fclose($fp);
    }
}