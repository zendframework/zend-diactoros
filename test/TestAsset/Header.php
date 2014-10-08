<?php
namespace PhlyTest\Http\TestAsset;

class Header
{
    public $value = '';

    public function __toString()
    {
        return $this->value;
    }
}
