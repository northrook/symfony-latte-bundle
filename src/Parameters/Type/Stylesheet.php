<?php

namespace Northrook\Symfony\Latte\Parameters\Type;

use Northrook\Support\Arr;
use Northrook\Symfony\Latte\Core\Asset;

class Stylesheet extends Asset
{
    public function print() : string {
        return Arr::implode(
            [
                '<link',
                $this->__toString(),
                ... $this->attributes(),
                '/>',
            ],
            ' ',
        );
    }
}