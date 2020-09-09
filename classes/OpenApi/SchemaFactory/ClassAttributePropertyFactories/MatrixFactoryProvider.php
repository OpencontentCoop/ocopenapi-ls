<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\OpenApi\StringTools;
use Opencontent\Opendata\Api\Values\Content;

class MatrixFactoryProvider extends ContentClassAttributePropertyFactory
{
    private $matrixDefinition;

    public function provideProperties()
    {
        $schema = array(
            "type" => "array",
            "description" => $this->getPropertyDescription(),
            "items" => array(
                '$ref' => "#/components/schemas/" . StringTools::toCamelCase($this->attribute->attribute('identifier') . '_item'),
            ),
            'minItems' => (bool)$this->attribute->attribute('is_required') ? 1 : 0
        );

        return $schema;
    }

    public function serializeValue(Content $content, $locale)
    {
        $rows = $this->getContent($content, $locale);
        foreach ($rows as $index => $row){
            $rows[$index] = ['index' => $index] + $row;
        }

        return $rows;
    }

    private function getMatrixDefinition()
    {
        if ($this->matrixDefinition === null) {
            /** @var \eZMatrixDefinition $definition */
            $definition = $this->attribute->attribute('content');
            $columns = $definition->attribute('columns');

            $this->matrixDefinition = array_column($columns, 'identifier');
        }

        return $this->matrixDefinition;
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (isset($payload[$this->providePropertyIdentifier()])){
            $rows = $payload[$this->providePropertyIdentifier()];
            $data = [];
            foreach ($rows as $index => $row){
                if (isset($row['index'])) {
                    if (!is_numeric($row['index'])) {
                        throw new InvalidPayloadException($this->providePropertyIdentifier(), "index {$row['index']} is not an integer value");
                    }
                    $index = (int)$row['index'];
                    unset($row['index']);
                }
                if (isset($data[$index])){
                    throw new InvalidPayloadException($this->providePropertyIdentifier(), "duplicate index $index");
                }
                foreach ($this->getMatrixDefinition() as $column){
                    if (!isset($row[$column])){
                        throw new InvalidParameterException($column, 'value is required');
                    }
                    if (!is_string($row[$column])){
                        throw new InvalidParameterException($column, 'value must be a string, ' . gettype($row[$column]) . ' given');
                    }
                }
                $data[$index] = $row;
            }
            ksort($data);
            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                array_values($data)
            );
        }
    }
}