<?php

namespace Northrook\Symfony\Latte\Compiler;

class MissingTemplateException extends \LogicException
{


    public function __construct(
        string                         $message,
        public readonly array          $templates,
        public readonly string | array $key,
        int                            $code = 0,
        ?\Throwable                    $previous = null,
    ) {
        parent::__construct( $message, $code, $previous );
    }
}