**Yii2框架RESTful API教程**

一、目录结构
实现一个简单地RESTful API只需用到三个文件。目录如下：


`frontend
    ├─ config
    │   └ main.php
    ├─ controllers
    │   └ BookController.php
    └─ models
        └ Book.php`

二、配置URL规则
1.修改服务器的rewrite规则，将所有URL全部指向index.php上，使其支持 /books/1 格式。
如果是Apache服务器，在frontend/web/ 目录中新建.htaccess文件。文件内容如下：


`RewriteEngine on
# If a directory or a file exists, use the request directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
# Otherwise forward the request to index.php
    RewriteRule . index.php`

如果是Nginx服务器，修改nginx/conf/nginx.conf，在当前"server{}"的"location / {}"中添加下面红色标记内容：

`location / {
　　try_files $uri $uri/ /index.php$is_args$args;
}`

2.修改frontend/config/main.php文件，为book控制器增加一个 URL 规则。这样，就能通过美化的 URL 和有意义的 http 动词进行访问和操作数据。配置如下：


`'components' => [
    'urlManager' => [
        'enablePrettyUrl' => true,
        'enableStrictParsing' => true,
        'showScriptName' => false,
        'rules' => [
            ['class' => 'yii\rest\UrlRule', 'controller' => 'book'],
        ],
    ],
],`


三、创建一个model
1.在数据库中创建一张book表。book表的内容如下：


``
-- ----------------------------
-- Table structure for book
-- ----------------------------
DROP TABLE IF EXISTS `book`;
CREATE TABLE `book` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(50) NOT NULL DEFAULT '',
  `num` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of book
-- ----------------------------
INSERT INTO `book` VALUES ('1', 'toefl', '10');
INSERT INTO `book` VALUES ('2', 'ielts', '20');
INSERT INTO `book` VALUES ('3', 'sat', '30');
INSERT INTO `book` VALUES ('4', 'gre', '40');
INSERT INTO `book` VALUES ('5', 'gmat', '50');
复制代码
复制代码
2.在frontend/models/目录中新建Book.php。文件内容如下：

复制代码
复制代码
namespace frontend\models;

use yii\db\ActiveRecord;

class Book extends ActiveRecord
{
    public static function tableName()
    {
        return 'book';
    }
}``


四、创建一个控制器
在frontend/controllers/目录中新建BookController.php。控制器类扩展自 yii\rest\ActiveController。通过指定 yii\rest\ActiveController::modelClass 作为 frontend\models\Book， 控制器就能知道使用哪个模型去获取和处理数据。文件内容如下：



`namespace frontend\controllers;

use yii\rest\ActiveController;

class BookController extends ActiveController
{
    public $modelClass = 'frontend\models\Book';
}`

五、测试
到这里，我们就已经完成了创建用于访问用户数据 的 RESTful 风格的 API。创建的 API 包括：


GET /books: 列出所有的书
HEAD /books: 显示书的列表的概要信息
POST /books: 新增1本书
GET /books/1: 返回 书ID=1的详细信息
HEAD /books/1: 显示 书ID=1的概述信息
PATCH /books/1 and PUT /books/1: 更新书ID=1的信息
DELETE /books/1: 删除书ID=1的信息
OPTIONS /books: 显示关于末端 /books 支持的动词
OPTIONS /books/1: 显示有关末端 /books/1 支持的动词

可以通过Web浏览器中输入 URL http://{frontend的域名}/books 来访问API，或者使用一些浏览器插件来发送特定的 headers 请求，比如Firefox的RestClient、Chrome的Advanced Rest Client、postman等。

六、说明
1.Yii 将在末端使用的控制器的名称自动变为复数。这是因为 yii\rest\UrlRule 能够为他们使用的末端全自动复数化控制器。可以通过设置yii\rest\UrlRule::pluralize为false来禁用此行为:

`'rules' => [
    ['class' => 'yii\rest\UrlRule', 'controller' => 'book', 'pluralize' => false],
],`
2.可以使用fields和expand参数指定哪些字段应该包含在结果内。例如：URL http://{frontend的域名}/books?fields=name,num 将只返回 name 和 num 字段。

