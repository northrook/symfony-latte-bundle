<?php declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Preprocessor;

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

    protected string           $content;
    protected ?LoggerInterface $logger    = null;
    protected ?Stopwatch       $stopwatch = null;

    public function setProfiler( ?LoggerInterface $logger, ?Stopwatch $stopwatch, ) : PreprocessorInterface {

        $this->logger    = $logger;
        $this->stopwatch = $stopwatch;

        return $this;
    }


    /**
     * Load the content string to process.
     *
     * @param string  $content
     *
     * @return $this
     */
    final public function load( string $content ) : self {
        $this->stopwatch->start( $this::class, 'Preprocessor' );
        $this->content = $content;

        return $this;
    }

    /**
     * Retrieve the processed content string.
     *
     * @return string
     */
    final public function getContent() : string {
        $this->process();

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
    ) : Preprocessor {

        // Remove Latte comments
        if ( !$preserveComments ) {
            $this->content = preg_replace(
                [ '/{\*.*?\*}/ms', '/xmlns:\w.*?=".*?"/ms' ],
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

                // TODO: Improve auto-null to allow for filters (check from $ to pipe)
//                 if ( !str_contains( $var, '??' ) ) {
//                     $test = $var;
// //					print_r($test);
//                     $var = '{' . $var .= '??false}';
//                     // dump( $m,$var );
//
//                     return $var;
//                 }

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

        return $this;
    }
}