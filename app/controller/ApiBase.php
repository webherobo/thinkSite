<?php


namespace app\controller;

use app\BaseController;
/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="官网api文档",
 *         description="官网api文档",
 *         termsOfService="http://swagger.io/terms/",
 *         @OA\Contact(
 *          name="webherobo 开发支持",
 *          email="webherobo@gmail.com"
 *         ),
 *         @OA\License(
 *             name="Apache 2.0",
 *             url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *         )
 *     ),
 *     @OA\Server(
 *         description="官网OpenApi",
 *         url="http://www.ansdistributor.com/api",
 *         @OA\ServerVariable(
 *          serverVariable="schema",
 *          enum={"https", "http"},
 *          default="https"
 *        )
 *     ),
 *     @OA\Server(
 *         description="官网OpenApi测试",
 *         url="http://www.ansdistributor.com/testapi",
 *         @OA\ServerVariable(
 *          serverVariable="schema",
 *          enum={"https", "http"},
 *          default="https"
 *        )
 *     ),
 *     @OA\ExternalDocumentation(
 *         description="Find out more about Swagger",
 *         url="http://swagger.io"
 *     )
 * )
 *
 * 参数的来源，必填，取值范围：query、header、path、formData、body
 * 参数类型，取值范围：string、number、integer、boolean、array、file
 *  text/plain; charset=utf-8|application/json|multipart/form-data
 */
/**
 * @OA\Schema(
 *      schema="UploadFileModel",
 *      @OA\Property(
 *          property="file_name",
 *          type="string",
 *          description="文件名，不包含路径"
 *      ),
 *      @OA\Property(
 *          property="file_path",
 *          type="string",
 *          description="文件路径"
 *      ),
 *      @OA\Property(
 *          property="file_url",
 *          type="string",
 *          description="URL链接，用于展示"
 *      ),
 *      @OA\Property(
 *          property="file_size",
 *          type="string",
 *          description="文件大小，单位B"
 *      ),
 *      @OA\Property(
 *          property="extension",
 *          type="string",
 *          description="文件扩展名"
 *      )
 * )
 */
class ApiBase extends BaseController
{

    //统一返回格式
    public static function return($data = [])
    {
        return json($data,self::code($data["code"])["code"]);
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
    /**
     *
     * @OA\SecurityScheme(type="apiKey",securityScheme="apikey",name="apikey")
     * @OA\Post(
     *     tags={"教育站点api"},
     *     path="/website/test/index",
     *     summary="测试接口",
     *     @OA\Parameter(name="firstname",in="query",@OA\Schema(type="string"),description="名字",example="hero"),
     *     @OA\Parameter(name="lastname",in="query",@OA\Schema(type="string"),description="姓氏",example="cheng"),
     *     security={{"apikey"={}}},
     *     @OA\RequestBody(required=true,description="body",content={
     *     @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *     required={"upload_file"},
     *     @OA\Property(
     *        property="username",
     *        type="string",
     *        default="webherobo",
     *        example="hero"
     *     ),
     *     @OA\Property(
     *        property="password",
     *        type="string",
     *        default="admin123"
     *     ),
     *     @OA\Property(
     *        property="sex",
     *        type="integer",
     *        example=0
     *     ),
     *      @OA\Property(
     *        property="upload_file",
     *        type="file",
     *        description="上传文件"
     *     ),
     * )),
     *    @OA\MediaType(mediaType="application/json",
     *     @OA\Schema(
     *     required={"upload_file"},
     *     @OA\Property(
     *        property="username",
     *        type="string",
     *        default="webherobo",
     *        example="hero"
     *     ),
     *     @OA\Property(
     *        property="password",
     *        type="string",
     *        default="admin123"
     *     ),
     *     @OA\Property(
     *        property="sex",
     *        type="integer",
     *        example=0
     *     ),
     * )),
     * }
     * ),
     *     @OA\Response(
     *     response=200,
     *     description="ok",
     *     @OA\JsonContent(ref="#/components/schemas/UploadFileModel")
     * )
     * )
     */
    public function testapi(){

    }
    public function index(){
        $path = app()->getRootPath().'/app'; //你想要哪个文件夹下面的注释生成对应的API文档
        $openapi = \OpenApi\scan($path);
        //header('Content-Type: application/x-yaml');
        // header('Content-Type: application/json');
        // echo $swagger;
        $swagger_json_path = app()->getRootPath().'/public/static/swagger-ui/swagger.json';
        $res = file_put_contents($swagger_json_path, $openapi->toJson());
        if ($res == true) {
            $this->redirect('/static/swagger-ui/dist/index.html');
        }
    }
}