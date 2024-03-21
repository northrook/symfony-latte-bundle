<?php declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Preprocessor;

use Northrook\Support\Attributes\EntryPoint;
use Northrook\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @version 1.0 ☑️
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook Documentation
 * @todo    Update URL to documentation
 */
abstract class Preprocessor implements PreprocessorInterface
{

    protected string $content;

    #[EntryPoint]
    final public function __construct(
        protected ?LoggerInterface $logger = null,
        protected ?Stopwatch       $stopwatch = null,
    ) {}

    abstract protected function construct() : void;

    final public function load( string $content ) : void {
        $this->content = $content;
    }

    final public function getContent() : string {
        $this->construct();

        return $this->content;
    }

    final protected function updateContent( string $match, string $update ) : void {
        $this->content = str_ireplace( $match, $update, $this->content );
    }

    protected function prepareContent(
        bool $minify = false,
        bool $preserveComments = false,
        bool $preserveExcessWhitespaces = false,
    ) : void {


        // Remove Latte comments
        // TODO: Remove all comments?
        if ( !$preserveComments ) {
            $this->content = preg_replace(
                        '/{\*.*?\*}/ms',
                        '',
                        $this->content,
                count : $count,
            );
            $this->logger->info( 'Removed {count} Latte comments', [ 'count' => $count ] );
        }

        $this->content = preg_replace_callback(
            '/{(.*?)}/ms',
            function ( array $m ) {
                $var = trim( $m[ 1 ] );
                if ( !str_contains( $var, '$' ) || Str::startsWith( $var, [ 'layout', 'block', 'var' ] ) ) {
                    return '{' . $var . '}';
                }

                if ( !str_contains( $var, '??' ) ) {
                    $test = $var;
//					print_r($test);
                    $var = '{' . $var .= '??false}';
                    // dump( $m,$var );

                    return $var;
                }

                $var = '{' . $var . '}';

                return $var;
            },
            $this->content,
        );


        if ( $minify === false && $preserveExcessWhitespaces === false ) {

            $this->content = preg_replace(
                '/^.\s*\n/ms',
                "\n",
                $this->content,
            );

        }

        if ( $minify ) {
            $this->content = Str::squish( $this->content );
        }

    }

}