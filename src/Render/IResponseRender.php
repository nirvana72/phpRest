<?php
namespace PhpRest\Render;

interface IResponseRender
{
    /**
     * @param $return
     */
    public function render($return);
}