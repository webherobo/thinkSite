<?php

namespace app\controller;

use app\BaseController;
USE app\model\User;
USE app\validate\UserValidate;
use think\exception\ValidateException;
use think\facade\View;

think\facade\Cache;

class Index extends BaseController
{
    public function index()
    {
        // 获取缓存对象句柄
        $cacheHandler = Cache::handler();
        //Cache::set('name', "webherobo", 3600);
        Cache::set('name', 1);
        // name自增（步进值为1）
        Cache::inc('name');
        // name自增（步进值为3）
        Cache::inc('name', 3);
        Cache::dec('name',3);
        Cache::get('name','');
        Cache::set('name', [1,2,3]);
        Cache::push('name', 4);
        Cache::remember('start_time', time());
        Cache::tag('tag')->clear();
        Cache::getTagItems('tag');
       // Cache::store('redis')->get('name');
         return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V6<br/><span style="font-size:30px">13载初心不改 --- 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }

    /**
     * @return 测试
     */
    public function getTest()
    {
        try {
            //批量验证
            validate(UserValidate::class)->scene('edit')->batch(true)->check([
                'name' => 'thinkphp',
                'age' => 100,
                'email' => 'thinkphp@qq.com',
            ]);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            dump($e->getError());
        }

        $list = User::select()->toArray();
        //var_dump(view('test', ["list"=>$list]));
        //View::assign(["list"=>$list]);
        //return View::fetch('test');
        //return view('test', ["list"=>$list]);//助手
        $this->view->assign('list', $list);
        return $this->view->fetch('test');
    }

    public function upload()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $files = request()->file('image');
        if (count($files)) {
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::putFile('topic', $files);

        } else {
            //多文件
            try {
                validate(['image' => 'filesize:10240|fileExt:jpg|image:200,200,jpg'])
                    ->check($files);
                $savename = [];
                foreach ($files as $file) {
                    $savename[] = \think\facade\Filesystem::putFile('topic', $file);
                }
            } catch (think\exception\ValidateException $e) {
                echo $e->getMessage();
            }

        }


    }
}
