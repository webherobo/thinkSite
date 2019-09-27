<?php

namespace app\controller;

use app\BaseController;
USE app\model\User;
USE app\validate\UserValidate;
use think\exception\ValidateException;

class Index extends BaseController
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V6<br/><span style="font-size:30px">13载初心不改 - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
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
            validate(User::class)->check([
                'name' => 'thinkphp',
                'email' => 'thinkphp@qq.com',
            ]);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            dump($e->getError());
        }
        //批量验证
        try {
            $result = validate(User::class)->batch(true)->check([
                'name' => 'thinkphp',
                'email' => 'thinkphp@qq.com',
            ]);

            if (true !== $result) {
                // 验证失败 输出错误信息
                dump($result);
            }
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            dump($e->getError());
        }


        $list = UserValidate::select();
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
