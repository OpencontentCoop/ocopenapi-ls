<?php

use Opencontent\OpenApi;

class OpenApiProvider implements ezpRestProviderInterface
{
    private $routes;

    public function getRoutes()
    {
        if ($this->routes !== null){
            return $this->routes;
        }

        $builder = OpenApi\Loader::instance()->getSchemaBuilder();
        $schema = $builder->build();
        $version = 1;

        $routes = [
            'openApi' => new ezpRestVersionedRoute(new OpenApiRailsRoute(
                '/',
                'OpenApiController',
                'endpoint',
                [],
                'http-get'
            ), $version),
        ];

        // sort pattern by length
        $paths = $schema['paths'];
        $patterns = array_keys($paths);
        usort($patterns, function ($patternA, $patternB){
            $a = explode('{', $patternA)[0];
            $b = explode('{', $patternB)[0];
            if (mb_strlen($a) == mb_strlen($b)) {
                return 0;
            }
            return (mb_strlen($a) < mb_strlen($b)) ? 1 : -1;
        });

        foreach ($patterns as $index => $pattern) {
            $path = $paths[$pattern];
            foreach ($path as $method => $definition) {
                if (!in_array(strtoupper($method), ['POST', 'GET', 'PUT', 'DELETE', 'PATCH']) || empty($definition)){
                    continue;
                }
                $operationId = $definition['operationId'];
                $defaultValues = ['operationId' => $operationId];

                if (isset($definition['parameters'])) {
                    foreach ($definition['parameters'] as $parameter) {
                        if ($parameter['in'] == 'query') {
                            $defaultValues[$parameter['name']] = isset($parameter['schema']['default']) ? $parameter['schema']['default'] : null;
                        } elseif ($parameter['in'] == 'path') {
                            $pattern = str_replace('{' . $parameter['name'] . '}', ':' . $parameter['name'], $pattern);
                        }
                    }
                }

                $routes['openApi' . $index . ucfirst($operationId)] = new ezpRestVersionedRoute(new OpenApiRailsRoute(
                    $pattern,
                    'OpenApiController',
                    'action',
                    $defaultValues,
                    'http-' . $method
                ), $version);
            }
        }
        $this->routes = $routes;

        return $routes;
    }

    public function getViewController()
    {
        return new OpenApiViewController();
    }

}
