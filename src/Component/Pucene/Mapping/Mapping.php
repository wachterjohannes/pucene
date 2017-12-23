<?php

namespace Pucene\Component\Pucene\Mapping;

use Pucene\Component\Analysis\AnalyzerInterface;
use Pucene\Component\Mapping\Types;

class Mapping
{
    /**
     * @var array
     */
    private $indexMapping;

    /**
     * @var AnalyzerInterface
     */
    private $analyzer;

    /**
     * @var array
     */
    private $fields;

    public function __construct(array $indexMapping, AnalyzerInterface $analyzer)
    {
        $this->indexMapping = $indexMapping;
        $this->analyzer = $analyzer;

        $this->fields = [];
        foreach ($indexMapping as $name => $index) {
            $this->fields[$name] = [];
            foreach ($index['mappings'] as $type) {
                $this->fields[$name] = $this->prepareFields($type['properties']);
            }
        }
    }

    public function getTypeForField(string $index, string $field): ?string
    {
        if (!array_key_exists($index, $this->fields) || !array_key_exists($field, $this->fields[$index])) {
            return null;
        }

        return $this->fields[$index][$field]['type'];
    }

    public function getAnalyzerForField(string $index, string $field): ?AnalyzerInterface
    {
        $type = $this->getTypeForField($index, $field);
        if (Types::TEXT === $type) {
            return $this->analyzer;
        }

        return null;
    }

    private function prepareFields(array $properties, string $prefix = '')
    {
        $result = [];
        foreach ($properties as $key => $field) {
            $fieldName = $prefix . $key;
            $result[$fieldName] = $field;
            unset($result[$fieldName]['properties']);

            if (0 < count($field['properties'])) {
                $result[$fieldName]['type'] = Types::OBJECT;
                $result = array_merge($result, $this->prepareFields($field['properties'], $fieldName . '.'));
            }
        }

        return $result;
    }
}
