<?php
namespace PhpRest\Event;

interface EventInterface
{
    public function listen(): array;

    public function handle($params): void;
}