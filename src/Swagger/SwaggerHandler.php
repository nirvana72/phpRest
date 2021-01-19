<?php
namespace PhpRest\Swagger;

use Symfony\Component\HttpFoundation\Response;
use PhpRest\Entity\EntityBuilder;

class SwaggerHandler
{
    public static function register($app, $namesapces, $callback) 
    {
        foreach($namesapces as $key => $namesapce) {
            $app->addRoute('GET', "/swagger/{$key}.json", function ($app, $request) use($callback, $namesapce, $key){
                $swaggerHandler = $app->get(SwaggerHandler::class);
                $swaggerHandler->build($app, $namesapce, $key);
                $callback($swaggerHandler->swagger, $key);
                return new Response($swaggerHandler->toJson());
            });
        }
    }   

    public function build($app, $namesapce, $key) 
    {
        $this->swagger = [];
        $this->swagger['swagger'] = '2.0';
        $this->swagger['info'] = $this->makeInfo($key);
        $this->swagger['host'] = $this->appHost;
        $this->swagger['schemes'] = $this->config['schemes'];
        $this->swagger['tags'] = [];
        $this->swagger['schemes'] = ['https', 'http'];
        $this->swagger['paths'] = [];
        
        $this->makeDefaultResponseDefinition();

        foreach($app->controllers as $classPath) {
            if (0 !== strpos($classPath, $namesapce)) { continue; }

            $controller = $app->controllerBuilder->build($classPath);

            $this->addTag($controller->summary, $controller->description);
            
            foreach($controller->routes as $action => $route) {
                $path = $this->makePath($app, $controller, $route);
                $method = strtolower($route->method);
                $this->swagger['paths'][$route->uri][$method] = $path;
            }
        }

        // swagger有些对象如果存在，必须是非空对象, 以下代码解决swagger解析报错问题
        if (count($this->swagger['paths']) === 0) {
            $this->swagger['paths'] = new \stdClass();
        }
        if (count($this->swagger['definitions']) === 0) {
            $this->swagger['definitions'] = new \stdClass();
        }
    }

    // 基本信息
    private function makeInfo($key) 
    {
        $info = [];
        $info['title'] = "{$this->appName} - {$key} - {$this->appEnv}";
        $info['description'] = '这个不应该配置，应该根据group自己定义';
        $info['version'] = $this->config['version'];
        $info['termsOfService'] = 'http://swagger.io/terms/';
        $info['contact'] = [];
        $info['contact']['name'] = "{$this->config['author']} <{$this->config['email']}>";
        $info['contact']['email'] = $this->config['email'];
        $info['license'] = [];
        $info['license']['name'] = 'Apache 2.0';
        $info['license']['url'] = 'http://www.apache.org/licenses/LICENSE-2.0.html';
        return $info;
    }

    // 创建默认的成功返回引用
    private function makeDefaultResponseDefinition() 
    {
        $this->swagger['definitions'] = [];
        $schema = [
            'type' => 'object',
            'properties' => [
                'ret' => ['type' => 'integer', 'default' => 1],
                'msg' => ['type' => 'string',  'default' => 'success']
            ]
        ];
        $this->swagger['definitions']['Response200'] = $schema;
    }

