<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Legacy\Content;
use CND\KrakenSDK\Services\AuthService;
use CND\KrakenSDK\Services\KrakenService;

class KrakenConnector extends BaseConnector {

    /* @var KrakenService $store */
    protected $store;

    const TTL = 60;

    /**
     * KrakenConnector constructor.
     * @param $key
     * @param Application $container
     * @param $config
     * @throws \Exception
     */
    public function __construct($key, Application $container, $config){

        if(!class_exists('CND\\KrakenSDK\\Services\\KrakenService', true))
            throw new \Exception('Required composer package "cnd/kraken-sdk" not available');

        $auth = new AuthService();

        $privateKeyPath = $container['resources']->getPath('config').'/extensions/'.$config['auth']['key-private'];
        $publicKeyPath  = $container['resources']->getPath('config').'/extensions/'.$config['auth']['key-public'];

        $auth->setPrivateKey($privateKeyPath);
        $auth->setPublicKey($publicKeyPath);

        $this->store = new KrakenService($auth, $config['api'] ?? []);

        parent::__construct($key, $container, $config);
    }

    /**
     * @inheritdoc
     * @throws \CND\KrakenSDK\Exception
     * @throws \Exception
     */
    public function searchRecords($config, $text): array{

        // Basic Query
        $query = ($config['query'] ?? []) + [
            'filter' => [],
            'limit' => 20,
            'offset' => 0,
            'order' => [],// ['control.publishDate' => true],
        ];

        // Add search filter
        $query['filter']['$text'] = ['$search' => $text];

        return $this->requestKraken($query['filter'], $query['limit'], $query['offset'], $query['order']);
    }


    /**
     * @param Relation[] $relations
     * @return array
     * @throws \CND\KrakenSDK\Exception
     */
    protected function getRecords($relations): array{
        // Kraken currently does not support $in operator with UID's as it can not auto-convert them to MongoObjectIDs
        $ids = [];
        foreach($relations as $relation){
            $ids[] = ['control.uid' => $relation->id];
        }
        $filters = [ '$or' => $ids ];

        $result = $this->requestKraken($filters, 20, 0, []);
        foreach($result as $item) {
            $results[$item['control']['uid']] = $item;
        }

        return $results ?? [];
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function fillRecords($config, $count, $exclude = []): array {
        // Basic Query
        $query = ($config['query'] ?? []) + [
            'filter' => [],
            'limit' => min($count, 50),
            'offset' => 0,
            'order' => ['control.publishDate' => false],
        ];

        $query['filter']['control.uid'] = ['$nin' => $exclude];

        return $this->requestKraken($query['filter'], $query['limit'], $query['offset'], $query['order']) ?? [];
    }

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record, $customFields=[]): Relation {
        $item = new Relation();
        $item->id = $record['control']['uid'];
        $item->type = $record['type'];
        $item->service = $this->key;
        $item->teaser = [
            'title'       => strtoupper($record['source']['name']).' - '.$record['content']['title'],
            'image'       => $this->getImage($record),
            'description' => $record['content']['abstract'],
            'date'        => date('c', strtotime($record['control']['publishDate'])),
            'link'        => $record['teaser']['url']
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    protected function record2Item($record, $customFields=[]): Item {
        $item = new Item();
        $item->id = $record['control']['uid'];
        $item->type = $record['type'];
        $item->service = $this->key;
        $item->object = $record;
        $item->teaser = [
            'title'       => strtoupper($record['source']['name']).' - '.$record['content']['title'],
            'image'       => $this->getImage($record),
            'description' => $record['content']['abstract'],
            'date'        => date('c', strtotime($record['control']['publishDate'])),
            'link'        => $record['teaser']['url']
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    /**
     * Get largest image for a desired aspect ration
     * @param Content $record
     * @param float $target
     * @return mixed
     */
    protected function getImage($record, $target = 1.5) {

        $variants = $record['teaser']['media']['image'] ?? [];

        $bestImage = false;
        $bestDiff = false;

        foreach($variants as $variant){

            if(!isset($variant["url"]) || !$variant["url"])
                continue;

            $diff = abs($target - $variant["aspectRatio"]);

            if($bestDiff === false || $diff < $bestDiff){
                $bestImage = $variant;
                $bestDiff = $diff;
            }

            return $bestImage['url'] ?? false;
        }

        return false;
    }

    /**
     * @param $filters
     * @param int $limit
     * @param int $offset
     * @param array $order
     * @return array|mixed
     * @throws \CND\KrakenSDK\Exception
     */
    protected function requestKraken($filters, $limit = 20, $offset = 0, $order = []){
        $cacheKey = md5(serialize([$filters,$limit,$offset,$order]));

        /* @var \Doctrine\Common\Cache\Cache $cache */
        $cache = $this->container['cache'];

        if($cache->contains($cacheKey))
            return $cache->fetch($cacheKey);

        $result = $this->store->findBy($filters, $limit, $offset, $order);

        $cache->save($cacheKey, $result, self::TTL);

        return $result;
    }
}