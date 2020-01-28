<?php

namespace Bolt\Extension\CND\RelationList\Service;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\IConnector;

class FillService {

    /** @var array Static array of already shown record keys */
    protected static $alreadyShown = [];

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
     * Add one or more Items to the exclusion list
     * @param Item[]|Item $items
     * @param string $bucket
     */
    public function addShownItems($items, $bucket = 'default'){

        $items = \is_array($items) ? $items : [$items];

        self::$alreadyShown[$bucket] = self::$alreadyShown[$bucket] ?? [];

        foreach($items as $item){
            $key = $item->id;
            $service = $item->service;

            self::$alreadyShown[$bucket][$service] = self::$alreadyShown[$bucket][$service] ?? [];
            self::$alreadyShown[$bucket][$service][] = $key;
        }
    }

    /**
     * Add one or more keys to the exclusion list
     * @param string[]|string $keys
     * @param string $service
     * @param string $bucket
     */
    public function addShownKeys($keys, $service, $bucket = 'default'){

        $keys = is_array($keys) ? $keys : [$keys];

        self::$alreadyShown[$bucket] = self::$alreadyShown[$bucket] ?? [];
        self::$alreadyShown[$bucket][$service] = self::$alreadyShown[$bucket][$service] ?? [];

        self::$alreadyShown[$bucket][$service] = array_merge(self::$alreadyShown[$bucket][$service], $keys);
    }

    /**
     * Collect items from a pool and merge/sort them with given items
     * @param string $poolKey              name of pool from pools configuration
     * @param int $count                   number of items to return in total
     * @param array $parameters            parameters to insert into search query (see pool configuration)
     * @param Item[] $fixedItems           array of fixed items to merge
     * @param bool|string $positionField   fixed items contain an attribute with a position number
     * @return Item[]
     * @throws \Exception
     */
    public function getItems($poolKey, $count, $parameters = [], $fixedItems = [], $bucket = 'default'){

        $pool = $this->config['pools'][$poolKey] ?? false;
        if(!$pool) {
            throw new \Exception('Pool configuration for field "' . $poolKey . '" invalid');
        }

        // add fixed items to shown
        $this->addShownItems($fixedItems, $bucket);

        $results = [];
        // Load additional items from connectors
        if($count > count($fixedItems)) {
            $resultsByConnector = [];
            foreach ($pool['sources'] as $sourceKey => $source) {
                $connector = $this->connectors[$source['connector'] ?? false] ?? false;

                if (!$connector) {
                    throw new \Exception('Connector configuration for pool "' . $poolKey . '" and source "' . $sourceKey . '" invalid');
                }

                $exclusion = self::$alreadyShown[$bucket][$sourceKey] ?? [];

                try {
                    $resultsByConnector[] = $connector->fillItems($source, $count, $parameters, $exclusion);
                } catch (\Exception $e) {
                    $this->container['logger']->error('RelationFill - Exception in connector '.$sourceKey, ['exception' => $e]);
                }

            }
            // merge all sub arrays (by split by connector) into one large array
            $results = array_merge([], ...$resultsByConnector);
            unset($resultsByConnector);
        }

        // merge all records into one array and sort
        $sortKey = $pool['order'] ?? '!date';
        $sortDir = strpos($sortKey, '!') !== 0;

        if($sortKey){
            $sorted = [];
            $sortKey = trim($sortKey, '!');

            foreach($results as $item){
                $key = $item->teaser[$sortKey].$item->id;
                $sorted[$key] = $item;
            }
            ksort($sorted, SORT_STRING);

            if(!$sortDir){
                $sorted = array_reverse($sorted);
            }

            $results = array_values($sorted);
        }

        // merge with positioned items (Use reversed fixed items for injection otherwise they will push each other around!)
        $positionKey = $pool['position'] ?? false;
        foreach(array_reverse($fixedItems) as $item){
            $position = $positionKey ? ($item->attributes[$positionKey] ?? 0) : 0;
            array_splice($results, $position, 0, [$item]);
        }

        // cut to requested size
        $results = \array_slice($results, 0, $count);
        return $results;
    }

}