<?php

namespace Bolt\Extension\CND\RelationList\Service;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Base;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\IConnector;
use Bolt\Extension\CND\RelationList\Utility\ConfigUtility;

class RelationService {

    /* @var Application $container */
    protected $container;
    /* @var array $config */
    protected $config = [];
    /* @var IConnector[] $connectors */
    protected $connectors = array();

    public function __construct(Application $container, $config, array $connectors){
        $this->container = $container;
        $this->config = $config;
        $this->connectors = $connectors;
    }

    /**
     * @param string $text
     * @param string $contenttype
     * @param string $field
     * @param string|false $subfield
     * @param array $parameters
     * @return Relation[]
     * @throws \Exception
     */
    public function searchRelations($text, $contenttype, $field, $subfield = false, $parameters = []): array {

        $fieldConfig = $this->getFieldConfig($contenttype, $field, $subfield);

        $pool = $this->getPool($fieldConfig);
        if(!$pool) {
            throw new \Exception('Pool configuration for field "' . $field . '" invalid');
        }

        $results = [];
        foreach($pool['sources'] as $sourceKey => $source) {
            $connector = $this->connectors[$source['connector'] ?? false] ?? false;

            if(!$connector) {
                throw new \Exception('Connector configuration for pool with source "' . $sourceKey . '" invalid');
            }

            // Merge the Defaults
            $defaults = $source['defaults'] ?? [];

            $placeholders = ConfigUtility::getQueryParameters(
                $defaults,
                $parameters,
                $source['customfields'] ?? []
            );
            $source = ConfigUtility::applyQueryParameters($source, $placeholders);

            $results[] =  $connector->searchRelations($source, $text);
        }
        return $results ? array_merge(...$results) : [];
    }

    /**
     * @param $type
     * @param $slug
     * @param $search
     * @return array
     * @throws \Exception
     */
    public function autocomplete($type, $slug, $search): array {

        $items = [];
        $results = [];
        $count = 0;

        if ($this->container->offsetExists('cnd-library.storage')) {
            $StorageService = $this->container['cnd-library.storage'];
        }
        elseif ($this->container->offsetExists('cnd-basics.storage')) {
            $StorageService = $this->container['cnd-basics.storage'];
        }
        else {
            throw new \Exception('RelationList requires either cnd/basics or cnd/library extension');
        }

        switch ($type){
            case 'taxonomies':
            case 'taxonomy':
                $taxonomies = $StorageService->selectTaxonomy(['name'=>[$slug]]);

                foreach ($taxonomies as $key => $value){

                    // checks if the search pattern is found in the array

                    if(!$search || stripos($value['name'], $search) !== false) {
                        $items[] = [
                            "value" => $value['name'] ?? false,
                            "label" => $value['name'] ?? false,
                            "info" => isset($value['count']) ? (int) $value['count'] : null
                        ];
                        $count += $value['count'] ?? 0;
                    }

                }

                $results['items'] = $items;
                $results['stats']['total'] = $count;

                break;
        }

        return $results;


    }

    /**
     * Update a list of Relation objects
     * @param Relation[] $relations
     * @return Relation[]
     */
    public function updateRelations($relations): array{

        // Split items by connector
        $itemsByConnector = [];
        foreach($relations as $relation){
            $itemsByConnector[$relation->service][] = $relation;
        }

        // get relations from connectors
        $results = [];
        foreach($this->connectors as $key => $connector) {
            if($itemsByConnector[$key] ?? false) {
                $results[] = $connector->updateRelations($itemsByConnector[$key]);
            }
        }
        $results = $results ? array_merge(...$results) : [];

        // Re-sort into original order
        return $this->orderByList($relations, $results);
    }

    /**
     * Get full items for given relations
     * @param Relation[] $relations
     * @return Item[]
     */
    public function getItems($relations): array{
        // Split items by connector
       
        $itemsByConnector = [];
        foreach($relations as $relation){
            $itemsByConnector[$relation->service][] = $relation;
        }
        
        /* @var IConnector $connector */
        $results = [];
        echo "<pre>"; 
        print_r($relations);
        foreach($this->connectors as $key => $connector) {
            print_r($connector);
            if($itemsByConnector[$key] ?? false) {

                try {
                    $results[] = $connector->getItems($itemsByConnector[$key]);
                } catch (\Exception $e) {
                    $this->container['logger']->error('RelationList - Exception in connector '.$key, ['exception' => $e]);
                }

            }
        }
        echo "</pre>";
        
        
        $results = $results ? array_merge(...$results) : [];
        echo "Result";
        print_r($results);
        
        // Re-sort into original order
        return $this->orderByList($relations, $results);
    }

    /**
     * Return configuration for a field
     * @param string $contenttype
     * @param string $field
     * @param string|false $subfield
     * @return array|false
     */
    protected function getFieldConfig($contenttype, $field, $subfield = false){

        $contenttype = $this->container['storage']->getContentType($contenttype);
        if(!$contenttype) {
            return false;
        }

        $fieldDefinition = $contenttype['fields'][$field] ?? [];

        // Is it a StructuredContentField? Then we need to dive into the respective subfield/block
        $type = $fieldDefinition['type'] ?? false;
        if($fieldDefinition && $subfield && $type === 'structuredcontentfield'){
            $fieldDefinition = $fieldDefinition['extend'][$subfield] ?? false;
        }

        return $this->parseConfig($fieldDefinition);
    }

    /**
     * @param array $data
     * @return array
     */
    public function parseConfig(array $data){
        return $data + [
            'attributes' => [],
            'globals' =>  [],
            'min' => 0,
            'max' => 20,
            'pool' => false
        ];
    }

    /**
     * @param Base[] $ordered
     * @param Base[] $unordered
     * @return Base[]
     */
    protected function orderByList($ordered, $unordered): array {
        $orderedKeys = [];
        foreach ($ordered as $item){
            $orderedKeys[$item->service.'/'.$item->id] = $item;
        }

        $unorderedKeys = [];
        foreach ($unordered as $item){
            $unorderedKeys[$item->service.'/'.$item->id] = $item;
        }

        return array_values(array_merge(array_intersect_key($orderedKeys, $unorderedKeys), $unorderedKeys));
    }

    protected function getPool($fieldConfig){

        $poolkey = $fieldConfig['pool']['search'] // seperate pools per type
                ?? $fieldConfig['pool']           // one pool for everything
                ?? false;                         // no pool found

        return $this->config['pools'][$poolkey] ?? false;
    }

}
