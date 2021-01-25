<?php
namespace PhpRest\Swagger;

use Symfony\Component\HttpFoundation\Response;
use PhpRest\Application;
use PhpRest\Entity\EntityBuilder;
use PhpRest\Controller\ControllerBuilder;
use PhpRest\Exception\BadCodeException;

class SwaggerHandler
{
    /**
     * 把所有接口注册成一个swagger
     * 
     * @param string $route
     * @param callable $callback
     */
    public static function register($route, $callback = null) 
    {
        Application::getInstance()->addRoute('GET', $route, function ($request) use($callback){
            $swaggerHandler = Application::getInstance()->get(SwaggerHandler::class);
            $swaggerHandler->build();
            if ($callback) {
                $callback($swaggerHandler->swagger);
            }
            $response = Application::getInstance()->make(Response::class);
            $response->setContent($swaggerHandler->toJson());
            return $response;
        });
    }

    /**
     * swagger分组
     * 
     * $group:
     * [
     *    'sys' => 'App\Controller\Sys',
     *    'bll' => 'App\Controller\Bll
     * ]
     * 
     * @param array $group
     * @param callable $callback
     */
    public static function registerGroup($group, $callback = null) 
    {        
        foreach($group as $key => $namesapce) {
            Application::getInstance()->addRoute('GET', "/swagger/{$key}.json", function ($request) use($callback, $namesapce, $key){
                $swaggerHandler = Application::getInstance()->get(SwaggerHandler::class);
                $swaggerHandler->build($namesapce);
                if ($callback) {
                    $callback($swaggerHandler->swagger, $key);
                }
                $response = Application::getInstance()->make(Response::class);
                $response->setContent($swaggerHandler->toJson());
                return $response;
            });
        }
    }

    public function build($namesapce = '') 
    {
        $this->swagger['swagger'] = '2.0';
        $this->swagger['info'] = $this->makeInfo();
        $this->swagger['host'] = $this->appHost;
        $this->swagger['schemes'] = $this->config['schemes'];
        $this->swagger['tags'] = [];
        $this->swagger['paths'] = [];

        foreach(Application::getInstance()->getControllers() as $classPath) {
            if ($namesapce !== '' && 0 !== strpos($classPath, $namesapce)) { continue; }

            $controller = Application::getInstance()->get(ControllerBuilder::class)->build($classPath);
            $hasRouteInController = false;
            foreach($controller->routes as $action => $route) {
                if ($route->swagger === true) {
                    $path = $this->makePath($controller, $route);
                    $method = strtolower($route->method);
                    $this->swagger['paths'][$route->uri][$method] = $path;
                    $hasRouteInController = true;
                }
            }
            if ($hasRouteInController === true) {
                $this->addTag($controller->summary, $controller->description);
            }
        }

        // swagger有些对象如果存在，必须是非空对象, 以下代码解决swagger解析报错问题
        if (count($this->swagger['paths']) === 0) {
            $this->swagger['paths'] = new \stdClass();
        }
    }

    // 基本信息
    private function makeInfo() 
    {
        return [
            'title'       => "{$this->appName} - {$this->appEnv}",
            'description' => '',
            'version'     => $this->config['version'],
            'termsOfService' => 'http://swagger.io/terms/',
            'contact' => [
                'name'  => "{$this->config['author']} <{$this->config['email']}>",
                'email' => $this->config['email']
            ],
            'license' => [
                'name' => 'Apache 2.0',
                'url'  => 'http://www.apache.org/licenses/LICENSE-2.0.html'
            ]
        ];
    }

