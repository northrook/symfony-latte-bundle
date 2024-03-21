<?php declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Preprocessor;

use Latte;

/**
 * Latte Preprocessor Interface.
 *
 * * Parsed by the {@see Latte\Loader} when generating cache files.
 *
 * @version 1.0 ✅
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook Documentation
 * @todo    Update URL to documentation
 */
interface PreprocessorInterface
{
    /**
     * Load the content string to process.
     *
     * @param string  $content
     *
     * @return void
     */
    public function load(
        string $content,
    ) : void;

    /**
     * Return the processed content string to the {@see Latte\Loader}.
     *
     * @return string
     */
    public function getContent() : string;
}