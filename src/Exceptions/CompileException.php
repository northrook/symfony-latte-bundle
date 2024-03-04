<?php declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Services;

use Exception;

/**
 * The exception occurred during Component compilation.
 */
class CompileException extends Exception
{

	/**
	 * Construct the exception. Note: The message is NOT binary safe.
	 * @link https://php.net/manual/en/exception.construct.php
	 * @param  string  $message  [optional] The Exception message to throw.
	 * @param  string|null  $string
	 * @param  int  $code  [optional] The Exception code.
	 */
	public function __construct(
		string                  $message = '',
		public readonly ?string $string = null,
		int                     $code = 422,
	) {
		parent::__construct( $message, $code );
		$this->message = "$message";
		$this->code = $code;
	}
}