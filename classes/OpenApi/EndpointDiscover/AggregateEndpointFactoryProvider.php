<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use eZContentClass;
use eZContentObjectTreeNode;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\OperationFactoryCollection;

/**
 * Espone endpoint di sola lettura che aggregano su un intero sottoalbero
 * tutti i contenuti di una o più classi, indipendentemente dalla cartella in cui si trovano.
 *
 * Le rotte generate da RoleEndpointFactoryDiscover seguono invece le cartelle (una rotta per nodo),
 * quindi non permettono, per esempio, di ottenere in una sola chiamata tutte le unità organizzative
 * di Amministrazione oppure tutti gli articoli di Novità.
 */
class AggregateEndpointFactoryProvider extends EndpointFactoryProvider
{
    use LoggableTrait;

    /**
     * path: percorso dell'endpoint
     * node: url alias del nodo radice del sottoalbero
     * classes: identificatori delle classi da includere
     */
    const DEFINITIONS = [
        [
            'path' => '/amministrazione/unita-organizzative',
            'node' => 'amministrazione',
            'classes' => ['organization'],
        ],
        [
            'path' => '/novita/avvisi-notizie-comunicati',
            'node' => 'novita',
            'classes' => ['article'],
        ],
    ];

    /**
     * @var EndpointFactory[]|null
     */
    private $endpoints;

    public function getEndpointFactoryCollection()
    {
        if ($this->endpoints === null) {
            $this->endpoints = [];
            foreach (self::DEFINITIONS as $definition) {
                $this->appendEndpoints($definition);
            }
        }

        return new EndpointFactoryCollection($this->endpoints);
    }

    private function appendEndpoints(array $definition)
    {
        $node = eZContentObjectTreeNode::fetchByURLPath($definition['node']);
        if (!$node instanceof eZContentObjectTreeNode) {
            $this->log('Node not found for aggregate endpoint ' . $definition['path'], 'error');

            return;
        }

        $classes = [];
        foreach ($definition['classes'] as $classIdentifier) {
            if (eZContentClass::classIDByIdentifier($classIdentifier)) {
                $classes[] = $classIdentifier;
            } else {
                $this->log('Class ' . $classIdentifier . ' not found for ' . $definition['path'], 'error');
            }
        }
        if (empty($classes)) {
            return;
        }

        $tag = str_replace('/', '-', trim($definition['path'], '/'));

        $this->endpoints[] = (new EndpointFactory\NodeClassesEndpointFactory(
            $node->attribute('node_id'),
            $classes
        ))
            ->setPath($definition['path'])
            ->addTag($tag)
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    new OperationFactory\ContentObject\FilteredSearchOperationFactory(),
                ])
            );

        $this->endpoints[] = (new EndpointFactory\NodeClassesEndpointFactory(
            $node->attribute('node_id'),
            $classes
        ))
            ->setPath($definition['path'] . '/{id}')
            ->addTag($tag)
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    new OperationFactory\ContentObject\ReadOperationFactory(),
                ])
            );
    }
}
