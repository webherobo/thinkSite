<?php


namespace app\controller;

use app\BaseController;

class ApiBase extends BaseController
{

    //统一返回格式
    public static function return($statusCode, $data = [])
    {
        return json($data,$statusCode);
    }
    //状态码转换
    public static function code($code=0){
        $codeArray=[
            //常用0=>200
            ['code'=>200,'message'=>"请求成功并且服务器创建了新的资源。"],
            ['code'=>400,'message'=>"服务器不理解请求的语法。"],
            ['code'=>500,'message'=>"（HTTP 版本不受支持） 服务器不支持请求中所用的 HTTP 协议版本。"],
            ['code'=>301,'message'=>"请求的网页已永久移动到新位置。服务器返回此响应（对 GET 或 HEAD 请求的响应）时，会自动将请求者转到新位置。您应使用此代码告诉 Googlebot 某个网页或网站已永久移动到新位置。"],
            ['code'=>404,'message'=>"服务器找不到请求的网页。"],
            ['code'=>409,'message'=>"服务器在完成请求时发生冲突。 服务器必须在响应中包含有关冲突的信息。"],
            //一般
            ['code'=>100,'message'=>"（继续） 请求者应当继续提出请求。 服务器返回此代码表示已收到请求的第一部分，正在等待其余部分。"],
            ['code'=>101,'message'=>"（切换协议） 请求者已要求服务器切换协议，服务器已确认并准备切换。"],

            ['code'=>201,'message'=>"请求成功并且服务器创建了新的资源。"],
            ['code'=>202,'message'=>"接受请求但没创建资源；"],
            ['code'=>203,'message'=>"返回另一资源的请求；"],
            ['code'=>204,'message'=>"服务器成功处理了请求，但没有返回任何内容；"],
            ['code'=>205,'message'=>"服务器成功处理了请求，但没有返回任何内容；"],
            ['code'=>206,'message'=>"处理部分请求；"],

            ['code'=>300,'message'=>"(多种选择）  针对请求，服务器可执行多种操作。 服务器可根据请求者 (user agent) 选择一项操作，或提供操作列表供请求者选择。"],
            ['code'=>302,'message'=>"（临时移动）  服务器目前从不同位置的网页响应请求，但请求者应继续使用原有位置来进行以后的请求。"],
            ['code'=>303,'message'=>"（查看其他位置） 请求者应当对不同的位置使用单独的 GET 请求来检索响应时，服务器返回此代码。"],
            ['code'=>304,'message'=>"（未修改） 自从上次请求后，请求的网页未修改过。 服务器返回此响应时，不会返回网页内容。"],
            ['code'=>305,'message'=>"（使用代理） 请求者只能使用代理访问请求的网页。 如果服务器返回此响应，还表示请求者应使用代理。"],
            ['code'=>307,'message'=>"（临时重定向）  服务器目前从不同位置的网页响应请求，但请求者应继续使用原有位置来进行以后的请求。"],


            ['code'=>403,'message'=>"请求要求身份验证。 对于需要登录的网页，服务器可能返回此响应。"],
            ['code'=>403,'message'=>"服务器拒绝请求。"],
            ['code'=>405,'message'=>"禁用请求中指定的方法。"],
            ['code'=>406,'message'=>"无法使用请求的内容特性响应请求的网页。"],
            ['code'=>407,'message'=>"此状态代码与 401类似，但指定请求者应当授权使用代理。"],
            ['code'=>408,'message'=>"服务器等候请求时发生超时。"],

            ['code'=>410,'message'=>"如果请求的资源已永久删除，服务器就会返回此响应。"],
            ['code'=>411,'message'=>"服务器不接受不含有效内容长度标头字段的请求。"],
            ['code'=>412,'message'=>"服务器未满足请求者在请求中设置的其中一个前提条件。"],
            ['code'=>413,'message'=>"服务器无法处理请求，因为请求实体过大，超出服务器的处理能力。"],
            ['code'=>414,'message'=>"请求的 URI（通常为网址）过长，服务器无法处理。"],
            ['code'=>415,'message'=>"请求的格式不受请求页面的支持。"],
            ['code'=>416,'message'=>"如果页面无法提供请求的范围，则服务器会返回此状态代码。"],
            ['code'=>417,'message'=>"服务器未满足”期望”请求标头字段的要求。"],

            ['code'=>501,'message'=>"（尚未实施） 服务器不具备完成请求的功能。 例如，服务器无法识别请求方法时可能会返回此代码。"],
            ['code'=>502,'message'=>"（错误网关） 服务器作为网关或代理，从上游服务器收到无效响应。"],
            ['code'=>503,'message'=>"（服务不可用） 服务器目前无法使用（由于超载或停机维护）。 通常，这只是暂时状态。"],
            ['code'=>504,'message'=>"（网关超时）  服务器作为网关或代理，但是没有及时从上游服务器收到请求。"],
            ['code'=>505,'message'=>"（HTTP 版本不受支持） 服务器不支持请求中所用的 HTTP 协议版本。"],

        ];
        return in_array($code,$codeArray)?$codeArray[$code]:$codeArray[0];
    }
}