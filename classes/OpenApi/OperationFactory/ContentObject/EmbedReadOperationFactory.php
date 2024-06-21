<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;

class EmbedReadOperationFactory extends ReadOperationFactory
{
    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        if (!isset($properties['parameters'])) {
            $properties['parameters'] = [];
        }
        foreach ($this->getFilters() as $name => $filter) {
            $properties['parameters'][] = new OA\Parameter(
                $name,
                $filter['in'],
                $filter['description'],
                [
                    'schema' => $this->generateSchemaProperty($filter['schema']),
                ]
            );
        }
        return $properties;
    }

    protected function getFilters(): array
    {
        if ($this->filters === null) {
            $this->filters = [];
            $this->filters['fields'] = [
                'in' => OA\Parameter::IN_QUERY,
                'description' => 'Comma separated fields to show. The blank value means show all fields',
                'schema' => [
                    'type' => 'string',
                ],
                'queryField' => null,
                'dataType' => null,
            ];
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

    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)) {
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }

        $resource = $this->getResource($endpointFactory, $requestId, $this->getCurrentRequestLanguage());

        $filters = $this->getFilters();
        $embedHtml = $this->getCurrentRequestParameter('embed_html');
        if (in_array($embedHtml, $filters['embed_html']['schema']['enum'])) {
            $contentObjectIdentifier = \eZDB::instance()->escapeString($resource['id']);
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
                $resource['embedded'][$embedHtml] = EmbedReadOperationFactory::cleanEmbedHtml($html);
            } else {
                $resource['embedded'][$embedHtml] = '';
            }
        }

        $fields = $this->getCurrentRequestParameter('fields');
        if ($fields) {
            $fields = array_map('trim', explode(',', $fields));
            if (!empty($fields)) {
                $fieldsMock = array_fill_keys($fields, true);
                $filteredHit = array_intersect_ukey($resource, $fieldsMock, function ($key1, $key2) {
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
                $resource = $filteredHit;
            }
        }

        $result->variables = $resource;

        return $result;
    }

    public static function cleanEmbedHtml(string $html)
    {
        $html = preg_replace(['/\s*\R\s*/', '/\s{2,}/', '/[\t\n]/'], '', $html);
        $domain = 'https://' . \eZSys::hostname();
        $html = str_replace('href="/', 'href="' . $domain . '/', $html);
        $html = str_replace('src="/ocembed', 'data-local-src="/ocembed', $html);
        $html = str_replace('src="/', 'src="' . $domain . '/', $html);
        $html = str_replace('data-src="', 'src="', $html);
        if (strpos($html, 'id="relations-map') !== false) {
            $html = str_replace('width: 100%; height: 400px;', 'display:none', $html);
        }
        return $html;
    }

}