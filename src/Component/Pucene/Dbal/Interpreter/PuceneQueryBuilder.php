<?php

namespace Pucene\Component\Pucene\Dbal\Interpreter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Pucene\Component\Math\MathExpressionBuilder;
use Pucene\Component\Pucene\Dbal\PuceneSchema;

class PuceneQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    private $documentAlias;

    /**
     * @var PuceneSchema
     */
    private $schema;

    /**
     * @var string[]
     */
    private $joins = [];

    /**
     * @param Connection $connection
     * @param PuceneSchema $schema
     * @param string $documentAlias
     */
    public function __construct(Connection $connection, PuceneSchema $schema, string $documentAlias = 'document')
    {
        parent::__construct($connection);

        $this->documentAlias = $documentAlias;
        $this->schema = $schema;
    }

    public function math()
    {
        return new MathExpressionBuilder();
    }

    public function selectFrequency(string $field, string $term)
    {
        return $this->math()->count($this->math()->variable($this->joinTerm($field, $term) . '.id'));
    }

    public function joinToken(string $field)
    {
        $tokenName = 'token' . ucfirst($field);
        $tokenName = trim(preg_replace('/\W/', '_', $tokenName), '_');
        if (in_array($tokenName, $this->joins)) {
            return $tokenName;
        }

        $condition = sprintf(
            '%1$s.document_id = %2$s.id AND %1$s.field_name = \'%3$s\'',
            $tokenName,
            $this->documentAlias,
            $field
        );

        $this->leftJoin($this->documentAlias, $this->schema->getTokensTableName(), $tokenName, $condition);

        return $this->joins[] = $tokenName;
    }

    public function joinTerm(string $field, string $term)
    {
        $termName = 'term' . ucfirst($field) . ucfirst($term);
        $termName = trim(preg_replace('/\W/', '_', $termName), '_');
        if (in_array($termName, $this->joins)) {
            return $termName;
        }

        $condition = sprintf(
            '%1$s.document_id = %2$s.id AND %1$s.field_name = \'%3$s\' AND %4$s.term = \'%5$s\'',
            $termName,
            $this->documentAlias,
            $field,
            $termName,
            $term
        );

        $this->leftJoin($this->documentAlias, $this->schema->getTokensTableName(), $termName, $condition);

        return $this->joins[] = $termName;
    }

    public function joinField(string $field)
    {
        $fieldName = 'field' . ucfirst($field);
        if (in_array($fieldName, $this->joins)) {
            return $fieldName;
        }

        $this->leftJoin(
            $this->documentAlias,
            $this->schema->getFieldsTableName(),
            $fieldName,
            $fieldName . '.document_id = ' . $this->documentAlias . '.id AND ' . $fieldName . '.name = \'' . $field . '\''
        );

        return $this->joins[] = $fieldName;
    }
}
