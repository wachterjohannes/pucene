<?php

namespace Pucene\InvertedIndex;

use Pucene\Analysis\Token;

interface StorageInterface
{
    public function save(Token $token, array $document);

    public function getDocuments(Token $token);
}