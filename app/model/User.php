<?php

namespace app\model;

use think\Model;
//Db::name('user')->where('id','>',10)->select();
//UserValidate::where('id','>',10)->select();
class User extends Model
{
    //protected $name = 'user';//模型名（相当于不带数据表前后缀的表名，默认为当前模型类名）
    //protected $table = 'think_user';//数据表名（默认自动获取）
    //protected $suffix= '';//数据表后缀（默认为空）
    // protected $pk= 'id';//主键名（默认为id）
    //// 设置json类型字段 protected $json = ['info'];
    // 开启自动写入时间戳字段 配置里'auto_timestamp' => true,模型里protected $autoWriteTimestamp = true;写入数据的时候，系统会自动写入create_time和update_time字段，而不需要定义修改器
    //// 定义时间戳字段名protected $createTime = 'create_at';protected $updateTime = 'update_at';，默认支持类型timestamp/datetime/int
    /// 软删除 use SoftDelete;protected $defaultSoftDelete = 0;
    //protected $query= '';//模型使用的查询类名称
    // 设置当前模型的数据库连接
    // protected $connection = 'db_config';//数据库连接（默认读取数据库配置）
    // protected $field= [];//模型允许写入的字段列表（数组）schema模型对应数据表字段及类型 type模型需要自动转换的字段及类型 strict是否严格区分字段大小写（默认为true） disuse数据表废弃字段（数组）
    // 设置完整数据表字段信息
    //protected $schema = [
    //   'id'          => 'int',
    //        'name'        => 'string',
    //        'status'      => 'int',
    //        'score'       => 'float',
    //        'create_time' => 'datetime',
    //        'update_time' => 'datetime',
    //    ];
        // 设置对某个字段自动转换类型
    //    protected $type = [
    //        'score'       => 'float',
    //    ];
    //
    //$user->data($data, true, ['name','score']);//模型赋值表示只设置data数组的name和score数据。第二个参数支持使用修改器
    //新增$user->allowField(['name', 'email'])->save();$user->saveAll($list);$user = UserValidate::create([ 'name'  =>  'thinkphp', 'email' =>  'thinkphp@qq.com'], ['name', 'email']);
    //强制更新$user->force()->save();UserValidate::update(['name' => 'thinkphp'], ['id' => 1]);
    //删除$user->delete();根据主键删除User::destroy(1);
    //查询User::find(1);$list = UserValidate::select([1,2,3]);UserValidate::where('status',1)->column('name');UserValidate::getByName('thinkphp');
    //范围查询User::scope('thinkphp')->find();
    //数据分批处理
    //UserValidate::chunk(100, function ($users) {
    //    foreach($users as $user){
    //        // 处理user模型对象
    //    }
    //});
    //使用游标查询
    //foreach(UserValidate::where('status', 1)->cursor() as $user){
    //echo $user->name;
    //}//
   //模型的序列化输出操作（$model->toArray()及toJson()）；
    //// 获取全部原始数据//dump($user->getData());

    // 设置返回数据集的对象名
    //protected $resultSetType = '\app\common\Collection';
    // 模型初始化
    protected static function init()
    {
        //TODO:初始化内容
    }
    //模型方法依赖注入
//    public function getUerLoginFieldAttr($value,$data) {
//        return $this->invoke(function(Request $request)  use($value,$data) {
//            return $data['name'] . $request->action();
//        });
//    }
//    //直接调用某个已经定义的模型方法
//    protected function bar($name, Request $request) {
//        // ...
//    }
//    //invoke方法第二个参数用于传入需要调用的（数组）参数
//    protected function invokeCall(){
//        return $this->invoke('bar',['think']);
//    }

    // 启动事务
    //Db::startTrans();
    //try {
    //Db::table('think_user')->find(1);
    //Db::table('think_user')->delete(1);
    //    // 提交事务
    //Db::commit();
    //} catch (\Exception $e) {
    //    // 回滚事务
    //    Db::rollback();
    //}
}