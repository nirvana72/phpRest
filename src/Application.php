<?php
namespace PhpRest;

class Application
{
    /**
     * @Inject
     * @var \PhpRest\Controller\ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var \DI\Container
     */
    private $container;

    public static function createDefault($conf = []) {
        $default = [
            'env' => 'develop'
        ];

        $builder = new \DI\ContainerBuilder();

        $builder->addDefinitions($default);
        $builder->addDefinitions($conf);
        $builder->useAutowiring(false);
        $builder->useAnnotations(true);
        $container = $builder->build();

        $app = $container->make('\PhpRest\Application');
        $app->container = $container;
        return $app;
    }

    public function get($id) {
        return $this->container->get($id);
    }

    public function loadRoutesFromPath($fullPath, $namespace) {
        $d = dir($fullPath);
        while (($entry = $d->read()) !== false){
            if ($entry == '.' || $entry == '..') { continue; }
            $path = $fullPath . '/' . $entry;
            if (is_file($path)) {
                if (substr($entry, -14) === 'Controller.php') {
                    $className = $namespace . '\\' . substr($entry, 0, -4);
                    $this->containerBuilder->build($className);
                }
            } else {
                $this->loadRoutesFromPath($path, $namespace . '\\' . $entry);
            }
        }
        $d->close();
    }

    public function test() {
        // echo 'Application.test';
        $ctl = $this->get('\App\Controller\Admin\UserController');
        call_user_func_array([$ctl, 'test'], ['nijia', 'nzh']);
    }
}