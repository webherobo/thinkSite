<?php

namespace app\controller;

use think\Exception;
use think\facade\Request;
use think\facade\Cache;

/**
 * API鉴权验证
 */
class Oauth
{

    /**
     * accessToken存储前缀
     *
     * @var string
     */
    public static $accessTokenPrefix = 'accessToken_';
    /**
     * 过期时间秒数
     *
     * @var int
     */
    public static $expires = 7200;

    /**
     * 认证授权 通过用户信息和路由
     * @param Request $request
     * @return \Exception|UnauthorizedException|mixed|Exception
     * @throws UnauthorizedException
     */
    final function authenticate()
    {
        return self::certification(self::getClient());
    }

    /**
     * 获取用户信息
     * @param Request $request
     * @return $this
     * @throws UnauthorizedException
     */
    public static function getClient()
    {
        //获取头部信息
        try {
            $authorization = Request::header('authentication');   //获取请求中的authentication字段，值形式为USERID asdsajh..这种形式
            $authorization = explode(" ", $authorization);        //explode分割，获取后面一窜base64加密数据
            $authorizationInfo = explode(":", base64_decode($authorization[1]));  //对base_64解密，获取到用:拼接的自字符串，然后分割，可获取appid、accesstoken、uid这三个参数
            $clientInfo['uid'] = $authorizationInfo[2];
            $clientInfo['appid'] = $authorizationInfo[0];
            $clientInfo['access_token'] = $authorizationInfo[1];
            return $clientInfo;
        } catch (Exception $e) {
            return self::returnMsg(401, 'Invalid authorization credentials', Request::header(''));
        }
    }

    /**
     * 获取用户信息后 验证权限
     * @return mixed
     */
    public static function certification($data = [])
    {
        $getCacheAccessToken = Cache::get(self::$accessTokenPrefix . $data['access_token']);  //获取缓存access_token
        if (!$getCacheAccessToken) {
            return self::returnMsg(401, 'fail', "access_token不存在或为空");
        }
        if ($getCacheAccessToken['client']['appid'] !== $data['appid']) {
            return self::returnMsg(401, 'fail', "appid错误");  //appid与缓存中的appid不匹配
        }
        return $data;
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public static function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }
        // 没找到匹配
        return false;
    }

    /**
     * 生成签名
     * _字符开头的变量不参与签名
     */
    public static function makeSign($data = [], $app_secret = '')
    {
        unset($data['version']);
        unset($data['sign']);
        return self::_getOrderMd5($data, $app_secret);
    }

    /**
     * 计算ORDER的MD5签名
     */
    private static function _getOrderMd5($params = [], $app_secret = '')
    {
        ksort($params);
        $params['key'] = $app_secret;
        return strtolower(md5(urldecode(http_build_query($params))));
    }

    /**
     * 返回成功
     */
    public static function returnMsg($code = 200, $message = '', $data = [], $header = [])
    {
        http_response_code($code);    //设置返回头部
        $return['code'] = (int)$code;
        $return['message'] = $message;
        $return['data'] = is_array($data) ? $data : ['info' => $data];
        // 发送头部信息
        foreach ($header as $name => $val) {
            if (is_null($val)) {
                header($name);
            } else {
                header($name . ':' . $val);
            }
        }
        exit(json_encode($return, JSON_UNESCAPED_UNICODE));
    }


}