<?php
/**
 * 文件路径： \application\job\Hello.php
 * 这是一个消费者类，用于处理 helloJobQueue 队列中的任务
 */

namespace app\job;

use think\queue\Job;
USE app\model\User;
use think\facade\Db;

class Hello
{

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        $fp = fopen(app()->getRootPath() . "runtime/queuejoblog.log", "a+");
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if (!$isJobStillNeedToBeDone) {
            $job->delete();
            return;
        }

        $isJobDone = $this->doHelloJob($data);
        // $isJobDone=true;
        if ($isJobDone) {
            // 如果任务执行成功， 记得删除任务
            $job->delete();
            fwrite($fp, "Hello Job has been done and deleted!\n");
        } else {
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                // fwrite($fp, 'Hello Job has been retried more than 3 times!');
                $job->delete();

                // 也可以重新发布这个任务
                //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
                //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
            }
            fwrite($fp, "Hello Job failed!\n");
        }
        fclose($fp);
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed $data 发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data)
    {
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    public function doHelloJob($data)
    {
        try {
            $fp = fopen(app()->getRootPath() . "runtime/queuejoblog.log", "a+");
            $lock = app()->lockService->lock('queuetest', 10000);
            $type = false;
            $userModel = new User();
            if ($lock) {
                Db::startTrans();
                $userdata = $userModel->where(["id" => 1])->lock($type)->find();
                try {
                    if ($lock && $userdata['score'] > 0) {
                        $userdata->inc('score')->update();
                        fwrite($fp, $userdata['score'] . "newLOCK_ye Write something here\n");
                    } else {
                        fwrite($fp, "newNoLOCK_ye Write something here\n");
                    }
                    Db::commit();
                    //sleep(3);
                    app()->lockService->unlock($lock);
                } catch (\Exception $e) {
                    Db::rollback();
                    fwrite($fp, "回滚LOCK_ye Write something here\n");
                }
                fwrite($fp, "任务数据:" . json_encode($data) . " Write something here\n");
                fclose($fp);
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            fwrite($fp, $$e->getMessage() . "\n");
        }

    }

    //多个小任务的话，在发布任务时，需要用 任务的类名@方法名 如 app\lib\job\Job2@task1、app\lib\job\Job2@task2
    public function taskA(Job $job, $data)
    {

        $isJobDone = $this->_doTaskA($data);

        if ($isJobDone) {
            $job->delete();
            // print("Info: TaskA of Job MultiTask has been done and deleted" . "\n");
        } else {
            if ($job->attempts() > 3) {
                $job->delete();
            }
        }
    }

    public function taskB(Job $job, $data)
    {

        $isJobDone = $this->_doTaskA($data);

        if ($isJobDone) {
            $job->delete();
            // print("Info: TaskB of Job MultiTask has been done and deleted" . "\n");
        } else {
            if ($job->attempts() > 2) {
                $job->release();
                // 重发，延迟 2 秒执行
                //$job->release(2);
                // 延迟到 2017-02-18 01:01:01 时刻执行
                //$time2wait = strtotime('2017-02-18 01:01:01') - strtotime('now');
                //$job->release($time2wait);
            }
        }
    }

    private function _doTaskA($data)
    {
        $fp = fopen(app()->getRootPath() . "runtime/queuelog.log", "a+");
        fwrite($fp, "Info: doing TaskA of Job MultiTask " . "\n");
        fclose($fp);
        return true;
    }

    private function _doTaskB($data)
    {
        $fp = fopen(app()->getRootPath() . "runtime/queuelog.log", "a+");
        fwrite($fp, "Info: doing TaskB of Job MultiTask " . "\n");
        fclose($fp);
        return true;
    }

    /**
     * 该方法用于接收任务执行失败的通知，你可以发送邮件给相应的负责人员
     * @param $jobData  string|array|...      //发布任务时传递的 jobData 数据
     */
    public function failed($jobData)
    {
        $fp = fopen(app()->getRootPath() . "runtime/queuelog.log", "a+");
        fwrite($fp, 'Warning: Job failed after max retries. job data is :' . var_export($jobData, true) . "\n");
        fclose($fp);
    }
}