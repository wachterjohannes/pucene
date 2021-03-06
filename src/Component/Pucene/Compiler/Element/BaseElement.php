<?php

namespace Pucene\Component\Pucene\Compiler\Element;

use Pucene\Component\Pucene\Compiler\ElementInterface;

abstract class BaseElement implements ElementInterface
{
    /**
     * @var float
     */
    private $boost;

    public function __construct(float $boost = 1.0)
    {
        $this->boost = $boost;
    }

    public function getBoost(): float
    {
        return $this->boost;
    }
}
