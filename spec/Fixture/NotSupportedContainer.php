<?php

namespace ErrorHeroModule\Spec\Fixture;

use Psr\Container\ContainerInterface;
use stdClass;

final class NotSupportedContainer implements ContainerInterface
{
    public function get($id): stdClass|array
    {
        if (random_int(0, 1) !== 0) {
            return new stdClass();
        }

        return [];
    }

    public function has($id): bool
    {
        return true;
    }
}