<?php

namespace Northrook\Symfony\Latte\Parameters\Type;

use Northrook\Support\Arr;
use Northrook\Symfony\Latte\Core\Asset;

class Script extends Asset
{
    public function print() : string {
        return Arr::implode(
            [
                '<script',
                $this->__toString(),
                ... $this->attributes(),
                '</script>',
            ],
            ' ',
        );
    }
}