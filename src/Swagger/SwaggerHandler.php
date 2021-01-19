<?php
namespace PhpRest\Swagger;

use Symfony\Component\HttpFoundation\Response;

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

    public function build($app, $namesapce, $key) {
        $this->swagger = [];
        $this->swagger['swagger'] = '2.0';
        $this->swagger['info'] = $this->makeInfo($key);
        $this->swagger['host'] = $this->appHost;
        $this->swagger['schemes'] = $this->config['schemes'];
        $this->swagger['tags'] = [];
        $this->swagger['schemes'] = ['https', 'http'];
        $this->swagger['paths'] = [];

        foreach($app->controllers as $classPath) {
            if (0 !== strpos($classPath, $namesapce)) { continue; }

            $controller = $app->controllerBuilder->build($classPath);
            $modifyTime = date("Y-m-d H:i:s", $controller->modifyTimespan);

            // 多个controller 可以写同名tag, 则会排版到同一个tag下
            if ($this->isTagExist($controller->summary) === false) {
                $tag = [];
                $tag['name'] = $controller->summary;
                $tag['description'] = $controller->description;
                $this->swagger['tags'][] = $tag;
            }

            foreach($controller->routes as $action => $route) {
                $path = $this->makePath($controller, $route);
                $method = strtolower($route->method);
                $this->swagger['paths'][$route->uri][$method] = $path;
            }
        }

        // paths 必须是非空对象, 以下代码解决空路由时swagger解析报错问题
        if (count($this->swagger['paths']) === 0) {
            $this->swagger['paths'] = new \stdClass();
        }
    }

    private function makeInfo($key) {
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

    private function makePath($controller, $route) {
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
            
            $in = $this->castParamSource($param->source);
            if ($in === 'body') {
                // 所有body需要参数合并成一个对象
                $schema = [];
                $schema['type'] = $this->typeCast($param->type[0]);
                $schema['description'] = $param->description . ($param->validation? " [{$param->validation}]" : '');
                if ($schema['type'] === 'array') {
                    $subType = $this->typeCast(substr($param->type[0], 0, -2));
                    $default = $this->defaultCast($subType);
                    $schema['items'] = ['type' => $subType, 'default' => $default];
                } else {
                    $schema['default'] = $this->defaultCast($schema['type'], $param->default, $param->validation);
                }
                $bodyParameter['schema']['properties'][$pName] = $schema;
            } else {
                $parameter = [];
                $parameter['name'] = $pName;
                $parameter['in'] = $in;
                $parameter['type'] = $this->typeCast($param->type[0]);
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

    private function makeResponse() {
        $responses = [];
        $responses['200'] = [];
        $responses['200']['description'] = 'successful operation';
        $responses['200']['schema'] = [];
        // $responses['200']['type'] = 'object';
        // $responses['200']['properties'] = [];

        $schema = [];
        $schema['type'] = 'integer';
        $schema['default'] = 1;
        $schema['description'] = '';
        $responses['200']['schema']['properties']['ret'] = $schema;

        $schema2 = [];
        $schema2['type'] = 'string';
        $schema2['default'] = 'success';
        $schema2['description'] = '成功';
        $responses['200']['schema']['properties']['msg'] = $schema2;
        return $responses;
    }

    public function toJson() {
        return json_encode($this->swagger, true);
    }

    private function isTagExist($tagName) {
        foreach($this->swagger['tags'] as $tag) {
            if ($tag['name'] === $tagName) {
                return true;
            }
        }
        return false;
    }

    private function castParamSource($source) {
        $ary = explode('.', $source);
        if ($ary[0] === 'request') return 'body';
        if ($ary[0] === 'query') return 'query';
        if ($ary[0] === 'attributes') return 'path';
        if ($ary[0] === 'headers') return 'header';
        return $ary[0];
    }

    private function typeCast($type) {
        if(substr($type, -2) === '[]') {
            return 'array';
        } elseif(in_array($type, ['int', 'integer'])) {
            return 'integer';
        } elseif($type === 'numeric') {
            return 'number';
        } elseif($type === 'bool') {
            return 'boolean';
        }
        return 'string';
    }

    private function defaultCast($type, $default = null, $validation = null) {
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