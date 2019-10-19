<?php
declare(strict_types=1);

namespace app\service;

use think\Service;

/**
 * Class LockService
 * @package app\service
 */
class LockService extends Service
{
    private $retryDelay;
    private $retryCount;
    private $clockDriftFactor = 0.01;//漂移的因素
    private $quorum;//仲裁
    private $servers = array();
    private $instances = array();

    function __construct(array $servers, $retryDelay = 200, $retryCount = 3)
    {
        $this->servers = $servers;
        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
        $this->quorum = min(count($servers), (count($servers) / 2 + 1));
    }

    public function lock($resource, $ttl)
    {
        $this->initInstances();
        $token = uniqid();
        $retry = $this->retryCount;
        do {
            $n = 0;
            $startTime = microtime(true) * 1000;
            foreach ($this->instances as $instance) {
                if ($this->lockInstance($instance, $resource, $token, $ttl)) {
                    $n++;
                }
            }
            # Add 2 milliseconds to the drift to account for Redis expires
            # precision, which is 1 millisecond, plus 1 millisecond min drift
            # for small TTLs.
            $drift = ($ttl * $this->clockDriftFactor) + 2;
            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;
            if ($n >= $this->quorum && $validityTime > 0) {
                return [
                    'validity' => $validityTime,
                    'resource' => $resource,
                    'token' => $token,
                ];
            } else {
                foreach ($this->instances as $instance) {
                    $this->unlockInstance($instance, $resource, $token);
                }
            }
            // Wait a random delay before to retry
            $delay = mt_rand(floor($this->retryDelay / 2), $this->retryDelay);
            usleep($delay * 1000);
            $retry--;
        } while ($retry > 0);
        return false;
    }

    public function unlock(array $lock)
    {
        $this->initInstances();
        $resource = $lock['resource'];
        $token = $lock['token'];
        foreach ($this->instances as $instance) {
            $this->unlockInstance($instance, $resource, $token);
        }
    }

    private function initInstances()
    {
        if (empty($this->instances)) {
            foreach ($this->servers as $server) {
                list($host, $port, $timeout) = $server;
                $redis = new \Redis();
                $redis->connect($host, $port, $timeout);
                $this->instances[] = $redis;
            }
        }
    }

    private function lockInstance($instance, $resource, $token, $ttl)
    {
        return $instance->set($resource, $token, ['NX', 'PX' => $ttl]);
    }

    private function unlockInstance($instance, $resource, $token)
    {
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';
        return $instance->eval($script, [$resource, $token], 1);
    }


    //REDIS锁示例
    public function redislock()
    {
        $servers = [
            ['127.0.0.1', 6379, 0.01],
            ['127.0.0.1', 6389, 0.01],
            ['127.0.0.1', 6399, 0.01],
        ];
        $redLock = new LockService($servers);
        while (true) {
            $lock = $redLock->lock('test', 10000);
            if ($lock) {
                print_r($lock);
            } else {
                print "Lock not acquired\n";
            }
        }
    }

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

//示例2

    public function filelock2()
    {
        $pid = pcntl_fork();
        $fp = fopen("log.txt", "a");

        if ($pid == 0) {
            for ($i = 0;
                 $i < 1000;
                 $i++) {
                if (flock($fp, LOCK_EX)) {
                    fwrite($fp, "黄河远上白云间，");
                    fflush($fp);
                    fwrite($fp, "一片孤城万仞山。");
                    fflush($fp);
                    fwrite($fp, "羌笛何须怨杨柳，");
                    fflush($fp);
                    fwrite($fp, "春风不度玉门关。\n");
                    fflush($fp);
                    flock($fp, LOCK_UN);
                }
            }
        } else if ($pid > 0) {
            for ($i = 0; $i < 1000; $i++) {
                if (flock($fp, LOCK_EX)) {
                    fwrite($fp, "葡萄美酒夜光杯，");
                    fflush($fp);
                    fwrite($fp, "欲饮琵琶马上催。");
                    fflush($fp);
                    fwrite($fp, "醉卧沙场君莫笑，");
                    fflush($fp);
                    fwrite($fp, "古来征战几人回。\n");
                    fflush($fp);
                    flock($fp, LOCK_UN);
                }
            }
        }
    }
}