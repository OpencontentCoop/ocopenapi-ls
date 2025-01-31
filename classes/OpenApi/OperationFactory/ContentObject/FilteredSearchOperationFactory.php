<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use eZObjectRelationListType;
use eZObjectRelationType;
use eZSelectionType;
use eZTagsType;
use OCEventType;
use Opencontent\OpenApi\Exceptions\InternalException;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;
use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\Opendata\Api\Values\ContentClass;
use Opencontent\Opendata\Api\Values\SearchResults;
use erasys\OpenApi\Spec\v3 as OA;

class FilteredSearchOperationFactory extends SearchOperationFactory
{
    private $filters;

    private static $enableFacets = false;

    protected function generateSearchParameters()
    {
        $parameters = parent::generateSearchParameters();
        foreach ($this->getFilters() as $name => $filter) {
            $parameters[] = new OA\Parameter(
                $name,
                $filter['in'],
                $filter['description'],
                [
                    'schema' => $this->generateSchemaProperty($filter['schema']),
                ]
            );
        }
        return $parameters;
    }

    protected function buildQueryParts($endpointFactory)
    {
        $filters = $this->getFilters();

        $query = [];

        $searchTerm = $this->getCurrentRequestParameter('searchTerm');
        if (!empty($searchTerm)) {
            $query[] = 'q = \'' . addcslashes($searchTerm, '\'()[]"') . '\'';
        }

        $query[] = 'classes [' . implode(',', $endpointFactory->getClassIdentifierList()) . ']';
        $query[] = 'subtree [' . $endpointFactory->getNodeId() . ']';
        $query[] = 'raw[meta_language_code_ms] in [' . $this->getCurrentRequestLanguage() . ']';

        $eventQuery = [];
        foreach ($filters as $name => $filter) {
            $name = str_replace('[]', '', $name);
            switch ($filter['dataType']) {
                case eZTagsType::DATA_TYPE_STRING:
                case eZObjectRelationListType::DATA_TYPE_STRING:
                case eZObjectRelationType::DATA_TYPE_STRING:
                    $values = $this->getCurrentRequestParameter($name);
                    if (!empty($values) && !empty($values[0])) {
                        $values = array_map(function ($value) {
                            $value = addcslashes($value, '\'()[]"');
                            return '"' . $value . '"';
                        }, $values);
                        $query[] = $filter['queryField'] . ' in [' . implode(',', $values) . ']';
                    }
                    break;

                case eZSelectionType::DATA_TYPE_STRING:
                    $value = $this->getCurrentRequestParameter($name);
                    if (!empty($values) && is_string($value)) {
                        $query[] = $filter['queryField'] . ' = \'"' . addcslashes($value, '\'()[]"') . '"\'';
                    }
                    break;

                case OCEventType::DATA_TYPE_STRING:
                    if (!isset($eventQuery[$filter['queryField']])) {
                        $eventQuery[$filter['queryField']] = true;
                        $from = $this->getCurrentRequestParameter($filter['queryField'] . '_from');
                        $to = $this->getCurrentRequestParameter($filter['queryField'] . '_to');
                        if ($from || $to) {
                            $interval = [
                                $from ?? '*',
                                $to ?? '*',
                            ];
                            $query[] = "calendar[" . $filter['queryField'] . "] = ['$interval[0]','$interval[1]']";
                        }
                    }
                    break;
            }
        }

        if (self::$enableFacets) {
            $facets = $this->getCurrentRequestParameter('facets');
            if (!empty($facets) && !empty($facets[0])) {
                $facets = array_intersect($facets, $filters['facets[]']['schema']['items']['enum']);
                if (!empty($facets)) {
                    $query[] = "facets [" . implode(',', $facets) . "]";
                }
            }
        }

        $sortValues = $filters['sort']['schema']['enum'];
        $sort = $this->getCurrentRequestParameter('sort');
        $order = $this->getCurrentRequestParameter('order');
        $sortString = '[published=>desc]';
        if ($sort || $order) {
            $sort = $sort ?? 'published';
            if (!in_array($sort, $sortValues)) {
                throw new InvalidParameterException('sort', $sort);
            }
            if ($sort === 'content-title'){
                $sort = 'name';
            }
            $order = $order === 'asc' ? 'asc' : 'desc';
            $sortString = "[$sort=>$order]";
        }

        $query[] = 'sort ' . $sortString;

        $limit = (int)$this->getCurrentRequestParameter('limit');
        if ($limit <= 0 || $limit > self::MAX_LIMIT) {
            throw new InvalidParameterException('limit', $limit);
        }
        $query[] = 'limit ' . $limit;

        $offsetCompat = $this->getCurrentRequestParameter('skip')
            ?? $this->getCurrentRequestParameter('offset');
        $offset = (int)$offsetCompat;
        if ($offset < 0) {
            throw new InvalidParameterException('offset', $offset);
        }
        $query[] = 'offset ' . $offset;

        return $query;
    }

