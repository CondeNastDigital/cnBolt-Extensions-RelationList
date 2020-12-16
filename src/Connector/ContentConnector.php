<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Legacy\Content;

class ContentConnector extends BaseConnector {

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function searchRecords($config, $text, $parameters = []): array{
        $results = [];
        $StorageService = false;

        if ($this->container->offsetExists('cnd-library.storage')) {
            $StorageService = $this->container['cnd-library.storage'];
        }
        elseif ($this->container->offsetExists('cnd-basics.storage')) {
            $StorageService = $this->container['cnd-basics.storage'];
        }

        // VERSION A - CND Storage Library
        if($StorageService){

            if(isset($config['query']['filter']['title']) || isset($config['query']['operator']['title']))
                throw new \Exception('RelationList: Query for content connector specifies conflicting title filter or operator');

            // Basic Query
            $query = ($config['query'] ?? []) + [
                'contenttypes' => [],
                'filter' => [],
                'operator' => [],
                'limit' => 20,
                'order' => ['datepublish' => true],
                // "options" => ["showquery" => true, "showparams" => true]
            ];

            // Add search filter
            if(is_numeric($text)){
                $query['filter']['id'] = (int)$text;
                $query['operator']['id'] = get_class($StorageService)::OPERATOR_EQUALS;
            } else {
                $query['filter']['title'] = $text;
                $query['operator']['title'] = get_class($StorageService)::OPERATOR_CONTAINS;
            }

            // Apply parameters
            $parameters = $this->getQueryParameters( $config['defaults'] + $config['custom-fields'], $parameters);
            $query = $this->applyQueryParameters($query, $parameters);

            $content = [
                'results' => $StorageService->selectContent($query)
            ];
        }
        // VERSION B - Bolt native search
        // This version is unable to handle more complex parametrized searches!
        else {
            $allowedTypes = $config['query']['contenttypes'] ?? [];
            $content = $this->container['storage']->searchContent($text, $allowedTypes, null, 20, 0);
        }

        return $content['results'] ?? [];
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
        $item->teaser = [
            'title' => $record->get('title'),
            'image' => $this->getImage($record),
            'description' => (string)$record->getExcerpt(200),
            'date' => date('c', strtotime($record->get('datepublish'))),
            'link' => $record->editlink(),
        ];

        return $item;
    }

    /**
     * @param Content $record
     * @return mixed
     */
    protected function getImage($record) {
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
            try {
                $url = $this->container['cnd.image-service.image']->imageUrl($image, 150, 150, 'fit');
            } catch (\Exception $e) {
                return $this->container['twig.runtime.bolt_image']->thumbnail('unknown', 150, 100, 'crop');
            }
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

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function fillRecords($config, $count, $parameters = [], $exclude = []): array{
        $results = [];

        if ($this->container->offsetExists('cnd-library.storage')) {
            $StorageService = $this->container['cnd-library.storage'];
        }
        elseif ($this->container->offsetExists('cnd-basics.storage')) {
            $StorageService = $this->container['cnd-basics.storage'];
        }
        else {
            throw new \Exception('RelationList: FillService requires either cnd/basics or cnd/library extension');
        }

        if(isset($config['query']['filter']['contentslug']) || isset($config['query']['operator']['contentslug']))
            throw new \Exception('RelationList: FillService query for content connector specifies conflicting contentslug filter or operator');

        // Basic Query
        $query = ($config['query'] ?? []) + [
            'contenttypes' => [],
            'filter' => [],
            'operator' => [],
            'limit' => min($count, 50),
            'order' => ['datepublish' => false],
            // "options" => ["showquery" => true, "showparams" => true]
        ];

        $query['filter']['contentslug'] = $exclude;
        $query['operator']['contentslug'] = 'notin';

        // Apply parameters
        $parameters = $this->getQueryParameters($config['defaults'], $parameters);
        $query = $this->applyQueryParameters($query, $parameters);

        return $StorageService->selectContent($query);
    }
}
