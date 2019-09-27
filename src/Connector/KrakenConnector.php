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

        $privateKeyPath = '../app/config/extensions/'.$config['auth']['key-private'];
        $publicKeyPath  = '../app/config/extensions/'.$config['auth']['key-public'];

        $auth->setPrivateKey($privateKeyPath);
        $auth->setPublicKey($publicKeyPath);

        $this->store = new KrakenService($auth, $config['api'] ?? []);

        parent::__construct($key, $container, $config);
    }

    /**
     * @inheritdoc
     * @throws \CND\KrakenSDK\Exception
     */
    public function searchRelations($config, $text): array{

        $filters = $config['api']['search-filter'] ?? [];
        $allowedTypes = $config['source'] ?? [];
        $results = [];

        $filters += [
            '$text' => ['$search' => $text],
            'type' => ['$in' => $allowedTypes]
        ];
        $order = [
            'control.publishDate' => false
        ];
        // FIXME: $options currently not supported by kraken find api
        $options = [
            'projection' => ['score' => ['$meta' => 'textScore']],
            'sort' => ['score' => ['$meta' => 'textScore']],
        ];

        $result = $this->requestKraken($filters, 20, 0, $order);
        foreach($result as $item) {
            $results[] = $this->record2Relation($item);
        }

        return $results;
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

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record): Relation {
        $item = new Relation();
        $item->id = $record['control']['uid'];
        $item->type = $record['type'];
        $item->service = $this->key;
        $item->teaser = [
            'title' => strtoupper($record['source']['name']).' - '.$record['content']['title'],
            'image' => $this->getImage($record),
            'description' => $record['content']['abstract'],
            'date' => date('c', strtotime($record['control']['publishDate'])),
            'link' => $record['teaser']['url'],
        ];

        return $item;
    }

    protected function record2Item($record): Item {
        $item = new Item();
        $item->id = $record['control']['uid'];
        $item->type = $record['type'];
        $item->service = $this->key;
        $item->object = $record;

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

        $result = $this->store->findBy($filters, 20, 0, []);

        $cache->save($cacheKey, $result, self::TTL);

        return $result;
    }

}