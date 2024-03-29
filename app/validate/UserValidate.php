<?php

namespace app\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'name' => 'require|max:25',
        //'name' =>'checkName:thinkphp',
        'age' => 'number|between:1,120',
        'email' => 'email',
    ];

    protected $message = [
        'name.require' => '名称必须',
        'name.max' => '名称最多不能超过25个字符',
        'age.number' => '年龄必须是数字',
        'age.between' => '年龄只能在1-120之间',
        'email' => '邮箱格式错误',
    ];

    // 自定义验证规则
    protected function checkName($value, $rule, $data = [])
    {
        return $rule == $value ? true : '名称错误';
    }

    //验证场景 validate(UserValidate::class)->scene('edit')->check($data)
    protected $scene = [
        'edit' => ['name', 'age', 'email'],
    ];
    // 单个edit 验证场景定义
//    public function sceneEdit()
//    {
//        return $this->only(['name','age'])
//            ->append('name', 'min:5')
//            ->remove('age', 'between')
//            ->append('age', 'require|max:100');
//    }
}