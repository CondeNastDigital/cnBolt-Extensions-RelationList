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
            $item = $this->record2Item($result, $this->config['custom-fields'] ?? []);
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
                $item = $this->record2Item($results[$relation->id], $this->config['custom-fields'] ?? []);
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
            $item = $this->record2Item($result, $config['custom-fields'] ?? []);
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
    protected function applyCustomFields($fields, $source, &$target) {
        // get all the strign paths
        foreach($fields as $field => $path) {
            $path  = explode('.', $path);
            $field = explode('.', $field);
            $last  = array_pop($field);
            $toSet = &$target;
            $value = array_reduce($path, function($array, $key) {
                return is_array($array) && isset($array[$key]) ? $array[$key] : false;
            }, $source);
            $toSet = array_reduce($field, function(&$array, $key) {
                if(!array_key_exists($key, $array)) {
                    $array[$key] = [];
                }
                $ret = &$array[$key];
                return $ret;
            }, $toSet);
            $toSet[$last] = $value;
        }
    }
    /**
     * Replace search > replacement from array of replacements
     * @param $query
     * @param $parameters
     * @return mixed
     */
    protected function applyQueryParameters($query, $replacements){
        $path = [&$query]; // Helper variable for recursive replacements in below loop
        $i = 0;
        while($i<count($path) && $i<10000) {
            $current = &$path[$i++];
            foreach($current as $key => &$val) {
                // Replace a key placeholder in $query
                if(array_key_exists($key, $replacements)) {
                    $current[$replacements[$key]] = $val;
                    unset($current[$key]);
                }
                // Replace a value placeholder in $query
                if(is_string($val) && array_key_exists($val,$replacements)) {
                    $val = $replacements[$val];
                }
                // Add to the process $path $path
                if(is_array($val)) {
                    $path[] = &$val;
                }
            }
        }
        return $query;
    }

    /**
     * Generate a list of replacements from given and default values
     * @param $defaults
     * @param $parameters
     * @return array
     */
    protected function getQueryParameters($defaults, $parameters, $customfields = []): array {
        // Remove any empty strings from given parameters. These should fallback to defaults.
        // (Reason: Empty values from Input-Fields in forms have empty strings when nothing is specified instead of false/null)
        $filtered = array_filter($parameters, function($value,$key) {
            return (bool)$value;
        }, ARRAY_FILTER_USE_BOTH);
        $parameters = $filtered + $defaults;
        // Prepare replacements array with proper key/values for string replacement
        array_walk($parameters, function($value, $key) use (&$replacements, $customfields){
            // Syntax 1 - '%<key>%' - Normale values from parameters (relationlist global attrributes) array
            $replacements['%'.$key.'%'] = $value;
            // Syntax 2 - '%customfields%<key>%' - Values from $customfields (mapping of custom fields in teaser object)
            $mapped = is_string($value) && isset($customfields[$value]) ? $customfields[$value] : null;
            $replacements['%customfields%'.$key.'%'] = $mapped;
        });
        return $replacements;
    }

}