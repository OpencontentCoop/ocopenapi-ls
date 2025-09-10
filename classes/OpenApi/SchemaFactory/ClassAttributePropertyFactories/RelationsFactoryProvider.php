<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\NotFoundException as ApiNotFoundException;
use Opencontent\OpenApi\Logger;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;
use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\Exception\NotFoundException;
use Opencontent\Opendata\Api\Values\Content;

class RelationsFactoryProvider extends ContentClassAttributePropertyFactory
{
    const MODE_LIST_BROWSE = 0;
    const MODE_LIST_DROP_DOWN = 1;
    const MODE_LIST_RADIO = 2;
    const MODE_LIST_CHECKBOX = 3;
    const MODE_LIST_MULTIPLE = 4;
    const MODE_LIST_TEMPLATE_MULTIPLE = 5;
    const MODE_LIST_TEMPLATE_SINGLE = 6;

    private $selectionType;

    private $classConstraintList;

    private $defaultPlacement;

    private $isSelect;

    private $resourceEndpointPath;

    public function __construct(\eZContentClass $class, \eZContentClassAttribute $attribute)
    {
        parent::__construct($class, $attribute);

        /** @var array $classContent */
        $classContent = $this->attribute->content();
        $this->selectionType = (int)$classContent['selection_type'];
        $classConstraintList = (array)$classContent['class_constraint_list'];
        foreach ($classConstraintList as $classConstraint) {
            if (\eZContentClass::classIDByIdentifier($classConstraint)) {
                $this->classConstraintList[] = $classConstraint;
            } else {
                Logger::instance()->error("Class not found in class_constraint_list", [
                    'class_constraint' => $classConstraint,
                    'class' => $class->attribute('identifier'),
                    'attribute' => $attribute->attribute('identifier'),
                ]);
            }
        }
        $this->defaultPlacement = isset($classContent['default_placement']['node_id']) ? $classContent['default_placement']['node_id'] : null;

        if ($this->selectionType == self::MODE_LIST_DROP_DOWN
            || $this->selectionType == self::MODE_LIST_MULTIPLE
            || $this->selectionType == self::MODE_LIST_TEMPLATE_SINGLE
            || $this->selectionType == self::MODE_LIST_TEMPLATE_MULTIPLE
        ) {
            $this->isSelect = true;
        }
    }


    public function provideProperties()
    {
        $schema = array(
            "description" => $this->getPropertyDescription(),
        );

        $uriDescription = 'Resource uri';
        if (RelationsSchemaFactory::getResourceEndpointPath($this->attribute->attribute('id')) != '/') {
            $uriDescription .= ' from ' . RelationsSchemaFactory::getResourceEndpointPath($this->attribute->attribute('id'));
        }

        //@todo @see RelationsSchemaFactory::generateSchema()
        $schema['type'] = 'array';
        $schema['items'] = [
            "type" => "object",
            "properties" => [
                "id" => [
                    "title" => 'Resource Id',
                    "type" => "string",
                    "readOnly" => true,
                ],
                "uri" => [
                    "title" => "$uriDescription",
                    "type" => "string",
                ],
                "priority" => [
                    "title" => "Priority",
                    "type" => "integer",
                    'format' => 'int32',
                ],
            ],
            "required" => [
                "uri"
            ]
        ];
        if ($this->selectionType == self::MODE_LIST_DROP_DOWN) {
            $schema['maxItems'] = 1;
        }

        if ($this->selectionType == self::MODE_LIST_CHECKBOX || $this->isSelect) {
            if ($this->defaultPlacement) {
                $schema['examples'] = array();
                $dataSource = $this->getDataSource();
                foreach ($dataSource as $remote => $name) {
                    $schema['examples'][\eZCharTransform::instance()->transformByGroup($name, 'urlalias')] = [
                        'value' => RelationsSchemaFactory::getResourceEndpointPath($this->attribute->attribute('id')) . $remote . '#' . \eZCharTransform::instance()->transformByGroup($name, 'urlalias')
                    ];
                }
            }
        }

        if ((bool)$this->attribute->attribute('is_required')) {
            $schema['minItems'] = 1;
        }

        return $schema;
    }

