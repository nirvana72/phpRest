<?php
namespace PhpRest\Render;

use Symfony\Component\HttpFoundation\Response;

interface ResponseRenderInterface
{
    /**
     * @param $return
     */
    public function render($return): Response;
}