<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Mytest extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('hello')
            ->addArgument('type', Argument::OPTIONAL, "0:消费者 1:生产者")
            ->addOption('test', null, Option::VALUE_REQUIRED, 'city name')
            ->setDescription('option=test name=hello argv=type');
    }

    protected function execute(Input $input, Output $output)
    {
        // rabbitmq队列任务指令输出
        $type=$input->hasOption('test');
        if ($type==0) {
            $mqConf = config('rabbit_mq')["rabbit_mq_queue"]["test"];
            $this->app->rabbitMqService->instance($mqConf);
            //队列别名 ,进程数 ,-d(守护进程) | -s (杀死进程)
            $argv = ['test', 'fire'];
            $this->app->rabbitMqService->rabbitMqConsumer($argv);
            $rabbitMqConsumer = new \ReflectionClass("app\job\RabbitMqConsumer");
            $dealObj = $rabbitMqConsumer->newInstance();
            $dealObj->other($argv);
            echo "ok!";
        } else {
            //生产者
            $fp = fopen(app()->getRootPath() . "runtime/rabbitmq.log", "a+");
            $mqConf = config('rabbit_mq')["rabbit_mq_queue"]["test"];
            $this->app->rabbitMqService->instance($mqConf);
            $data = ["name" => "webherobo"];
            $this->app->rabbitMqService->wMq($data);
            fwrite($fp, "数据入队.\n");
            fclose($fp);
            echo "ok!";

        }

    }
}
