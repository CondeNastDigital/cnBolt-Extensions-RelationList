<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Legacy\Content;
use CND\KrakenSDK\Services\AuthService;
use CND\KrakenSDK\Services\KrakenService;

class TipserProductConnector extends BaseConnector {

    const SERVICE_NAME = 'tipser';
    const TIPSER_URL_PROD = 'https://t3-prod-api.tipser.com/v4/';
    const TIPSER_URL_STAGING = 'https://t3-stage-api.tipser.com/v4/';
    const TTL = 60;

    protected $apiKey = null;
    protected $env = 'staging';


    /**
     * KrakenConnector constructor.
     * @param $key
     * @param Application $container
     * @param $config
     * @throws \Exception
     */
    public function __construct($key, Application $container, $config){
        parent::__construct($key, $container, $config);

        $api = $config['api'] ?? [];
        $this->apiKey = $api['key'] ?? '';
        $this->env = $api['evn'] ?? 'staging';

    }

    /**
     * @inheritdoc
     * @throws \CND\KrakenSDK\Exception
     * @throws \Exception
     */
    public function searchRecords($config, $text): array{

        // Basic Query
        $query = ($config['query'] ?? []) + [
                'query' => $text,
                'limit' => 20,
                'offset' => 0,
                'order' => 'name',
            ];

        return $this->requestTipser('products', $query);
    }


    /**
     * @param Relation[] $relations
     * @return array
     * @throws \CND\KrakenSDK\Exception
     */
    protected function getRecords($relations): array{
        return $relations;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function fillRecords($config, $count, $exclude = []): array {
        return [];
    }

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record, $customFields=[]): Relation {
        $item = new Relation();
        $item->id = $record['id'];
        $item->type = 'products';
        $item->service = self::SERVICE_NAME;
        $item->teaser = [
            'title'       => strtoupper($item->service).' - '.$record['name'] ?? '',
            'image'       => $this->getImage($record),
            'description' => $record['description'] ?? '',
            'date'        => null,
            'link'        => '#'
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    protected function record2Item($record, $customFields=[]): Item {
        dump($record);
        $item = new Item();
        return $item;
        $item->id = $record['id'];
        $item->type = 'product';
        $item->service = self::SERVICE_NAME;
        $item->object = $record;
        $item->teaser = [
            'title'       => strtoupper($item->service).' - '.$record['name'] ?? '',
            'image'       => $this->getImage($record),
            'description' => $record['description'],
            'date'        => null,
            'link'        => '#'
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

        $variants = $record['images'] ?? [];

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
    protected function requestTipser($endpoint, $query){

        $url  = $this->env === 'production' ? self::TIPSER_URL_PROD : self::TIPSER_URL_STAGING;
        $query['apiKey'] = $this->apiKey;

        $data = http_build_query($query);

        if(!$url) return false;

        $url = $url.$endpoint.'?'.$data;

        // Check Cache
        $hash = md5($url);
        if($this->container["cache"]->contains($hash))
            return $this->container["cache"]->fetch($hash);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0 );

        $output = curl_exec($ch);

        if(curl_error($ch))
            return $this->container['debug'] ? curl_error($ch) : false;

        curl_close($ch);

        $result = json_decode($output, true);

        $this->container["cache"]->save($hash, $result, self::TTL);

        return $result;
    }
}