    private function getDataSource($fields = '[metadata.remoteId=>metadata.name]')
    {
        $query = "select-fields $fields";
        if (is_array($this->classConstraintList) && !empty($this->classConstraintList)) {
            $query .= " classes [" . implode(',', $this->classConstraintList) . "]";
        }
        if ($this->defaultPlacement) {
            $query .= " subtree [" . $this->defaultPlacement . "]";
        }

        $query .= " sort [name=>asc] limit 300";

        $contentSearch = new ContentSearch();
        $contentSearch->setEnvironment(new \DefaultEnvironmentSettings());

        return $contentSearch->search($query);
    }

    public function serializeValue(Content $content, $locale)
    {
        $list = [];

        $relationsContent = (array)$this->getContent($content, $locale);
        $nameList = [];
        foreach ($relationsContent as $item) {
            if (isset($item['id'])) {
                $nameList[$item['id']] = $item['name'][$locale] ?? $item['name']['ita-IT'];
            }
        }
        // refetch avoiding unsynchronized remote_ids
        $identifier = $this->attribute->attribute('identifier');
        if (isset($content->data[$locale][$identifier])) {
            $attribute = \eZContentObjectAttribute::fetch(
                $content->data[$locale][$identifier]['id'],
                $content->data[$locale][$identifier]['version']
            );
            if ($attribute instanceof \eZContentObjectAttribute) {
                $attributeContent = $attribute->content();
                foreach ($attributeContent['relation_list'] as $relation) {
                    $remoteId = $this->getRemoteIdByObjectId($relation['contentobject_id']);
                    $uri = RelationsSchemaFactory::getResourceEndpointPath($this->attribute->attribute('id')) . $remoteId;
                    if (isset($nameList[$relation['contentobject_id']])) {
                        $uri .= '#' . \eZCharTransform::instance()->transformByGroup($nameList[$relation['contentobject_id']], 'urlalias');
                    }
                    $list[] = [
                        'id' => $remoteId,
                        'uri' => $uri,
                        'priority' => (int)$relation['priority']
                    ];
                }
            }
        }

        return $list;
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (isset($payload[$this->providePropertyIdentifier()])) {
            $payloadData = $payload[$this->providePropertyIdentifier()];
            $data = [];
            foreach ($payloadData as $payloadItem) {
                if (!isset($payloadItem['uri'])) {
                    throw new InvalidPayloadException($this->providePropertyIdentifier(), 'Missing url value');
                }
                if (strpos($payloadItem['uri'], RelationsSchemaFactory::getResourceEndpointPath($this->attribute->attribute('id'))) !== false) {
                    $remoteId = basename(parse_url($payloadItem['uri'], PHP_URL_PATH));
                    try {
                        $data[] = [
                            'id' => $this->getObjectIdByRemoteId($remoteId),
                            'priority' => isset($payloadItem['priority']) ? $payloadItem['priority'] : count($payloadData) + 1
                        ];
                    } catch (NotFoundException $e) {
                        throw new ApiNotFoundException($payloadItem['uri']);
                    }
                } else {
                    throw new InvalidPayloadException($this->providePropertyIdentifier(), $payloadItem['uri']);
                }
            }

            usort($data, function ($a, $b) {
                if ($a['priority'] == $b['priority']) {
                    return 0;
                }
                return ($a['priority'] < $b['priority']) ? -1 : 1;
            });

            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                array_unique(array_column($data, 'id'))
            );
        }
    }

    private function getObjectIdByRemoteId($remoteId)
    {
        $whereSql = "ezcontentobject.remote_id='" . \eZDB::instance()->escapeString($remoteId) . "'";
        $fetchSQLString = "SELECT ezcontentobject.id FROM ezcontentobject WHERE $whereSql";
        $resArray = \eZDB::instance()->arrayQuery($fetchSQLString);
        if (count($resArray) == 1 && $resArray !== false) {
            return $resArray[0]['id'];
        }

        throw new NotFoundException($remoteId);
    }

    private function getRemoteIdByObjectId($objectId)
    {
        $whereSql = "ezcontentobject.id = " . intval($objectId);
        $fetchSQLString = "SELECT ezcontentobject.remote_id FROM ezcontentobject WHERE $whereSql";
        $resArray = \eZDB::instance()->arrayQuery($fetchSQLString);
        if (count($resArray) == 1 && $resArray !== false) {
            return $resArray[0]['remote_id'];
        }

        throw new NotFoundException($objectId);
    }
}