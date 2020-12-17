<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;

interface IConnector {

    /**
     * IConnector constructor.
     * @param string $key
     * @param Application $container
     * @param array $config
     */
    public function __construct($key, Application $container, $config);

    /**
     * search for text and return matching relation objects
     * @param array $config
     * @param string $text
     * @param array $parameters
     * @return Relation[]
     */
    public function searchRelations($config, $text);

    /**
     * Update teaser info on requested relation objects
     * @param array $config
     * @param Relation[] $items
     * @return Relation[]
     */
    public function updateRelations($items);

    /**
     * Return full items for relation obbjects
     * @param Relation[] $relations
     * @return Item[]
     */
    public function getItems($relations);

    /**
     * Return a list of items matching a query
     * @param array $config
     * @param int $count
     * @param array $parameters
     * @param array $exclude
     * @return Item[]
     */
    public function fillItems($config, $count, $exclude = []);
}