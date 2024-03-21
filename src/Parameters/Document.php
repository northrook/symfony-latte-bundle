<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Elements\Element\Attributes;

class Document
{

    public readonly Attributes $body;

    public string $title = __METHOD__;

    public function __construct(
        private readonly Application $application,
        private readonly Content     $content,
    ) {
        $this->body = new Attributes(
            id          : $this->application->request->getPathInfo(),
            class       : 'test cass',
            data_strlen : strlen( $this->content->__toString( å ) ),
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