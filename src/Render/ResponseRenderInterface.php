<?php
namespace PhpRest\Render;

interface ResponseRenderInterface
{
    /**
     * @param $return
     */
    public function render($return);
}