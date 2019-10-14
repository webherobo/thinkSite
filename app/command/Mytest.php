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
        $this->setName('test')
            ->setDescription('the test command');        
    }

    protected function execute(Input $input, Output $output)
    {
    	// 指令输出
    	$output->writeln('test');
    }
}