    public function handleCurrentRequest(\Opencontent\OpenApi\EndpointFactory $endpointFactory)
    {
        try {
            return parent::handleCurrentRequest($endpointFactory);
        } catch (InternalException $e) {
            throw new InternalException('Internal error fetching data');
        }
    }

    protected function buildResult(SearchResults $searchResults, $path)
    {
        $result = parent::buildResult($searchResults, $path);

        if (self::$enableFacets) {
            $result['facets'] = $searchResults->facets;
        }
        $filters = $this->getFilters();

        $embedHtml = $this->getCurrentRequestParameter('embed_html');
        if (in_array($embedHtml, $filters['embed_html']['schema']['enum'])) {
            foreach ($result['items'] as $index => $hit) {
                $contentObjectIdentifier = \eZDB::instance()->escapeString($hit['id']);
                $query = "SELECT node_id
                  FROM ezcontentobject_tree
                  WHERE main_node_id = node_id AND
                        contentobject_id in ( SELECT ezcontentobject.id
                                              FROM ezcontentobject
                                              WHERE ezcontentobject.remote_id='$contentObjectIdentifier')";
                $resArray = \eZDB::instance()->arrayQuery($query);
                if (count($resArray) == 1 && $resArray !== false) {
                    $nodeId = $resArray[0]['node_id'];
                    $view = $embedHtml;
                    $html = \OpenPABootstrapItaliaContentEnvironmentSettings::generateView(
                        $nodeId,
                        $view
                    );
                    $hit['embedded'][$embedHtml] = EmbedReadOperationFactory::cleanEmbedHtml($html);
                }else{
                    $hit['embedded'][$embedHtml] = '';
                }
                $result['items'][$index] = $hit;
            }
        }

        $fields = $this->getCurrentRequestParameter('fields');
        if ($fields) {
            $fields = array_map('trim', explode(',', $fields));
            if (!empty($fields)) {
                $fieldsMock = array_fill_keys($fields, true);
                $hits = $result['items'];
                foreach ($hits as $index => $hit) {
                    $filteredHit = array_intersect_ukey($hit, $fieldsMock, function ($key1, $key2) {
                        if ($key1 == $key2) {
                            return 0;
                        } else {
                            if ($key1 > $key2) {
                                return 1;
                            } else {
                                return -1;
                            }
                        }
                    });
                    $result['items'][$index] = $filteredHit;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getFilters(): array
    {
        if ($this->filters === null) {
            $this->filters = [];

            $sortValues = [
                'published',
                'modified',
                'content-title',
            ];
            $facetsValues = [];

            $schemaFactories = $this->getSchemaFactories();
            if (count($schemaFactories) == 1) {
                $schemaFactory = $schemaFactories[0];
                if ($schemaFactory instanceof ContentClassSchemaFactory) {
                    $schema = $schemaFactory->generateSchema();
                    $classRepository = new ClassRepository();
                    try {
                        $class = $classRepository->load($schemaFactory->getClassIdentifier());
                        if ($class instanceof ContentClass) {
                            foreach ($class->fields as $field) {
                                if (!$field['isSearchable']) {
                                    continue;
                                }
                                $identifier = $field['identifier'];
                                if (!isset($schema->properties[$identifier])) {
                                    continue;
                                }
                                if (isset($schema->properties[$identifier]['deprecated'])) {
                                    continue;
                                }
                                $facetsValues[] = $identifier;

                                switch ($field['dataType']) {
                                    case eZTagsType::DATA_TYPE_STRING;
                                        $this->filters[$identifier . '[]'] = [
                                            'in' => OA\Parameter::IN_QUERY,
                                            'description' => sprintf('Filter by %s field', $identifier),
                                            'schema' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'string',
                                                    'enum' => $schema->properties[$identifier]['enum'] ?? [],
                                                ],
                                            ],
                                            'queryField' => $identifier,
                                            'dataType' => $field['dataType'],
                                        ];
                                        break;

                                    case eZObjectRelationListType::DATA_TYPE_STRING;
                                    case eZObjectRelationType::DATA_TYPE_STRING;
                                        $this->filters[$identifier . '[]'] = [
                                            'in' => OA\Parameter::IN_QUERY,
                                            'description' => sprintf('Filter by %s field id', $identifier),
                                            'schema' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'string',
                                                ],
                                            ],
                                            'queryField' => $identifier . '.remote_id',
                                            'dataType' => $field['dataType'],
                                        ];
                                        break;

                                    case eZSelectionType::DATA_TYPE_STRING:
                                        $this->filters[$identifier] = [
                                            'in' => OA\Parameter::IN_QUERY,
                                            'description' => sprintf('Filter by %s field', $identifier),
                                            'schema' => [
                                                'type' => 'string',
                                                'enum' => $schema->properties[$identifier]['enum'] ?? [],
                                            ],
                                            'queryField' => $identifier,
                                            'dataType' => $field['dataType'],
                                        ];
                                        break;

                                    case OCEventType::DATA_TYPE_STRING:
                                        $sortValues[] = $identifier;
                                        $this->filters[$identifier . '_from'] = [
                                            'in' => OA\Parameter::IN_QUERY,
                                            'description' => 'Filter items starting after the entered date. Input value expects to be given a string containing an English date format (see https://www.php.net/manual/en/function.strtotime.php)',
                                            'schema' => [
                                                'type' => 'string',
                                                'example' => 'now',
                                            ],
                                            'queryField' => $identifier,
                                            'dataType' => $field['dataType'],
                                        ];
                                        $this->filters[$identifier . '_to'] = [
                                            'in' => OA\Parameter::IN_QUERY,
                                            'description' => 'Filter items that end before the entered date. Input value expects to be given a string containing an English date format (see https://www.php.net/manual/en/function.strtotime.php)',
                                            'schema' => [
                                                'type' => 'string',
                                                'example' => 'next month',
                                            ],
                                            'queryField' => $identifier,
                                            'dataType' => $field['dataType'],
                                        ];
                                        break;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }

            $this->filters['sort'] = [
                'in' => OA\Parameter::IN_QUERY,
                'description' => 'Sort items by field',
                'schema' => [
                    'type' => 'string',
                    'enum' => $sortValues,
                    'default' => 'published',
                ],
                'queryField' => 'sort',
                'dataType' => null,
            ];
            $this->filters['order'] = [
                'in' => OA\Parameter::IN_QUERY,
                'description' => 'Sort order',
                'schema' => [
                    'type' => 'string',
                    'enum' => ['desc', 'asc',],
                    'default' => 'desc',
                ],
                'queryField' => 'sort',
                'dataType' => null,
            ];

            $this->filters['fields'] = [
                'in' => OA\Parameter::IN_QUERY,
                'description' => 'Comma separated fields to show. The blank value means show all fields',
                'schema' => [
                    'type' => 'string',
                ],
                'queryField' => null,
                'dataType' => null,
            ];
            if (self::$enableFacets && count($facetsValues)) {
                $this->filters['facets[]'] = [
                    'in' => OA\Parameter::IN_QUERY,
                    'description' => 'Facets search result',
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => $facetsValues,
                        ],
                    ],
                    'queryField' => null,
                    'dataType' => null,
                ];
            }
            $this->filters['embed_html'] = [
                'in' => OA\Parameter::IN_QUERY,
                'description' => 'Html version of item',
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'fullpage',
                        'card',
                    ],
                ],
                'queryField' => null,
                'dataType' => null,
            ];
        }

        return $this->filters;
    }
}