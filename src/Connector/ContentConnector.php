<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Legacy\Content;

class ContentConnector extends BaseConnector {

    /**
     * @inheritdoc
     */
    public function searchRelations($config, $text): array{

        $allowedTypes = $config['source'] ?? [];
        $results = [];

        // VERSION A - CND Storage Library
        if($this->container->offsetExists('cnd-library.storage')){
            $parameters = [
                'limit' => 20,
                'contenttypes' => $allowedTypes,
                'order' => ['datepublish' => true],
                'filter' => [ 'title' => $text ],
                'operator' => [ 'title' => \Bolt\Extension\CND\Library\Services\StorageService::OPERATOR_CONTAINS ],
                // "options" => ["showquery" => true, "showparams" => true]
            ];
            $content = [
                'results' => $this->container['cnd-library.storage']->selectContent($parameters)
            ];
        }
        // VERSION B - Bolt native search
        else {
            $content = $this->container['storage']->searchContent($text, $allowedTypes, null, 20, 0);
        }

        if ($content['results']) {
            foreach ($content['results'] as $entry) {
                $results[] = $this->record2Relation($entry);
            }
        }

        return $results;
    }

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record): Relation {
        $item = new Relation();
        $item->id = $record->contenttype['slug'] . '/' . $record->id;
        $item->type = $record->contenttype['singular_slug'];
        $item->service = $this->key;
        $item->teaser = [
            'title' => $record->get('title'),
            'image' => $this->getImage($record),
            'description' => (string)$record->getExcerpt(200),
            'date' => date('c', strtotime($record->get('datechanged'))),
            'link' => $record->editlink(),
        ];

        return $item;
    }

    protected function record2Item($record): Item {
        $item = new Item();
        $item->id = $record->contenttype['slug'] . '/' . $record->id;
        $item->type = $record->contenttype['singular_slug'];
        $item->service = $this->key;
        $item->object = $record;

        return $item;
    }

    /**
     * @param Content $record
     * @return mixed
     */
    private function getImage($record) {
        // ImageService
        if(isset($record->get('image')['items']) && $record->get('image')['items']) {
            $image = reset($record->get('image')['items']);
        }
        elseif(isset($record->get('teaserimage')['items']) && $record->get('teaserimage')['items']) {
            $image = reset($record->get('teaserimage')['items']);
        }

        // Bolt native image
        else {
            $image = $record->getImage();
        }

        if(!$image) {
            return null;
        }

        // Render via ImageService
        if ($image instanceof \Bolt\Extension\CND\ImageService\Image && $this->container->offsetExists('cnd.image-service.image')) {
            $url = $this->container['cnd.image-service.image']->imageUrl($image, 150, 150, 'fit');
        }

        // Render via Bolt Tumbnails
        else {
            $url = $this->container['twig.runtime.bolt_image']->thumbnail($image, 150, 150, 'r');
        }

        return $url;
    }

    protected function getRecords($relations): array{
        $grouped = [];

        // Collect id's for Bolt and group by contenttype
        foreach($relations as $relation){
            list(,$id) = explode('/', $relation->id);
            $grouped[$relation->type][] = $id;
        }

        $results = [];
        foreach ( array_keys($grouped) as $contentType ) {
            // Retrieve content objects
            $records = $this->container['storage']->getContent($contentType, ['id' => implode(' || ', $grouped[$contentType])]);

            // Nothing found or invalid query
            if (!$records) {
                continue;
            }

            // Convert single object to list of objects
            if (!\is_array($records) && ($records instanceof Content)) {
                $newList = array();
                $newList[$records->id] = $records;
                $records = $newList;
            }

            foreach($records as $record){
                $id = $record->contenttype['slug'] . '/' . $record->id;
                $results[$id] = $record;
            }
        }

        return $results;
    }
}