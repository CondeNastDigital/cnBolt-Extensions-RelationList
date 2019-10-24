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
    abstract public function searchRelations($config, $text): array;

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
                $item->attributes = $relation->attributes ?? [];
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
     * @param Relation[] $relations
     * @return array
     */
    abstract protected function getRecords($relations): array;
}