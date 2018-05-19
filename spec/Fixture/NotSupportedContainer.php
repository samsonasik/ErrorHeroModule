<?php

namespace ErrorHeroModule\Spec\Fixture;

use Psr\Container\ContainerInterface;
use stdClass;

class NotSupportedContainer implements ContainerInterface
{
    public function get($id) { return new stdClass(); }
    public function has($id) { return true; }
}