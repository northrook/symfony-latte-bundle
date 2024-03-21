<?php

namespace Northrook\Symfony\Latte\Parameters;

/** Return Type for {@see Parameters::getEnv()}
 *
 * @version 1.0 ✅
 * @author  Martin Nielsen <mn@northrook.com>
 */
final class Env
{
    public function __construct(
        public bool $debug,
        public bool $authorized,
        public bool $production,
        public bool $staging,
        public bool $dev,
    ) {}
}