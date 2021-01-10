<?php

require __DIR__.'/../vendor/autoload.php';

// 加载配置
$app = \PhpRest\Application::createDefault(__DIR__.'/../config/config.php');

// 加载路由
$app->loadRoutesFromPath( __DIR__.'/../App/Controller', 'App\\Controller');

