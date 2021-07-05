<?php

namespace ErrorHeroModule\Spec\Fixture;

use Psr\Container\ContainerInterface;
use stdClass;

class NotSupportedContainer implements ContainerInterface
{
    /**
     * @return stdClass
     */
    public function get($id) { return new stdClass(); }
    /**
     * @return bool
     */
    public function has($id) { return true; }
}