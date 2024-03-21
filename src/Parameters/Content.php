<?php

namespace Northrook\Symfony\Latte\Parameters;

class Content
{

    private string $content = '';

    public function __construct() {}

    public function __toString() : string {
        return $this->content;
    }

    public function getContent() : string {
        return $this->content;
    }

    public function setContent( string $content ) : self {
        $this->content = $content;
        return $this;
    }
}