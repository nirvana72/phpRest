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
                $swaggerHandler->build($app, $namesapce);
                $callback($swaggerHandler->swagger, $key);
                return new Response($swaggerHandler->toJson());
            });
        }
    }   

    public function build($app, $namesapce) 
    {
        $this->swagger = new Schemas\SwaggerObject();
        $this->swagger->host = $this->appHost;
        $this->swagger->schemes = $this->config['schemes'];

        $info = new Schemas\InfoObject();
        $info->title = "{$this->appName} - {$this->appEnv}";
        $info->description = $this->config['description'];

        $info->contact = new Schemas\ContactObject();
        $info->contact->name = "{$this->config['author']} <{$this->config['email']}>";
        $info->contact->email = $this->config['email'];
        $info->license = new Schemas\LicenseObject();        
        $this->swagger->info = $info;

        foreach($app->controllers as $classPath) {
            if (0 === strpos($classPath, $namesapce)) {
                $controller = $app->controllerBuilder->build($classPath);
                $tag = new Schemas\TagObject();
                $tag->name = $controller->summary;
                $tag->description = $controller->description;
                $tag->externalDocs = new Schemas\ExternalDocsObject();
                $tag->externalDocs->description = '缓存时间 - ' . date("Y-m-d H:i:s", $controller->modifyTimespan);
                $this->swagger->tags[] = $tag;

                foreach($controller->routes as $action => $route) {
                    $path = new Schemas\PathObject();
                    $path->tags = [$tag->name];
                    $path->summary = $route->summary;
                    $path->description = $route->description;
                    $path->operationId = $action;

                    $method = strtolower($route->method);
                    foreach($route->requestHandler->paramMetas as $pName => $param) {
                        $parameter = new Schemas\ParameterObject();
                        $parameter->name = $pName;
                        $parameter->in = $this->castParamSource($param->source);
                        $parameter->description = "{$param->description} [{$param->validation}]";
                        $parameter->required = ! $param->isOptional;
                        $parameter->type = $param->type[0];
                        $parameter->default = $param->default;
                        $path->parameters[] = $parameter;
                    }
                    
                    $this->swagger->paths[$route->uri][$method] = $path;                     
                }
            }
        }
    }

    public function toJson() {
       return json_encode($this->swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function castParamSource($source) {
        $ary = explode('.', $source);
        if ($ary[0] === 'request') return 'body';
        if ($ary[0] === 'query') return 'query';
        if ($ary[0] === 'attributes') return 'path';
        if ($ary[0] === 'header') return 'header';
        return $ary[0];
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
     * @var SwaggerObject
     */
    private $swagger;
}