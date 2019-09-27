<?php

namespace Bolt\Extension\CND\RelationList\Service;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Base;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\IConnector;

class RelationService {

    /* @var Application $container */
    protected $container;
    /* @var array $config */
    protected $config = [];
    /* @var IConnector[] $connectors */
    protected $connectors = array();
    /* @var LegacyService $legacy */
    protected $legacy;

    public function __construct(Application $container, $config, LegacyService $legacy){
        $this->container = $container;
        $this->config = $config;
        $this->legacy = $legacy;

        // Init service connectors
        foreach($this->config['connectors'] as $key => $connectorConfig){

            if(!isset($connectorConfig['class'])) {
                continue;
            }

            if(!class_exists($connectorConfig['class'])) {
                continue;
            }

            $connector = new $connectorConfig['class']($key, $container, $connectorConfig);
            if(!$connector instanceof IConnector) {
                continue;
            }

            $this->connectors[$key] = $connector;
        }
    }

    /**
     * Correct data from older versions of relationlist to new data
     * @param array|string $data
     * @return array
     */
    public function prepare($data): array {
        return $this->legacy->convertLegacyValue($data);
    }

    /**
     * @param string $text
     * @param string $contenttype
     * @param string $field
     * @param string|false $subfield
     * @return Relation[]
     */
    public function searchRelations($text, $contenttype, $field, $subfield = false): array {

        $config = $this->getFieldConfig($contenttype, $field, $subfield);

        $results = [];
        foreach($this->connectors as $key => $connector) {
            $fieldConfig = $this->getConnectorFieldConfig($key, $config);
            $results[] =  $connector->searchRelations($fieldConfig, $text);
        }
        return $results ? array_merge(...$results) : [];
    }

    /**
     * Update a list of Relation objects
     * @param Relation[] $relations
     * @return Relation[]
     */
    public function updateRelations($relations): array{

        // Split by service
        $connectors = [];
        foreach($relations as $relation){
            $connectors[$relation->service][] = $relation;
        }

        // get relations from connectors
        $results = [];
        foreach($this->connectors as $key => $connector) {
            if($connectors[$key] ?? false) {
                $results[] = $connector->updateRelations($connectors[$key]);
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

        // Split by service
        $connectors = [];
        foreach($relations as $relation){
            $connectors[$relation->service][] = $relation;
        }

        /* @var IConnector $connector */
        $results = [];
        foreach($this->connectors as $key => $connector) {
            if($connectors[$key] ?? false) {
                $results[] = $connector->getItems($connectors[$key]);
            }
        }
        $results = $results ? array_merge(...$results) : [];

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

        // is it a legacy style configuration?
        if(isset($fieldDefinition['options']) || isset($fieldDefinition['allowed-types'])) {
            $fieldDefinition = $this->legacy->getLegacyFieldConfig($fieldDefinition);
        }

        if(!$fieldDefinition) {
            return false;
        }

        return $fieldDefinition + [
            'attributes' => [],
            'globals' =>  [],
            'min' => 0,
            'max' => 50,
            'sources' => []
        ];
    }

    /**
     * Reduce the field config containing all sources to only containing one source
     * @param string $source
     * @param array $config
     * @return array
     */
    protected function getConnectorFieldConfig($source, $config): array{

        $config['source'] = $config['sources'][$source] ?? [];

        unset($config['sources']);

        return $config;
    }

    /**
     * @param Base[] $ordered
     * @param Base[] $unordered
     * @return Base[]
     */
    protected function orderByList($ordered, $unordered){
        $orderedKeys = [];
        foreach ($ordered as $item){
            $orderedKeys[$item->service."/".$item->id] = $item;
        }

        $unorderedKeys = [];
        foreach ($unordered as $item){
            $unorderedKeys[$item->service."/".$item->id] = $item;
        }

        return array_values(array_merge(array_intersect_key($orderedKeys, $unorderedKeys), $unorderedKeys));
    }

}