    // 循环调用，创建API
    private function makePath($controller, $route) 
    {
        $modifyTime = date("Y-m-d H:i:s", $controller->modifyTimespan);

        $path = [];
        $path['tags'] = [$controller->summary];
        $path['summary'] = $route->summary;
        $path['description'] = "{$route->description} \r\n\r\n 缓存 @ {$modifyTime}";
        $path['parameters'] = [];
        // $path['operationId'] = '函数名'; // 这个貌似没什么用
        // $path['security'] = [1]; // 业务上接口是否需要验证在框架层面无法判断. 反正加了个这属性，接口后面会有一个小锁图标
        
        // 准备好一个body参数体，POST提交需要参数合并成一个对象
        $bodyParameter = [
            'in'     => 'body',
            'name'   => 'request',
            'schema' => ['type' => 'object', 'properties' => []]
        ];

        foreach($route->requestHandler->paramMetas as $pName => $param) {            
            $in = $this->sourceCast($param->source);
            if ($in === 'body') {
                $schema = [];
                if ($param->type[0] === 'Entity[]') {
                    $schema['type'] = 'array';
                    $schema['items'] = $this->makeDefinition($param->type[1]);
                }
                elseif ($param->type[0] === 'Entity') {
                    $schema = $this->makeDefinition($param->type[1]);
                }
                else {
                    $schema['description'] = $param->description . ($param->validation? " [{$param->validation}]" : '');
                    $schema['type'] = $this->typeCast($param->type[0]);
                    if ($schema['type'] === 'array') {
                        $subType = $this->typeCast(substr($param->type[0], 0, -2));
                        $default = $this->defaultCast($subType, null, $param->validation);
                        $schema['items'] = ['type' => $subType, 'default' => $default];
                    } else {
                        $schema['default'] = $this->defaultCast($schema['type'], $param->default, $param->validation);
                    }
                }
                $bodyParameter['schema']['properties'][$pName] = $schema;
            }
            elseif ($in === 'files') {
                $path['consumes'] = ['multipart/form-data'];
                $path['parameters'][] = [
                    'name' => $pName,
                    'in'   => 'formData',
                    'type' => 'file',
                    'description' => $param->description,
                    'required' => !$param->isOptional
                ];
            } 
            else {
                $parameter = [
                    'name' => $pName,
                    'in'   => $in,
                    'type' => $this->typeCast($param->type[0]),
                    'description' => $param->description . ($param->validation? " [{$param->validation}]" : '')
                ];
                if ($parameter['type'] === 'Entity') {
                    $parameter['type'] = 'string'; // GET 方式提交实体类数据，swagger没法处理
                }
                if ($parameter['type'] === 'array') {
                    $subType = $this->typeCast(substr($param->type[0], 0, -2));
                    $default = $this->defaultCast($subType);
                    $parameter['items'] = ['type' => $subType, 'default' => $default];
                }
                $parameter['required'] = ! $param->isOptional;
                if ($param->default !== null) {
                    $parameter['default'] = $param->default;
                }
                $path['parameters'][] = $parameter;
            }
        }
        
        if (count($bodyParameter['schema']['properties']) > 0) {
            $path['parameters'][] = $bodyParameter;
        }

        $path['responses']['200'] = $this->makeResponse($controller, $route);
        
        return $path;
    }

    // 生成 Response 描述
    private function makeResponse($controller, $route) 
    {
        $template = ''; // 是否指定了模版
        if (strpos($route->return[1], '#template=') !== false) {
            $ary = explode('#template=', $route->return[1]);
            $route->return[1] = trim($ary[0]);
            $template = trim($ary[1]);
        }

        $returnType = $route->return[0];
        $isArray = substr($returnType, -2) === '[]';
        if ($isArray) $returnType = substr($returnType, 0, -2);

        $returnSchema = null;
        
        if ($returnType === 'void') {
            $returnSchema = $this->makeDefaultResponseDefinition();
            return ['description' => '成功返回', 'schema' => $returnSchema];
        } 
        
        if (strpos($returnType, '\\') !== false || preg_match("/^[A-Z]{1}$/", $returnType[0])) {
            // 返回实体类
            $entityClassPath = $returnType;
            if (strpos($entityClassPath, '\\') === false) {
                $entityClassPath = \PhpRest\Utils\ReflectionHelper::resolveFromReflector($controller->classPath, $entityClassPath);
            }
            if (!class_exists($entityClassPath)) {
                throw new BadCodeException("{$controller->classPath}::{$route->summary} @return 指定的实体类 {$entityClassPath} 不存在");
            }
            if ($isArray === true) {
                $returnSchema['type'] = 'array';
                $returnSchema['items'] = $this->makeDefinition($entityClassPath);
            } else {
                $returnSchema = $this->makeDefinition($entityClassPath);
            }
        }
        elseif ($returnType === 'object') {
            $obj = json_decode($route->return[1]);
            if ($obj === null) {
                throw new BadCodeException("{$controller->classPath}::{$route->summary} @return 描述无法格式化为JSON");
            }
            $schema['type'] = 'object';
            $schema['properties'] = $this->makeObjectResponseSchema($obj);
            if ($isArray === true) {
                $returnSchema['type'] = 'array';
                $returnSchema['items'] = $schema;
            } else {
                $returnSchema = $schema;
            }
        } elseif (in_array($returnType, ['int', 'float', 'string'])) {
            $schema['type'] = $this->typeCast($returnType);
            $schema['default'] = $this->defaultCast($schema['type']);
            if ($isArray === true) {
                $returnSchema['type'] = 'array';
                $returnSchema['items'] = $schema;
            } else {
                $returnSchema = $schema;
            }
        }

        // 指定了模版
        if ($template !== '' && isset($this->config['template'][$template])) {
            $temp = $this->config['template'][$template];
            $temp = json_decode(json_encode($temp));
            $temp = $this->makeObjectResponseSchema($temp, $returnSchema);
            $returnSchema = ['type' => 'object', 'properties' => $temp];
        }

        if ($returnSchema === null) {
            // 没有写 @return 或 @return 值不规范, 默认返回 string
            $returnSchema = ['type' =>'string','default' => 'string'];
        }

        return ['description' => '成功返回', 'schema' => $returnSchema];
    }

    // 创建默认的成功返回引用
    private function makeDefaultResponseDefinition() 
    {
        if (isset($this->swagger['definitions']['Response200']) === false) {
            if (isset($this->config['template']['default'])) {
                $temp = $this->config['template']['default'];
                $temp = json_decode(json_encode($temp));
                $default = $this->makeObjectResponseSchema($temp);
                $this->swagger['definitions']['Response200'] = ['type' => 'object', 'properties' => $default];
            } else {
                $this->swagger['definitions']['Response200'] = ['type' => 'string',  'default' => 'string'];
            }
        }
        return ['$ref' => "#/definitions/Response200"];
    }

