<?php
declare (strict_types=1);

namespace app\command;

use Swoole\Coroutine;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\swoole\concerns\InteractsWithHttp;
use think\swoole\concerns\InteractsWithRpc;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\InteractsWithWebsocket;

class swooleServer extends Command
{
    //swoole特性
    use InteractsWithHttp;

    //use InteractsWithPool;
    //use InteractsWithPoolConnector;
    use InteractsWithRpc;
    use InteractsWithServer;
    use InteractsWithSwooleTable;
    use InteractsWithWebsocket;


    protected $port = 8081;//9052;


    private $serv;


    private $db_config = [];


    private $redis_server = "127.0.0.1";


    private $redis_port = 6379;


    private $redis_pwd = "";


    private $all_fd_token_map = "all_tunnel_online_map";


    protected function configure()
    {
        // 指令配置
        $this->setName('swooleServer')
            ->setDescription('the swooleServer command');

        /* 读取站点配置 */

        $this->set_config();

        echo "构造函数初始化。。。\n";

        //    var_dump(config("database"));

        $this->db_config = config("database");

        //redie 配置

        $this->redis_server = '127.0.0.1';

        $this->redis_port =  6379;

        $this->redis_pwd =  "";

        //$this->clean_all_tunnel_key();

        //swoole
        if (!defined('GLOBAL_START')) {

            $this->serv = new \swoole_server("0.0.0.0", $this->port);

            $this->serv->set(array(

                'worker_num' => 8,//建议开启的worker进程数为cpu核数的1-4倍

                'daemonize' => false,

                'max_request' => 10000,

                'dispatch_mode' => 2,

                'debug_mode' => 1,

                'task_worker_num' => 8

            ));

            //'reactor_num' => 8 //，默认会启用CPU核数相同的数量， 一般设置为CPU核数的1-4倍，最大不得超过CPU核数*4。
            $this->serv->on('WorkerStart', array($this, 'onWorkerStart'));

            $this->serv->on('Start', array($this, 'onStart'));

            $this->serv->on('Connect', array($this, 'onConnect'));

            $this->serv->on('Receive', array($this, 'onReceive'));

            $this->serv->on('Request', array($this, 'onRequest'));

            $this->serv->on('Close', array($this, 'onClose'));

            $this->serv->on('Task', array($this, 'onTask'));

            // bind callback

            $this->serv->on('Finish', array($this, 'onFinish'));

            $this->serv->start();

            define('GLOBAL_START', true);

        }
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('swooleServer runing');
    }

    public function onStart($serv)
    {

        echo "Start OK\n";

        echo "确保 onstart 时 所有的 相关都初始化 ！重启后 fd 会重头再记录 ，redis 里面的数据 将失准";

        dump(config("PUBLIC_REDIS_ADDR"));

        // 清空已有 的redis 相关业务 可能涵盖 多平台

    }

    public function onConnect($serv, $fd, $from_id)
    {

        //    $serv->send($fd, "Hello {$fd}!"); // 打招呼

        echo "lingking——fd:----" . $fd;
        // 打印

        echo " ";

        echo "lingking——from_id:----" . $from_id; // 打印work id

    }