今天接着来探究一下Yii2 RESTful的格式化响应，授权认证和速率限制三个部分

一、目录结构
先列出需要改动的文件。目录如下：


`web
 ├─ common
 │      └─ models 
 │              └ User.php
 └─ frontend
        ├─ config
        │   └ main.php
        └─ controllers
            └ BookController.php
`
二、格式化响应
Yii2 RESTful支持JSON和XML格式，如果想指定返回数据的格式，需要配置yii\filters\ContentNegotiator::formats属性。例如，要返回JSON格式，修改frontend/controllers/BookController.php，加入红色标记代码:


`namespace frontend\controllers;

use yii\rest\ActiveController;
use yii\web\Response;

class BookController extends ActiveController
{
    public $modelClass = 'frontend\models\Book';

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }
}`

返回XML格式：FORMAT_XML。formats属性的keys支持MIME类型，而values必须在yii\web\Response::formatters中支持被响应格式名称。

三、授权认证
RESTful APIs通常是无状态的，因此每个请求应附带某种授权凭证，即每个请求都发送一个access token来认证用户。

1.配置user应用组件（不是必要的，但是推荐配置）：
　　设置yii\web\User::enableSession属性为false（因为RESTful APIs为无状态的，当yii\web\User::enableSession为false，请求中的用户认证状态就不能通过session来保持）
　　设置yii\web\User::loginUrl属性为null（显示一个HTTP 403 错误而不是跳转到登录界面）
具体方法，修改frontend/config/main.php，加入红色标记代码：


`'components' => [
    ...
    'user' => [
        'identityClass' => 'common\models\User',
        'enableAutoLogin' => true,
        
        'enableSession' => false,
        'loginUrl' => null,
        
    ],
    ...
]`

2.在控制器类中配置authenticator行为来指定使用哪种认证方式，修改frontend/controllers/BookController.php，加入红色标记代码：


`namespace frontend\controllers;

use yii\rest\ActiveController;
use yii\web\Response;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

class BookController extends ActiveController
{
    public $modelClass = 'frontend\models\Book';

    public function behaviors() {
        $behaviors = parent::behaviors();
    
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                /*下面是三种验证access_token方式*/
                //1.HTTP 基本认证: access token 当作用户名发送，应用在access token可安全存在API使用端的场景，例如，API使用端是运行在一台服务器上的程序。
                //HttpBasicAuth::className(),
                //2.OAuth 2: 使用者从认证服务器上获取基于OAuth2协议的access token，然后通过 HTTP Bearer Tokens 发送到API 服务器。
                //HttpBearerAuth::className(),
                //3.请求参数: access token 当作API URL请求参数发送，这种方式应主要用于JSONP请求，因为它不能使用HTTP头来发送access token
                //http://localhost/user/index/index?access-token=123
                QueryParamAuth::className(),
            ],
        ];
        
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }
}`

3.创建一张user表

``
-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `password_hash` varchar(100) NOT NULL DEFAULT '' COMMENT '密码',
  `password_reset_token` varchar(50) NOT NULL DEFAULT '' COMMENT '密码token',
  `email` varchar(20) NOT NULL DEFAULT '' COMMENT '邮箱',
  `auth_key` varchar(50) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `access_token` varchar(50) NOT NULL DEFAULT '' COMMENT 'restful请求token',
  `allowance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'restful剩余的允许的请求数',
  `allowance_updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'restful请求的UNIX时间戳数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `access_token` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('1', 'admin', '$2y$13$1KWwchqGvxDeORDt5pRW.OJarf06PjNYxe2vEGVs7e5amD3wnEX.i', '', '', 'z3sM2KZvXdk6mNXXrz25D3JoZlGXoJMC', '10', '1478686493', '1478686493', '123', '4', '1478686493');
``

在common/models/User.php类中实现 yii\web\IdentityInterface::findIdentityByAccessToken()方法。修改common/models/User.php，加入红色标记代码：：


