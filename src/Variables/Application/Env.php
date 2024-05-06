<?php

namespace Northrook\Symfony\Latte\Variables\Application;

use Northrook\Symfony\Latte\Variables\Application;

/**
 * Return type for {@see Application::getEnv()}
 *
 * @version 1.0 ✅
 * @author  Martin Nielsen <mn@northrook.com>
 */
final readonly class Env
{
    public function __construct(
        public bool $debug,
        public bool $authorized,
        public bool $production,
        public bool $staging,
        public bool $dev,
    ) {}
}