    public function onRequest($request, $response)
    {
        $response->header("Server", "SwooleServer");
        $response->header("Content-Type", "text/html; charset=utf-8");
        $server = $request->server;
        $path_info = $server['path_info'];
        $request_uri = $server['request_uri'];

        if ($path_info == '/favicon.ico' || $request_uri == '/favicon.ico') {
            return $response->end();
        }

        $controller = 'Index';
        $method = 'home';


        if ($path_info != '/') {
            $path_info = explode('/', $path_info);
            if (!is_array($path_info)) {
                $response->status(404);
                $response->end('URL不存在');
            }

            if ($path_info[1] == 'favicon.ico') {
                return;
            }

            $count_path_info = count($path_info);
            if ($count_path_info > 4) {
                $response->status(404);
                $response->end('URL不存在');
            }

            $controller = (isset($path_info[1]) && !empty($path_info[1])) ? $path_info[1] : $controller;
            $method = (isset($path_info[2]) && !empty($path_info[2])) ? $path_info[2] : $method;
        }

        $result = "class 不存在";

        if (class_exists($controller)) {
            $class = new $controller();
            $result = "method 不存在";
            if (method_exists($controller, $method)) {
                $result = $class->$method($request);
            }
        }

        $response->send($result);


        $_SERVER = [];
        //server
        if (isset($request->server)) {
            foreach ($request->server as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        //header
        if (isset($request->header)) {
            foreach ($request->header as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        //get
        $_GET = [];
        if (isset($request->get)) {
            foreach ($request->get as $k => $v) {
                $_GET[$k] = $v;
            }
        }
        //post
        $_POST = [];
        if (isset($request->post)) {
            foreach ($request->post as $k => $v) {
                $_POST[$k] = $v;
            }
        }
        //开启缓冲区
        ob_start();
        // 执行应用并响应
        try {
            think\Container::get('app', [APP_PATH])
                ->run()
                ->send();
        } catch (\Exception $e) {
            // todo
        }
        //输出TP当前请求的控制方法
        //echo "-action-".request()->action().PHP_EOL;
        //获取缓冲区内容
        $res = ob_get_contents();
        ob_end_clean();

        // $response->header("Content-Type","text/html");
        $response->header("charset", "utf-8");
        $response->send($res);
        //把之前的进程kill，swoole会重新启一个进程，重启会释放内存，把上一次的资源包括变量等全部清空
        //$http->close();

        // 判断未初始化完毕，则挂起协程
        if (!defined('APP_INITED')) {
            $GLOBALS['WORKER_START_END_RESUME_COIDS'][] = Coroutine::getuid();
            Coroutine::suspend();
        }
        $response->header('content-type', 'text/html;charset=utf-8');
        $response->end($res);
    }

    public function onReceive(\swoole_server $serv, $fd, $from_id, $data)
    {

        echo "有新消息 来自客户端 {$fd} Client :{$data}\n";

        if ($this->is_json($data)) {

            $data = json_decode($data, true);

        }

        $param = array(

            'fd' => $fd,

            'data' => $data

        );

        // start a task

        $serv->task(json_encode($param));

        //    echo "上面已经 交个task 这里不影响 做其他事 over\n";

    }

    public function onTask($serv, $task_id, $from_id, $in_data)
    {

        $backbool = "false";

        //    echo "This Task {$task_id} from Worker {$from_id}\n";

        //    echo "Data: {$in_data}\n";

        //    var_dump(json_decode($in_data,true));

        $fd = json_decode($in_data, true)['fd'];

        $data = json_decode($in_data, true)['data'];
        echo json_encode($data["message"]);
        if (!isset($data["data"]["token"]) || !isset($data["data"]["platform"])) {

            echo "缺少token或者platform";

            $serv->send($fd, "缺少token或者platform");
            // 这里作为回复客户端

            return "fd: {$fd} Task {$task_id}'s result";

        }

        // data 中 有三参数 token platfom info(内涵 now_mac set mac)

        dump($this->redis_server);

        $redis = new \Redis();

        $redis->pconnect($this->redis_server, $this->redis_port);

        if (!empty($this->redis_pwd)) {

            $redis->auth($this->redis_pwd);

        }

        $tokenall = $data["token"];

        $time_length = 3 * 60;

        $bad_token_key = "aur_bad_token_" . $tokenall;

        $check_bool = $this->bad_token_check($bad_token_key);

        if (empty($check_bool)) {

            $backbool = "false";

            $re_mag = $this->send_msg($fd, $backbool);
            // 这里作为回复客户端

            return "fd: {$fd} 触发的 Task {$task_id} 的 结果:{$re_mag}\n";

        }

        $platform = $data["platform"];

        $token = substr($tokenall, 0, 32);

        $tunnel_id = substr($tokenall, 33);

        // 一个键 两个囊 一个放最大数量 另一个放 fd 对 用hash token_all:[fd1:1,fd2:1....max_num:100]

        if ($platform == 4) {// 目前只有极光做了 隧道

            $out_key = "aur_tunnel_online_" . $tokenall;

            // 先验证

            $have_fd = $redis->hExists($out_key, $fd);

            if ($have_fd) {

                $backbool = "true";

                echo "有记录 {$fd}\n";

                $re_mag = $this->send_msg($fd, $backbool);
                // 这里作为回复客户端

                return "fd: {$fd} 触发的 Task {$task_id} 的 结果:{$re_mag}\n";

            }

            echo "该id 没有记录 {$fd}\n";

            // 没有在里面 就 要重新搞了

            $have_max = $redis->hExists($out_key, "max_num");

            if (!$have_max) {

                echo "没找到最大数 {$out_key}\n";

                $tunnel_info = $this->set_max_num($prefix = "aur_", $tunnel_id, $token, $out_key);// 重置下

                if (!empty($tunnel_info)) {

                    $max_num = $tunnel_info["online_max_num"];

                    $redis->hSet($out_key, "max_num", $max_num);

                    echo "设置后获取max_num：" . $redis->hGet($out_key, "max_num");

                    //          $redis->expire($out_key,5*60);// 60s 从库里面校验

                } else {// 这里 要 做下阻挡 由于 非法token 一直查询不到 ，每次过来查库 对 数据库造成压力

                    //bad_token 入库

                    $redis->set("aur_bad_token_" . $tokenall, NOW_TIME + $time_length);

                    $max_num = 0; //这里很重要 就是 当 token 不对 时 $max_num

                }

            } else {

                $max_num = $redis->hGet($out_key, "max_num");

            }

            $num_now = $redis->hLen($out_key) - 1;// 里面多了一个 键max_num

            echo " {$out_key}最大数：{$max_num},现在数：$num_now\n";

            if (!empty($max_num) && $max_num > $num_now) {

                // 验证一个 并且放入 （有就算了）

                $new_fd = $redis->hSet($out_key, $fd, $fd);

                $map_up = $redis->hSet($this->all_fd_token_map, $fd, $out_key); // all_tunnel_online_map:[fd1:aurtoken,fd2:inttoken2,fd3:token2]

                echo "new_fd {$fd} 入库 \n";

                echo "{$fd}:{$out_key}map 入库结果:{$map_up}\n";

                var_dump($new_fd);

                $backbool = "true";

            }

        } else {// 如果有其他的 请在这里 做分支判断

        }

        $redis->close();

        $msg = $this->send_msg($fd, $backbool);
        // 这里作为回复客户端

        return "fd: {$fd} Task {$task_id}'s 结果{$msg}";

    }

    private function bad_token_check($bad_token_key)
    {

        $redis = new \Redis();

        $redis->pconnect($this->redis_server, $this->redis_port);

        if (!empty($this->redis_pwd)) {

            $redis->auth($this->redis_pwd);

        }

        $expire = $redis->get($bad_token_key);

        echo "bad 过期时间是：{$expire}\n";

        if ($expire > NOW_TIME) {//被锁了

            echo "{$bad_token_key}这token是个坏小子\n";

            return false;

        }

        return true;

    }

    /**
     * @param string $prefix
     * @param $tunnel_id
     * @param $token
     * @param $out_key
     * @return bool|mixed
     */

    private function set_max_num($prefix = "aur_", $tunnel_id, $token, $out_key)
    {

        // 矫正用

        echo "矫正ing....................................数据库查询\n";

        $tunnel_info = Db::connect($this->db_config)->table($prefix . "tunnel_user_package")->where(['id' => $tunnel_id])->find();

        if (!$tunnel_info) {

            return false;

        }

        if (md6($tunnel_id . "lingjiang735" . $tunnel_info["salt"]) != $token) {

            return false;

        }

        return $tunnel_info;

    }

    public function send_msg($fd, $msg)
    {

        $reminder = "向->{$fd} 发送-> {$msg}\n";

        $this->serv->send($fd, $msg);

        return $reminder;

    }

    public function onFinish($serv, $task_id, $data)
    {

        echo "Task {$task_id} over\n";

        echo "Finish: {$data}\n";

    }

    public function onClose($serv, $fd, $from_id)
    {
        // 这个端的唯一 链接 id

        $redis = new \Redis();

        $redis->pconnect($this->redis_server, $this->redis_port);

        if (!empty($this->redis_pwd)) {

            $redis->auth($this->redis_pwd);

        }
        $have_map = $redis->hExists($this->all_fd_token_map, strval($fd));

        if ($have_map) {

            $token_key = $redis->hGet($this->all_fd_token_map, $fd);

            echo "3 {$fd}查询到token_key:{$token_key}\n";

            //删除该token_key 下的

            $re = $redis->hDel($token_key, $fd);

            echo "4 {$fd}删除结果：$re\n";

            echo "over\n";

        } else {

            echo "3 {$fd}没有查询到token_key\n";

        }

        $redis->close();

    }

    private function is_json($str)
    {

        return is_array(json_decode($str, true)) && !empty(json_decode($str));

    }

    /**
     * 从数据库拿到
     */

    private function set_config()
    {

        // $m = Db::connect($this->db_config)->table('wt_config');

        $r = [];//$m->select();

        foreach ($r as $k => $v) {

            $r[$k]['name'] = strtoupper($r[$k]['code']);

        }

        $r = array_column($r, 'value', 'code');

        cache('config_cache', $r);

        //取配置,赋值

        config(cache('config_cache')); //添加配置

        echo "设置缓存";

    }

    private function clean_all_tunnel_key()
    {

        $redis = new \Redis();

        $redisserver = $this->redis_server;

        $redisport = $this->redis_port;

        $redispwd = $this->redis_pwd;

        $redis->pconnect($redisserver, $redisport);

        if (!empty($redispwd)) {

            $redis->auth($redispwd);

        }

        echo "清理前各个token_list ：\n";

        $infos = $redis->keys('aur_tunnel_online_*');

        dump($infos);

        $redis->delete($infos);

        echo "清理前各个token_list ：\n";

        $infos = $redis->keys('aur_tunnel_online_*');

        dump($infos);

        echo "清理前map：\n";

        $infos = $redis->keys($this->all_fd_token_map);

        dump($infos);

        $redis->delete($infos);

        echo "清理前各个token_list and map：\n";

        $infos = $redis->keys($this->all_fd_token_map);

        dump($infos);

    }

    public function onWorkerStart($server, $workerId)
    {
        $initFlagFile = __DIR__ . '/init.flag';
        if (0 === $server->worker_id && (!is_file($initFlagFile) || file_get_contents($initFlagFile) != $server->manager_pid)) {
            // 处理项目初始化事件
            $this->initApp();
            // 写入文件，保证不再重复触发项目初始化事件
            file_put_contents($initFlagFile, $server->manager_pid);
            // 当前worker进程恢复协程
            $this->resumeCos();
            // 通知其它worker进程
            for ($i = 1; $i < $server->setting['worker_num']; ++$i) {
                echo '通知恢复协程',PHP_EOL;
               // $server->sendMessage('init', $i);
            }
        }
    }

    public function onPipeMessage($server, $workerId, $data)
    {
        if (0 === $workerId && 'init' === $data && !defined('APP_INITED')) {
            // 其它worker进程恢复协程
            $this->resumeCos();
        }
    }

    /**
     * 处理项目初始化事件，比如这里延时5秒，模拟初始化处理
     *
     * @return void
     */
    function initApp()
    {
        $count = 5;
        for ($i = 0; $i < $count; ++$i) {
            echo 'initing ', ($i + 1), '/', $count, PHP_EOL;
            sleep(1);
        }
    }

    /**
     * 恢复协程
     *
     * @return void
     */
    function resumeCos()
    {
        define('APP_INITED', true);
        $coids = $GLOBALS['WORKER_START_END_RESUME_COIDS'] ?? [];
        fwrite(STDOUT, 'suspend co count: ' . count($coids) . PHP_EOL);
        foreach ($coids as $id) {
            Coroutine::resume($id);
        }
    }



//    private $onStart = null;
//
//    private $onShutdown = null;
//
//    private $onWorkerStart = null;
//
//    private $onWorkerStop = null;
//
//    private $onWorkerExit = null;
//
//    private $onWorkerError = null;
//
//    private $onTask = null;
//
//    private $onFinish = null;
//
//    private $onManagerStart = null;
//
//    private $onManagerStop = null;
//
//    private $onPipeMessage = null;


//    protected $events = [
//        'start',
//        'shutDown',
//        'workerStart',
//        'workerStop',
//        'workerError',
//        'packet',
//        'task',
//        'finish',
//        'pipeMessage',
//        'managerStart',
//        'managerStop',
//        'request',
//    ];
}