    // 循环调用，创建API
    private function makePath($app, $controller, $route) 
    {
        $modifyTime = date("Y-m-d H:i:s", $controller->modifyTimespan);

        $path = [];
        $path['tags'] = [$controller->summary];
        $path['summary'] = $route->summary;
        $path['description'] = "{$route->description} \r\n\r\n 缓存 @ {$modifyTime}";
        $path['parameters'] = [];
        // $path['operationId'] = '函数名'; // 这个貌似没什么用
        // $path['security'] = [1]; // 业务上接口是否需要验证，框架层面无法判断. 反正加了个这属性，接口后面会有一个小锁图标
        
        // 准备好一个body参数体，也许用不到
        $bodyParameter = [];
        $bodyParameter['in'] = 'body';
        $bodyParameter['name'] = 'request';
        $bodyParameter['schema'] = [];
        $bodyParameter['schema']['type'] = 'object';
        $bodyParameter['schema']['properties'] = [];

        foreach($route->requestHandler->paramMetas as $pName => $param) {
            
            $in = $this->sourceCast($param->source);
            if ($in === 'body') {
                // 所有body需要参数合并成一个对象
                $schema = [];
                if ($param->type[0] === 'Entity[]') {
                    $schema['type'] = 'array';
                    $schema['items'] = $this->makeDefinition($app, $param->type[1]);
                }
                elseif ($param->type[0] === 'Entity') {
                    $schema = $this->makeDefinition($app, $param->type[1]);
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
                $parameter = [];
                $parameter['name'] = $pName;
                $parameter['in'] = 'formData';
                $parameter['type'] = 'file';
                $parameter['description'] = $param->description;
                $parameter['required'] = ! $param->isOptional;
                $path['parameters'][] = $parameter;
            } 
            else {
                $parameter = [];
                $parameter['name'] = $pName;
                $parameter['in'] = $in;
                $parameter['type'] = $this->typeCast($param->type[0]);
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
                $parameter['description'] = $param->description . ($param->validation? " [{$param->validation}]" : '');
                $path['parameters'][] = $parameter;
            }
        }
        
        if (count($bodyParameter['schema']['properties']) > 0) {
            $path['parameters'][] = $bodyParameter;
        }

        $path['responses'] = $this->makeResponse();
        
        return $path;
    }

    private function makeResponse() 
    {
        $responses = [];
        $responses['200']['description'] = '成功返回';
        $responses['200']['schema'] = ['$ref' => "#/definitions/Response200"];
        return $responses;
    }

    public function toJson() 
    {
        return json_encode($this->swagger, true);
    }

    
    // 多个controller 可以写同名tag, 则会排版到同一个tag下
    private function addTag($name, $desc) 
    {
        foreach($this->swagger['tags'] as $tag) {
            if ($tag['name'] === $name) {
                return;
            }
        }
        $this->swagger['tags'][] = ['name' => $name, 'description' => $desc];
    }

    // 如果是实体类， 创建引用
    private function makeDefinition($app, $entityClassPath) 
    {
        $defName = str_replace('\\', '', $entityClassPath);        
        foreach ($this->swagger['definitions'] as $key => $_) {
            if ($key === $entityClassPath) {
                return ['$ref' => "#/definitions/{$defName}"];
            }
        }

        $entityObj['type'] = 'object';
        $entityObj['properties'] = [];

        $entity = $app->get(EntityBuilder::class)->build($entityClassPath);
        foreach($entity->properties as $property) {
            if ($property->type[0] === 'Entity[]') {
                $itemObj['type'] = 'array';
                $itemObj['items'] = $this->makeDefinition($app, $property->type[1]);
                $entityObj['properties'][$property->name] = $itemObj;
            }
            elseif ($property->type[0] === 'Entity') {
                $entityObj['properties'][$property->name] = $this->makeDefinition($app, $property->type[1]);
            }
            else {
                $type = $this->typeCast($property->type[0]);
                $default = null;
                if ($type === 'array') {
                    $type = $this->typeCast(substr($property->type[0], 0, -2));
                    $default = $this->defaultCast($type);
                    $itemObj['type'] = 'array';
                    $itemObj['items'] = ['type' => $type, 'default' => $default];
                    $entityObj['properties'][$property->name] = $itemObj;
                } else {
                    $default = $this->defaultCast($type, null, $property->validation);
                    $entityObj['properties'][$property->name] = ['type' => $type, 'default' => $default ];
                }
            }
        }
        $this->swagger['definitions'][$defName] = $entityObj;
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
        if(substr($type, -2) === '[]') {
            return 'array';
        } elseif(in_array($type, ['int', 'integer'])) {
            return 'integer';
        } elseif($type === 'numeric') {
            return 'number';
        } elseif($type === 'bool') {
            return 'boolean';
        } elseif($type === 'Entity') {
            return 'Entity';
        }
        return 'string';
    }

    // 各种类型的默认显示值
    private function defaultCast($type, $default = null, $validation = null) 
    {
        if ($default !== null) { return $default; }
        if ($type === 'string' && $validation){
            return "string [{$validation}]";
        }
        elseif ($type === 'integer') { 
            return 1; 
        }
        elseif ($type === 'number')  { 
            return 1.1; 
        }
        elseif ($type === 'boolean') { 
            return true; 
        }
        return "string";
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
     * @var string
     */
    private $config;

    /**
     * @var array
     */
    private $swagger;
}