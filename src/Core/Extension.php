<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Core;

use Latte;
use Latte\Runtime\Template;
use Northrook\Symfony\Latte\Nodes\ClassNode;
use Northrook\Symfony\Latte\Nodes\ElementNode;
use Northrook\Symfony\Latte\Nodes\IdNode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Extension extends Latte\Extension
{
    private Template $template;

    public function __construct(
        private readonly UrlGeneratorInterface $url,
        private readonly ?LoggerInterface      $logger = null,
    ) {}

    /**
     * Initializes before template is rendered.
     */
    public function beforeRender( Template $template ) : void {
        $this->template = $template;
//        dd( $template );
    }

    public function getTags() : array {
        return [
            'n:id'      => [ IdNode::class, 'create' ],
            'n:class'   => [ ClassNode::class, 'create' ],
            'n:element' => [ ElementNode::class, 'create' ],
            //			'n:href'  => [ HrefNode::class, 'create' ], // TODO: Implement
            //			'asset'   => [$this->asset, 'asset'],       // TODO: Should be part of n:href? Can we autocomplete assets (images)?
            //			'n:src'   => [$this->asset, 'asset'],       // TODO: Can we make this autocomplete, and in <img only?
            //       If no image, provide optional default
        ];
    }


    public function getFilters() : array {
        return [
            'echo' => static function ( $string ) {
                echo $string;
            },
            'path' => [ $this, 'encodeString' ],
        ];
    }

    public function getFunctions() : array {
        return [
            'path'        => [ $this, 'resolvePathFromRoute' ],
            'print_debug' => static function ( ...$args ) {
                echo '<pre>';
                foreach ( $args as $arg ) {
                    print_r( $arg );
                }
                echo '</pre>';
            },
            'var_dump'    => static function ( ...$args ) {
                var_dump( ... $args );
            },
            'dump'        => static function ( ...$args ) {
                foreach ( $args as $arg ) {
                    dump( $arg );
                }
            },
            'dd'          => static fn ( ...$args ) => dd( $args ),
        ];
    }


    public function resolvePathFromRoute( string $route, bool $absoluteUrl = false ) : string {
        try {
            return $this->url->generate(
                name          : $route,
                referenceType : $absoluteUrl
                                    ? UrlGeneratorInterface::ABSOLUTE_URL
                                    : UrlGeneratorInterface::ABSOLUTE_PATH,
            );
        }
        catch (
        InvalidParameterException |
        MissingMandatoryParametersException |
        RouteNotFoundException  $e
        ) {
            $this->logger?->error(
                message : "Unable to resolve route {name}",
                context : [
                              'name'      => $route,
                              'template'  => $this->template,
                              'exception' => $e,
                          ],
            );
            return $route;
        }

    }

    public function encodeString( string $string ) : string {
        return '<data value="' . base64_encode( $string ) . '"></data>';
    }

}