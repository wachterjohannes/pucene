<?php

namespace Pucene\Component\Pucene\Dbal;

use Doctrine\DBAL\Connection;
use Pucene\Component\Analysis\Token;
use Pucene\Component\Pucene\Math\ElasticsearchPrecision;
use Pucene\Component\Pucene\Model\Document;
use Pucene\Component\Pucene\Model\Field;

class DocumentPersister
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PuceneSchema
     */
    private $schema;

    /**
     * @param Connection $connection
     * @param PuceneSchema $schema
     */
    public function __construct(Connection $connection, PuceneSchema $schema)
    {
        $this->connection = $connection;
        $this->schema = $schema;
    }

    /**
     * @param Document $document
     * @param Field[] $fields
     */
    public function persist(Document $document, array $fields)
    {
        $this->insertDocument($document);

        $terms = [];
        $documentTerms = [];
        foreach ($fields as $field) {
            $fieldNorm = ElasticsearchPrecision::fieldNorm($field->getNumberOfTerms());
            $fieldId = $this->insertField($document, $field, $fieldNorm);

            $fieldTerms = [];
            foreach ($field->getTokens() as $token) {
                if (!array_key_exists($token->getEncodedTerm(), $terms)) {
                    $terms[$token->getEncodedTerm()] = $this->findOrCreateTerm($token->getEncodedTerm());
                }

                if (!array_key_exists($token->getEncodedTerm(), $fieldTerms)) {
                    $fieldTerms[$token->getEncodedTerm()] = 0;
                }
                if (!array_key_exists($token->getEncodedTerm(), $documentTerms)) {
                    $documentTerms[$token->getEncodedTerm()] = 0;
                }

                ++$fieldTerms[$token->getEncodedTerm()];
                ++$documentTerms[$token->getEncodedTerm()];

                $this->insertToken($document->getId(), $field->getName(), $token->getEncodedTerm(), $token);
            }

            foreach ($fieldTerms as $term => $frequency) {
                $this->connection->insert(
                    $this->schema->getFieldTermsTableName(),
                    [
                        'document_id' => $document->getId(),
                        'field_name' => $field->getName(),
                        'term' => $term,
                        'frequency' => $frequency,
                    ]
                );
            }
        }

        foreach ($documentTerms as $term => $frequency) {
            $this->connection->insert(
                $this->schema->getDocumentTermsTableName(),
                [
                    'document_id' => $document->getId(),
                    'term' => $term,
                    'frequency' => $frequency,
                ]
            );
        }
    }

    /**
     * @param Document $document
     */
    protected function insertDocument(Document $document)
    {
        $this->connection->insert(
            $this->schema->getDocumentsTableName(),
            [
                'id' => $document->getId(),
                'type' => $document->getType(),
                'document' => json_encode($document->getDocument()),
                'indexed_at' => new \DateTime(),
            ],
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                'datetime',
            ]
        );
    }

    /**
     * @param Document $document
     * @param Field $field
     *
     * @param float $fieldNorm
     *
     * @return string
     */
    protected function insertField(Document $document, Field $field, float $fieldNorm)
    {
        $this->connection->insert(
            $this->schema->getFieldsTableName(),
            [
                'document_id' => $document->getId(),
                'name' => $field->getName(),
                'number_of_terms' => $field->getNumberOfTerms(),
                'field_norm' => $fieldNorm,
            ]
        );
        $fieldId = $this->connection->lastInsertId();

        return $fieldId;
    }

    /**
     * @param string $term
     *
     * @return int
     */
    protected function findOrCreateTerm($term)
    {
        $result = $this->connection->fetchArray(
            'SELECT term FROM ' . $this->schema->getTermsTableName() . ' WHERE term = ?',
            [$term]
        );

        if ($result) {
            return $result[0];
        }

        $this->connection->insert($this->schema->getTermsTableName(), ['term' => $term]);

        return $this->connection->lastInsertId();
    }

    /**
     * @param string $documentId
     * @param string $fieldName
     * @param int $termId
     * @param Token $token
     */
    protected function insertToken($documentId, $fieldName, $termId, Token $token)
    {
        $this->connection->insert(
            $this->schema->getTokensTableName(),
            [
                'document_id' => $documentId,
                'field_name' => $fieldName,
                'term' => $termId,
                'start_offset' => $token->getStartOffset(),
                'end_offset' => $token->getEndOffset(),
                'position' => $token->getPosition(),
                'type' => $token->getType(),
            ]
        );
    }
}
