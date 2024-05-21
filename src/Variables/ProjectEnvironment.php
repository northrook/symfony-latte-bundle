<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\variables;

use Northrook\Symfony\Latte\GlobalVariable;

/**
 * Return type for {@see GlobalVariable::getEnv()}
 *
 * @internal
 *
 * @version 1.0 ✅
 * @author  Martin Nielsen <mn@northrook.com>
 */
final readonly class ProjectEnvironment
{
    public function __construct(
        public bool $debug,
        public bool $production,
        public bool $staging,
        public bool $dev,
    ) {}
}