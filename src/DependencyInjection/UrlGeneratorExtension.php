<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\DependencyInjection;

use Latte\Extension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UrlGeneratorExtension extends Extension
{
    public function __construct( private readonly UrlGeneratorInterface $generator ) {}

    public function getFunctions() : array {
        return [
            'url'  => $this->getUrl( ... ),
            'path' => $this->getPath( ... ),
        ];
    }

    public function getPath( string $name, array $parameters = [], bool $relative = false ) : string {
        return $this->generator->generate(
            $name, $parameters,
            $relative
                ? UrlGeneratorInterface::RELATIVE_PATH
                : UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    public function getUrl( string $name, array $parameters = [], bool $relative = false ) : string {
        return $this->generator->generate(
            $name, $parameters,
            $relative
                ? UrlGeneratorInterface::NETWORK_PATH
                : UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}