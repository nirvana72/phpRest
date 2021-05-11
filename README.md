# 介绍 Introduction

PhpRest 是一款纯restful的轻量框架, 此框架参考自 [caoym/phpboot](https://github.com/caoym/phpboot).

~~~
<?php
namespace App\Controller;

class IndexController
{
    /**
     * @route GET /
     */
    public function index($p1) 
    {
        return "p1 = {$p1}";
    }
}

~~~
![swagger](https://nij20190123.oss-cn-shanghai.aliyuncs.com/phpRest/phpRest-index-swagger.png)

# 框架特点
* 注释即代码，代码即文档
* 自动路由
* 依赖注入
* 参数绑定
* 丰富的验证封装
* swagger 完美支持

# 环境 Requirements
 - PHP >= 7.3

# 安装 Installation

~~~
composer require nirvana72/phpRest
~~~

### nginx 配置
~~~
server {
    listen 80;
    server_name example.com;
    index index.php;
    root /path/to/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_pass 127.0.0.1:9000;
    }
}
~~~

### apache 配置
>开启 mod_rewrite 模块，入口目录(/public) 下添加 .htaccess 文件：
~~~
Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
~~~
# 文档 Document
文笔不好, 直接看示例代码. 或直接下载示例项目 [phpRest-example](https://github.com/nirvana72/phpRest-example)

[参数绑定](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/ParamsController.php)

[参数绑定实体类](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/EntityController.php)

[中间件hook](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/HookController.php)

[数据库操作](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/DbController.php)

[ORM](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/OrmController.php)

[swagger](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/SwaggerController.php)

[文件上传](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/FileUploadController.php)

[事件驱动](https://github.com/nirvana72/phpRest-example/blob/main/App/Controller/EventController.php)

# 其它 Other
框架默认缓存实现是文件缓存（Filesystem），生产环境推安装 apcu 扩展
