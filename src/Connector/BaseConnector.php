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
    public function searchRelations($config, $text, $parameters = []): array{
        $results = $this->searchRecords($config, $text, $parameters);
        $items = [];

        // Create item list
        foreach($results as $result){
            $item = $this->record2Item($result);
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
                $updated = $this->record2Relation($results[$relation->id]);
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
                $item = $this->record2Item($results[$relation->id]);
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
    public function fillItems($config, $count, $parameters = [], $exclude = []): array {
        $results = $this->fillRecords($config, $count, $parameters, $exclude);
        $items = [];

        // Create item list
        foreach($results as $result){
            $item = $this->record2Item($result);
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
    abstract protected function searchRecords($config, $text, $parameters = []): array;

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
    abstract protected function fillRecords($config, $count, $parameters = [], $exclude = []): array;

    // ------------------------------------------------------------------------------------------------
    // Utility functions

    /**
     * Replace placeholders '%sample-key%' from array of replacements ['sample-key' => 'sample value']
     * @param $query
     * @param $parameters
     * @return mixed
     */
    protected function applyQueryParameters($query, $parameters){
        $replacements = [];
        array_walk($parameters, function($value, $key) use (&$replacements){
            $replacements['%'.$key.'%'] = $value;
        });

        array_walk_recursive($query, function(&$value, $key) use ($replacements){
            if(isset($replacements[$value])) {
                $value = $replacements[$value];
            }
        });

        return $query;
    }

}