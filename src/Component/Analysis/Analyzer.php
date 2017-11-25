<?php

namespace Pucene\Component\Analysis;

use Pucene\Component\Analysis\CharacterFilter\CharacterFilterInterface;
use Pucene\Component\Analysis\TokenFilter\TokenFilterInterface;
use Pucene\Component\Analysis\Tokenizer\TokenizerInterface;

class Analyzer implements AnalyzerInterface
{
    /**
     * @var CharacterFilterInterface
     */
    protected $characterFilter;

    /**
     * @var TokenizerInterface
     */
    protected $tokenizer;

    /**
     * @var TokenFilterInterface
     */
    protected $tokenFilter;

    public function __construct(
        CharacterFilterInterface $characterFilter,
        TokenizerInterface $tokenizer,
        TokenFilterInterface $tokenFilter
    ) {
        $this->characterFilter = $characterFilter;
        $this->tokenizer = $tokenizer;
        $this->tokenFilter = $tokenFilter;
    }

    public function analyze(string $fieldContent): array
    {
        $input = $this->characterFilter->filter($fieldContent);
        $tokens = $this->tokenizer->tokenize($input);

        $result = [];
        foreach ($tokens as $token) {
            $result = array_merge($result, $this->tokenFilter->filter($token));
        }

        return $result;
    }
}
