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
    ) {
        $this->stopwatch->start( $this::class, 'Preprocessor' );
    }

    abstract protected function construct() : void;

    /**
     * Load the content string to process.
     *
     * @param string  $content
     *
     * @return $this
     */
    final public function load( string $content ) : self {
        $this->content = $content;

        return $this;
    }

    /**
     * Retrieve the processed content string.
     *
     * @return string
     */
    final public function getContent() : string {
        $this->construct();

        $this->stopwatch->stop( $this::class );
        return $this->content;
    }

    /**
     * Update the content string for this iteration.
     *
     * @param string  $match
     * @param string  $update
     *
     * @return void
     */
    final protected function updateContent( string $match, string $update ) : void {
        $this->content = str_ireplace( $match, $update, $this->content );
    }

    final protected function prepareContent(
        bool $minify = false,
        bool $preserveComments = false,
        bool $preserveExcessWhitespaces = false,
    ) : void {

        // Remove Latte comments
        if ( !$preserveComments ) {
            $this->content = preg_replace(
                '/{\*.*?\*}/ms',
                '',
                $this->content,
            );
        }

        $this->content = preg_replace_callback(
            '/{(.*?)}/ms',
            static function ( array $m ) {
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