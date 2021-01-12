<?php

require __DIR__.'/../vendor/autoload.php';

// 根据配置创建app对象
$app = \PhpRest\Application::createDefault(__DIR__.'/../config/config.php');

// 加载路由
$app->loadRoutesFromPath( __DIR__.'/../App/Controller', 'App\\Controller');

// 解析请求
$app->dispatch();