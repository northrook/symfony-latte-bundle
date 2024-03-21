<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Elements\Body;

class Document
{

    public readonly Body $body;

    public string $title = __METHOD__;

    public function __construct(
        private readonly Application $application,
        private readonly Content     $content,
    ) {
        $this->body = new Body(
            id          : $this->application->request->getPathInfo(),
            class       : 'test cass',
            data_strlen : strlen( $this->content->__toString() ),
        );
    }


    public function __get( string $name ) {
        $name = "get" . ucfirst( $name );
        if ( method_exists( $this, $name ) ) {
            return $this->$name() ?? null;
        }

        return null;
    }

    protected function getBase() : string {
        return __DIR__ . '/document.latte';
    }


}