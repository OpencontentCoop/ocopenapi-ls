<?php

namespace Opencontent\OpenApi\OperationFactory\Tags;

use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;
use eZTagsDescription;
use eZTagsObject;
use eZContentLanguage;
use eZDB;

trait TagFetchTrait
{
    private function getFetchConditions(array $parameters): string
    {
        $db = eZDB::instance();
        $locale = $this->getCurrentRequestLanguage();

        $customConds = " WHERE ";
        if (isset($parameters['main_only']) && $parameters['main_only']) {
            $customConds .= " eztags.main_tag_id = 0 AND ";
        }
        $customConds .= " eztags.id = eztags_keyword.keyword_id ";
        $customConds .= " AND " . eZContentLanguage::languagesSQLFilter('eztags') . " ";
        $customConds .= " AND eztags_keyword.locale = '" . $db->escapeString($locale) . "' ";

        $parentRemoteId = $parameters['parent_remote_id'] ?? null;
        if ($parentRemoteId) {
            $parentTagObject = eZTagsObject::fetchByRemoteID($parentRemoteId);
            if (!$parentTagObject instanceof eZTagsObject) {
                throw new NotFoundException();
            }
            $customConds .= " AND eztags.path_string like '"
                . $db->escapeString($parentTagObject->attribute('path_string')) . "%' ";
            $depth = $parentTagObject->attribute('depth') + 1;
            $customConds .= " AND eztags.depth = $depth";
        }

        $byTerm = $parameters['term'] ?? null;
        if ($byTerm) {
            $customConds .= " AND eztags_keyword.keyword ilike '%" . $db->escapeString($byTerm) . "%' ";
        }
        $likeTerm = $parameters['search'] ?? null;
        if ($likeTerm) {
            $customConds .= " AND eztags_keyword.keyword ilike '%" . $db->escapeString($likeTerm) . "%' ";
        }
        
        return $customConds;
    }

    protected function fetchTagCount(array $parameters): int
    {
        $tagsList = eZTagsObject::fetchObjectList(
            eZTagsObject::definition(),
            [],
            [],
            [],
            null,
            false,
            false,
            [
                [
                    'operation' => 'COUNT( * )',
                    'name' => 'row_count',
                ],
            ],
            ['eztags_keyword'],
            $this->getFetchConditions($parameters)
        );

        return (int)$tagsList[0]['row_count'];
    }

    protected function fetchTagList(array $parameters): array
    {
        $limits = [
            'limit' => intval($parameters['limit'] ?? 20),
            'offset' => intval($parameters['offset'] ?? 0),
        ];

        $tagsList = eZTagsObject::fetchObjectList(
            eZTagsObject::definition(),
            [],
            [],
            ['eztags_keyword.keyword' => 'asc'],
            $limits,
            true,
            false,
            [
                'DISTINCT eztags.*',
                ['operation' => 'eztags_keyword.keyword', 'name' => 'keyword'],
                ['operation' => 'eztags_keyword.locale', 'name' => 'locale'],
            ],
            ['eztags_keyword'],
            $this->getFetchConditions($parameters)
        );

        return $this->serializeTags($tagsList);
    }

    protected function fetchTagByRemoteId($remoteId): array
    {
        $tagObject = eZTagsObject::fetchByRemoteID($remoteId);
        if (!$tagObject instanceof eZTagsObject) {
            throw new NotFoundException();
        }

        return $this->serializeTag($tagObject);
    }

    protected function serializeTags($tagObjects): array
    {
        $data = [];
        foreach ($tagObjects as $tagObject) {
            $data[] = $this->serializeTag($tagObject);
        }

        return $data;
    }

    protected function serializeTag($tagObject): ?array
    {
        if (!$tagObject instanceof eZTagsObject) {
            return null;
        }

        $voc = $description = null;
        $translations = [];
        foreach ($tagObject->getTranslations() as $translation) {
            if ($translation->attribute('locale') === 'ita-PA') {
                $voc = $translation->attribute('keyword');
            } else {
                $translations[] = [
                    'language' => SchemaBuilderToolsTrait::getLanguageList()[$translation->attribute('locale')],
                    'term' => $translation->attribute('keyword'),
                ];
            }
        }
        $tagsDescription = eZTagsDescription::fetchObject(
            eZTagsDescription::definition(), null,
            [
                'keyword_id' => $tagObject->attribute('id'),
                'locale' => $this->getCurrentRequestLanguage(),
            ]
        );
        if ($tagsDescription instanceof eZTagsDescription) {
            $description = $tagsDescription->attribute('description_text');
        }

        $synonyms = [];
        foreach ($tagObject->getSynonyms() as $synonym) {
            foreach ($synonym->getTranslations() as $synonymTranslation) {
                $synonyms[] = [
                    'language' => SchemaBuilderToolsTrait::getLanguageList()[$synonymTranslation->attribute('locale')],
                    'term' => $synonymTranslation->attribute('keyword'),
                ];
            }
        }

        return [
            'id' => $tagObject->attribute('remote_id'),
            'term' => $tagObject->getKeyword($this->getCurrentRequestLanguage()),
            'isSynonymOf' => $tagObject->isSynonym() ? $tagObject->getMainTag()->attribute('remote_id') : null,
            'voc' => $voc,
            'description' => $description,
            'translations' => $translations,
            'synonyms' => $synonyms,
            'parent' => $this->serializeTag($tagObject->getParent()),
        ];
    }
}