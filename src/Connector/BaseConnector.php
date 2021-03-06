<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\IConnector;
use Bolt\Legacy\Content;

abstract class BaseConnector implements IConnector {

    protected $container;
    protected $config;
    protected $key;

    /**
     * @inheritdoc
     */
    public function __construct($key, Application $container, $config){
        $this->container = $container;
        $this->config = $config;
        $this->key = $key;
    }

    /**
     * @inheritdoc
     */
    public function searchRelations($config, $text): array{
        $results = $this->searchRecords($config, $text);
        $items = [];

        // Create item list
        foreach($results as $result){
            $item = $this->record2Item($result, $this->config['customfields'] ?? []);
            if($item instanceof Item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @inheritdoc
     */
    public function updateRelations($relations){
        $results = $this->getRecords($relations);

        // Update original list and remove objects not found
        foreach($relations as $idx => $relation){

            if(isset($results[$relation->id])){
                $updated = $this->record2Relation($results[$relation->id], $this->config['custom-fields'] ?? []);
                $updated->attributes = $relation->attributes;
                $relations[$idx] = $updated;
            } else {
                unset($relations[$idx]);
            }
        }

        return array_values($relations);
    }

    /**
     * @inheritdoc
     */
    public function getItems($relations): array{
        $results = $this->getRecords($relations);
        $items = [];

        // Create item list and remove objects not found
        foreach($relations as $idx => $relation){
            if(isset($results[$relation->id])){
                $item = $this->record2Item($results[$relation->id], $this->config['customfields'] ?? []);
                if($item instanceof Item) {
                    $item->attributes = $relation->attributes ?? [];
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @inheritdoc
     */
    public function fillItems($config, $count, $exclude = []): array {
        $results = $this->fillRecords($config, $count, $exclude);
        $items = [];

        // Create item list
        foreach($results as $result){
            $item = $this->record2Item($result, $config['customfields'] ?? []);
            if($item instanceof Item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    // ----------------------------------------------------------------------------------------------

    // Need to be overridden for individual data sources

    /**
     * @param mixed $record
     * @return Relation
     */
    abstract protected function record2Relation($record): Relation;

    /**
     * @param mixed $record
     * @return Item
     */
    abstract protected function record2Item($record): Item;

    /**
     * @param array $config
     * @param string $text
     * @param array $parameters
     * @return array
     */
    abstract protected function searchRecords($config, $text): array;

    /**
     * @param Relation[] $relations
     * @return array
     */
    abstract protected function getRecords($relations): array;

    /**
     * @param array $config
     * @param int $count
     * @param array $parameters
     * @param array $exclude
     * @return array
     */
    abstract protected function fillRecords($config, $count, $exclude = []): array;

    // ------------------------------------------------------------------------------------------------
    // Utility functions

    /**
     * Transfers fields and their values from a source array to a target array.
     * Not moved to the Utility Class as the data may need to be adapted to fit the local logic.
     * @param array $fields Array of mappings. The key is the path in the target array, the value if the path in the source array
     * @param $source
     * @param $target
     * @return mixed
     */
    protected function applyCustomFields(array $fields, $source, &$target) {
        // get all the string paths
        foreach($fields as $field => $path) {
            $path  = explode('.', $path);
            $field = explode('.', $field);
            $last  = array_pop($field);
            $toSet = &$target;

            // Read the Value from the source array
            $value = array_reduce($path, function($array, $key) {
                return is_array($array) && isset($array[$key]) ? $array[$key] : false;
            }, $source);

            // Set the Value into the Target array
            $toSet = array_reduce($field, function(&$array, $key) {
                if(!array_key_exists($key, $array)) {
                    $array[$key] = [];
                }
                $ret = &$array[$key];
                return $ret;
            }, $toSet);
            $toSet[$last] = $value;
        }

        return $target;
    }



}