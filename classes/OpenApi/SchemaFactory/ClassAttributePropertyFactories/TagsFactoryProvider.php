<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class TagsFactoryProvider extends ContentClassAttributePropertyFactory
{
    const SUBTREE_LIMIT_FIELD = 'data_int1';
    const HIDE_ROOT_TAG_FIELD = 'data_int3';
    const MAX_TAGS_FIELD = 'data_int4';
    const EDIT_VIEW_FIELD = 'data_text1';

    const MODE_LIST_SELECT = 'Select';

    private $subtreeLimit;
    private $maxTagsNumber;
    private $selectionType;

    public function __construct(\eZContentClass $class, \eZContentClassAttribute $attribute)
    {
        parent::__construct($class, $attribute);
        $this->subtreeLimit = $attribute->attribute(self::SUBTREE_LIMIT_FIELD);
        $this->maxTagsNumber = $attribute->attribute(self::MAX_TAGS_FIELD);
        $this->selectionType = $attribute->attribute(self::EDIT_VIEW_FIELD);
    }

    public function provideProperties()
    {
        $schema = array(
            "type" => 'string',
            "description" => $this->getPropertyDescription(),
        );

        if ($this->subtreeLimit) {
            $schema['type'] = 'array';
            $schema['items'] = [
                'type' => 'string'
            ];
            $tagList = $this->getDataSource($this->subtreeLimit);
            if (!empty($tagList)){
                $schema["enum"] = $tagList;
            }
        }

        return $schema;
    }

    private function getDataSource($tagID)
    {
        $result = array();
        $tag = \eZTagsObject::fetch($tagID);
        if (!$tag instanceof \eZTagsObject) {
            return $result;
        }

        $pathString = $tag->attribute('path_string');
        $rows = (array)\eZDB::instance()->arrayQuery("
        SELECT jsonb_object_agg(k.locale, k.keyword) ->> 'ita-IT' as keyword 
                FROM eztags t, eztags_keyword k 
                WHERE t.id = k.keyword_id and t.main_tag_id = 0 and path_string like '$pathString%' and path_string != '$pathString'
                GROUP BY t.id order by parent_id, id, path_string
        ");

        return array_column($rows, 'keyword');
    }
}