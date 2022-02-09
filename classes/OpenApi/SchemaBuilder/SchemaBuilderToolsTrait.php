<?php

namespace Opencontent\OpenApi\SchemaBuilder;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\StringTools;

trait SchemaBuilderToolsTrait
{
    private static $languageList;

    public static function getLanguageList()
    {
        if (self::$languageList === null) {
            self::$languageList = [];
            $languages = \eZINI::instance()->variable('RegionalSettings', 'SiteLanguageList');
            foreach ($languages as $language) {
                self::$languageList[$language] = \eZLocale::instance($language)->HTTPLocaleCode;
            }
        }

        return self::$languageList;
    }

    /**
     * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
     * @param string $str String in camel case format
     * @param string $separator
     * @return    string            $str Translated into underscore format
     */
    protected function fromCamelCase($str, $separator = '_')
    {
        return StringTools::fromCamelCase($str, $separator);
    }

    protected function generateLink($properties)
    {
        $link = new OA\Link();
        foreach ($properties as $key => $value) {
            $link->{$key} = $value;
        }

        return $link;
    }

    protected function toCamelCase($str, $capitaliseFirstChar = true)
    {
        return StringTools::toCamelCase($str, $capitaliseFirstChar);
    }

    protected function generateHeaderLanguageParameter()
    {
        return new OA\Parameter('Accept-Language', OA\Parameter::IN_HEADER, 'Current Translation', [
            'schema' => $this->generateSchemaProperty([
                'type' => 'string',
                'default' => self::getLanguageList()[\eZContentObject::defaultLanguage()],
                'enum' => array_values(self::getLanguageList())
            ]),
            'required' => false,
        ]);
    }

    protected function generateSchemaProperty($properties)
    {
        $schema = new ReferenceSchema();
        foreach ($properties as $key => $value) {
            if (property_exists($schema, $key)) {
                $schema->{$key} = $value;
            }
        }

        return $schema;
    }
}