`public static function findIdentityByAccessToken($token, $type = null)
{
    //findIdentityByAccessToken()方法的实现是系统定义的
    //例如，一个简单的场景，当每个用户只有一个access token, 可存储access token 到user表的access_token列中， 方法可在User类中简单实现，如下所示：
    return static::findOne(['access_token' => $token]);
    //throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
}`

四、速率限制
为防止滥用，可以增加速率限制。例如，限制每个用户的API的使用是在60秒内最多10次的API调用，如果一个用户同一个时间段内太多的请求被接收，将返回响应状态代码 429 (这意味着过多的请求)。

1.Yii会自动使用yii\filters\RateLimiter为yii\rest\Controller配置一个行为过滤器来执行速率限制检查。如果速度超出限制，该速率限制器将抛出一个yii\web\TooManyRequestsHttpException。
修改frontend/controllers/BookController.php，加入红色标记代码：


`namespace frontend\controllers;

use yii\rest\ActiveController;
use yii\web\Response;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use yii\filters\RateLimiter;

class BookController extends ActiveController
{
    public $modelClass = 'frontend\models\Book';

    public function behaviors() {
        $behaviors = parent::behaviors();
        
        $behaviors['rateLimiter'] = [
            'class' => RateLimiter::className(),
            'enableRateLimitHeaders' => true,
        ];
        
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                /*下面是三种验证access_token方式*/
                //1.HTTP 基本认证: access token 当作用户名发送，应用在access token可安全存在API使用端的场景，例如，API使用端是运行在一台服务器上的程序。
                //HttpBasicAuth::className(),
                //2.OAuth 2: 使用者从认证服务器上获取基于OAuth2协议的access token，然后通过 HTTP Bearer Tokens 发送到API 服务器。
                //HttpBearerAuth::className(),
                //3.请求参数: access token 当作API URL请求参数发送，这种方式应主要用于JSONP请求，因为它不能使用HTTP头来发送access token
                //http://localhost/user/index/index?access-token=123
                QueryParamAuth::className(),
            ],
        ];
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }
}`

2.在user表中使用两列来记录容差和时间戳信息。为了提高性能，可以考虑使用缓存或NoSQL存储这些信息。
修改common/models/User.php，加入红色标记代码：


`namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

use yii\filters\RateLimitInterface;

class User extends ActiveRecord implements IdentityInterface, RateLimitInterface
{

    ....

    // 返回在单位时间内允许的请求的最大数目，例如，[10, 60] 表示在60秒内最多请求10次。
    public function getRateLimit($request, $action)
    {
        return [5, 10];
    }

    // 返回剩余的允许的请求数。
    public function loadAllowance($request, $action)
    {
        return [$this->allowance, $this->allowance_updated_at];
    }

    // 保存请求时的UNIX时间戳。
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save();
    }
    
    ....
    
    public static function findIdentityByAccessToken($token, $type = null)
    {
        //throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
        //findIdentityByAccessToken()方法的实现是系统定义的
        //例如，一个简单的场景，当每个用户只有一个access token, 可存储access token 到user表的access_token列中， 方法可在User类中简单实现，如下所示：
        return static::findOne(['access_token' => $token]);
    }
    
    ....
}`

 
最近看了一些关于RESTful的资料，自己动手也写了一个RESTful实例，以下是源码

目录详情：

restful/
    Request.php 数据操作类
    Response.php 输出类
    index.php 入口文件
    .htaccess 重写url
Request.php ：包含一个Request类，即数据操作类。接收到URL的数据后，根据请求URL的方式（GET|POST|PUT|PATCH|DELETE）对数据进行相应的增删改查操作，并返回操作后的结果：


