<?php

namespace Pucene\Component\Pucene\Dbal\Interpreter\Element;

use Pucene\Component\Pucene\Compiler\Element\TermElement;
use Pucene\Component\Pucene\Compiler\ElementInterface;
use Pucene\Component\Pucene\Dbal\Interpreter\InterpreterInterface;
use Pucene\Component\Pucene\Dbal\Interpreter\PuceneQueryBuilder;
use Pucene\Component\Pucene\Dbal\ScoringAlgorithm;

class MatchAllInterpreter implements InterpreterInterface
{
    /**
     * @param TermElement $element
     */
    public function interpret(ElementInterface $element, PuceneQueryBuilder $queryBuilder, string $index)
    {
        return null;
    }

    /**
     * @param TermElement $element
     */
    public function scoring(ElementInterface $element, ScoringAlgorithm $scoring, string $index)
    {
        return null;
    }
}
