<?php

namespace Northrook\Symfony\Latte\Interfaces;

use Latte;

/**
 * Latte Preprocessor Interface.
 */
interface PreprocessorInterface
{
	/**
	 * Load the content string to process.
	 *
	 * @param  string  $content
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