`<?php

/**
 * 数据操作类
 */
class Request
{
    //允许的请求方式
    private static $method_type = array('get', 'post', 'put', 'patch', 'delete');
    //测试数据
    private static $test_class = array(
        1 => array('name' => '托福班', 'count' => 18),
        2 => array('name' => '雅思班', 'count' => 20),
    );

    public static function getRequest()
    {
        //请求方式
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if (in_array($method, self::$method_type)) {
            //调用请求方式对应的方法
            $data_name = $method . 'Data';
            return self::$data_name($_REQUEST);
        }
        return false;
    }

    //GET 获取信息
    private static function getData($request_data)
    {
        $class_id = (int)$request_data['class'];
        //GET /class/ID：获取某个指定班的信息
        if ($class_id > 0) {
            return self::$test_class[$class_id];
        } else {//GET /class：列出所有班级
            return self::$test_class;
        }
    }

    //POST /class：新建一个班
    private static function postData($request_data)
    {
        if (!empty($request_data['name'])) {
            $data['name'] = $request_data['name'];
            $data['count'] = (int)$request_data['count'];
            self::$test_class[] = $data;
            return self::$test_class;//返回新生成的资源对象
        } else {
            return false;
        }
    }

    //PUT /class/ID：更新某个指定班的信息（全部信息）
    private static function putData($request_data)
    {
        $class_id = (int)$request_data['class'];
        if ($class_id == 0) {
            return false;
        }
        $data = array();
        if (!empty($request_data['name']) && isset($request_data['count'])) {
            $data['name'] = $request_data['name'];
            $data['count'] = (int)$request_data['count'];
            self::$test_class[$class_id] = $data;
            return self::$test_class;
        } else {
            return false;
        }
    }

    //PATCH /class/ID：更新某个指定班的信息（部分信息）
    private static function patchData($request_data)
    {
        $class_id = (int)$request_data['class'];
        if ($class_id == 0) {
            return false;
        }
        if (!empty($request_data['name'])) {
            self::$test_class[$class_id]['name'] = $request_data['name'];
        }
        if (isset($request_data['count'])) {
            self::$test_class[$class_id]['count'] = (int)$request_data['count'];
        }
        return self::$test_class;
    }

    //DELETE /class/ID：删除某个班
    private static function deleteData($request_data)
    {
        $class_id = (int)$request_data['class'];
        if ($class_id == 0) {
            return false;
        }
        unset(self::$test_class[$class_id]);
        return self::$test_class;
    }
}`

Response.php ：包含一个Request类，即输出类。根据接收到的Content-Type，将Request类返回的数组拼接成对应的格式，加上header后输出



`<?php
/**
 * 输出类
 */
class Response
{
    const HTTP_VERSION = "HTTP/1.1";

    //返回结果
    public static function sendResponse($data)
    {
        //获取数据
        if ($data) {
            $code = 200;
            $message = 'OK';
        } else {
            $code = 404;
            $data = array('error' => 'Not Found');
            $message = 'Not Found';
        }

        //输出结果
        header(self::HTTP_VERSION . " " . $code . " " . $message);
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : $_SERVER['HTTP_ACCEPT'];
        if (strpos($content_type, 'application/json') !== false) {
            header("Content-Type: application/json");
            echo self::encodeJson($data);
        } else if (strpos($content_type, 'application/xml') !== false) {
            header("Content-Type: application/xml");
            echo self::encodeXml($data);
        } else {
            header("Content-Type: text/html");
            echo self::encodeHtml($data);
        }
    }

    //json格式
    private static function encodeJson($responseData)
    {
        return json_encode($responseData);
    }

    //xml格式
    private static function encodeXml($responseData)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><rest></rest>');
        foreach ($responseData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $xml->addChild($k, $v);
                }
            } else {
                $xml->addChild($key, $value);
            }
        }
        return $xml->asXML();
    }

    //html格式
    private static function encodeHtml($responseData)
    {
        $html = "<table border='1'>";
        foreach ($responseData as $key => $value) {
            $html .= "<tr>";
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $html .= "<td>" . $k . "</td><td>" . $v . "</td>";
                }
            } else {
                $html .= "<td>" . $key . "</td><td>" . $value . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</table>";
        return $html;
    }
}`

index.php ：入口文件，调用Request类取得数据后交给Response处理，最后返回结果



`<?php
//数据操作类
require('Request.php');
//输出类
require('Response.php');
//获取数据
$data = Request::getRequest();
//输出结果
Response::sendResponse($data);`