    // @return object 后面的json 转换成 ResponseSchema
    /**
     * @param object $obj stdClass对象
     * @param array $refSchema 填充值(如果$obj是个模板的话)
     */
    private function makeObjectResponseSchema($obj, $refSchema = null) 
    {
        $schema = [];
        foreach($obj as $k => $v) {
            if (is_array($v)) {
                $schema[$k]['type'] = 'array';
                if (is_object($v[0])) {
                    $schema[$k]['items']['type'] = 'object';
                    $schema[$k]['items']['properties'] = $this->makeObjectResponseSchema($v[0], $refSchema);
                } else {
                    $schema[$k]['items'] = [
                        'type' => $this->valueCast($v[0]),
                        'default' => $v[0]
                    ];
                }
            } elseif (is_object($v)) {
                $schema[$k] = [
                    'type' => 'object',
                    'properties' => $this->makeObjectResponseSchema($v, $refSchema)
                ];
            } else {
                if ($v === '#schema') { // $obj是个模板
                    if ($refSchema !== null) {
                        $schema[$k] = $refSchema;
                    }
                } else {
                    $schema[$k] = ['type' => $this->valueCast($v),'default' => $v];
                }
            }
        }
        return $schema;
    }

    public function toJson() 
    {
        return json_encode($this->swagger, JSON_UNESCAPED_UNICODE);
    }

    
    // 多个controller 可以写同名tag, 则会排版到同一个tag下
    private function addTag($name, $desc) 
    {
        foreach($this->swagger['tags'] as $tag) {
            if ($tag['name'] === $name) { return; }
        }
        $this->swagger['tags'][] = ['name' => $name, 'description' => $desc];
    }

    // 如果是实体类， 创建引用
    private function makeDefinition($entityClassPath) 
    {
        $defName = str_replace('\\', '', $entityClassPath);
        if (isset($this->swagger['definitions'][$defName]) === false) {
            $entityObj = ['type' => 'object', 'properties' => []];
            $entity = Application::getInstance()->get(EntityBuilder::class)->build($entityClassPath);
            foreach($entity->properties as $property) {
                if ($property->type[0] === 'Entity[]') {
                    $itemObj['type'] = 'array';
                    $itemObj['items'] = $this->makeDefinition($property->type[1]);
                    $entityObj['properties'][$property->name] = $itemObj;
                }
                elseif ($property->type[0] === 'Entity') {
                    $entityObj['properties'][$property->name] = $this->makeDefinition($property->type[1]);
                }
                else {
                    $type = $this->typeCast($property->type[0]);
                    if ($type === 'array') {
                        $type = $this->typeCast(substr($property->type[0], 0, -2));
                        $default = $this->defaultCast($type);
                        $itemObj['type'] = 'array';
                        $itemObj['items'] = ['type' => $type, 'default' => $default, 'description' => $property->summary];
                        $entityObj['properties'][$property->name] = $itemObj;
                    } else {
                        $default = $this->defaultCast($type, null, $property->validation);
                        $entityObj['properties'][$property->name] = ['type' => $type, 'default' => $default, 'description' => $property->summary];
                    }
                }
            }
            $this->swagger['definitions'][$defName] = $entityObj;
        }        
        return ['$ref' => "#/definitions/{$defName}"];
    }

    // 把框架中request中的取值位置， 转换成swagger支持的类型
    private function sourceCast($source) 
    {
        $ary = explode('.', $source);
        if ($ary[0] === 'request') return 'body';
        if ($ary[0] === 'query') return 'query';
        if ($ary[0] === 'attributes') return 'path';
        if ($ary[0] === 'headers') return 'header';
        return $ary[0];
    }

    // 把框架的参数类型，转成swagger支持的类型
    private function typeCast($type) 
    {
        if(substr($type, -2) === '[]')  return 'array';
        if(in_array($type, ['int', 'integer'])) return 'integer';
        if(in_array($type, ['float', 'numeric'])) return 'number';
        if($type === 'bool') return 'boolean';
        if($type === 'Entity') return 'Entity';
        return 'string';
    }

    // 各种类型的默认显示值
    private function defaultCast($type, $default = null, $validation = null) 
    {
        if ($default !== null) { return $default; }
        if ($type === 'string' && $validation) return "string [{$validation}]";
        if ($type === 'integer') return 1; 
        if ($type === 'number')  return 1.1; 
        if ($type === 'boolean') return true; 
        return "string";
    }

    // 识别值是什么类型
    private function valueCast($val) {
        if(is_integer($val)) return 'integer';
        if(is_float($val)) return 'number';
        if(is_bool($val)) return 'boolen';
        return 'string';
    }

    /**
     * @Inject("App.name")
     * @var string
     */
    private $appName;

    /**
     * @Inject("App.host")
     * @var string
     */
    private $appHost;

    /**
     * @Inject("App.env")
     * @var string
     */
    private $appEnv;

    /**
     * @Inject("swagger")
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $swagger = [];
}