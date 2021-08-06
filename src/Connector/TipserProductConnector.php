<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Legacy\Content;
use CND\KrakenSDK\Services\AuthService;
use CND\KrakenSDK\Services\KrakenService;

class TipserProductConnector extends BaseConnector {

    const TIPSER_URL_PROD = 'https://t3-prod-api.tipser.com/';
    const TIPSER_URL_STAGING = 'https://t3-stage-api.tipser.com/';
    const TTL_TOKEN = 3600 * 24 * 7; // 7 days cache for auth token
    const TTL_DATA_FALLBACK = 3600* 24 * 7 * 4; // A Month
    const TTL_DATA = 60 * 10; // 10 minutes cache for product data

    protected $config = [];
    protected $endpoint = false;

    protected static $defaults = [
        'api' => [
            'market'   => 'de',       // Tipser market code
            'key'      => false,      // api key
            'env'      => 'staging',  // use 'staging' or 'production' api endpoint of tipser
            'user'     => false,      // user to connect to pos api to do complex filter calls
            'password' => false,      // password to connect to pos api to do complex filter calls
            'posId'    => false,      // Shop POS id
        ]
    ];


    /**
     * KrakenConnector constructor.
     * @param $key
     * @param Application $container
     * @param $config
     * @throws \Exception
     */
    public function __construct($key, Application $container, $config){
        parent::__construct($key, $container, $config);

        $this->config = array_replace_recursive(self::$defaults, $config);
        $this->endpoint  = $this->config['api']['env'] === 'production' ? self::TIPSER_URL_PROD : self::TIPSER_URL_STAGING;
    }

    /**
     * @inheritdoc
     * @throws \CND\KrakenSDK\Exception
     * @throws \Exception
     */
    public function searchRecords($config, $text): array{

        // ID Search
        if(preg_match('/^[a-f\d]{24}$/i', $text)) {
            $found = $this->requestTipser('v4/products/'.$text, [
                'market' => $this->config['api']['market'],
                'apiKey' => $this->config['api']['key'],
            ]);
            return $found ? [$found]: [];
        }

        // Free Text Search
        $query = ($config['query'] ?? []) + [
            'query'  => $text,
            'limit'  => 20,
            'offset' => 0,
            'order'  => 'name',
            'market' => $this->config['api']['market'],
            'apiKey' => $this->config['api']['key'],
        ];

        return $this->requestTipser('v4/products', $query, 'query', false, 'products') ?: [];
    }

    /**
     * This function uses an export endpoint of tipser, to save requests.
     * However it requires a market!!!
     * @param Relation[] $relations
     * @return array
     * @throws \CND\KrakenSDK\Exception
     */
    protected function getRecords($relations): array{

        $ids = [];
        $result = [];

        foreach ($relations as $relation) {
            $ids[] = $relation->id;
        }

        $ids = implode(',', $ids);
        $products = $this->requestTipser('v4/export/products', [
            'productIds' => $ids,
            'market' => $this->config['api']['market'],
            'apiKey' => $this->config['api']['key'],
        ]) ?: [];

        foreach ($products as $key => $product) {
            $result[$product['id']] = $product;
        }

        return $result;
    }

    /**
     * select products via Tipser Rest Api
     * @see https://developers.tipser.com/rest-api
     * @inheritdoc
     * @throws \Exception
     */
    protected function fillRecords($config, $count, $exclude = []): array {

        $products = [];

        $mode = $config['fill']['mode'] ?? 'similar';
        switch ($mode) {

            // Select products similar to given product id
            case 'similar':
                $productId = $config['fill']['productid'] ?? false;
                if (!preg_match('/^[a-f\d]{24}$/i', $productId))
                    break;

                $products = $this->requestTipser('v4/products/' . $productId . '/similar', [
                        'onlyAvailable' => ($config['fill']['onlyAvailable'] ?? true) ? 'true' : 'false', // The Booleans are converted to digits in http_build_query and tipser wants strings
                        'market' => $this->config['api']['market'],
                        'apiKey' => $this->config['api']['key'],
                        // Currently no support for 'onlyOnSale' with this endpoint :(
                    ]) ?: [];
                break;

            // Select products inside given collection id
            case 'collection':
                $collectionId = $config['fill']['collectionid'] ?? false;
                if (!preg_match('/^[a-f\d]{24}$/i', $collectionId))
                    break;

                $items = $this->requestTipser('v4/collections/' . $collectionId, [
                        'market' => $this->config['api']['market'],
                        'apiKey' => $this->config['api']['key'],
                    ], 'items') ?: [];

                foreach($items as $item){
                    $products[] = $item['product'];
                }
                break;

            // Get filtered products via pos api
            case 'products':
                $query = $config['fill'] + [
                    'filters' => [],
                    'order' => [
                        'name' => 'relevance',
                        'direction' => 'ASC'
                    ],
                    'limit' => $count,
                    'market' => $this->config['api']['market'],
                ];
                $query = array_intersect_key($query, array_flip(['filters', 'order', 'query', 'market', 'limit']));
                $query['filters'] = array_intersect_key($query['filters'] ?? [], array_flip(['brands', 'genders', 'priceTo', 'categoryIds', 'onlyAvailable', 'onlyOnSale']));
                $products = $this->requestMultiTipser('v5/pos/products', $query, 'json', true, 'products') ?: [];
                break;

            // Get all products of a market
            case 'all':
                $products = $this->requestTipser('v4/export/products', [
                        'limit' => $count,
                        'market' => $this->config['api']['market'],
                        'apiKey' => $this->config['api']['key'],
                    ]) ?: [];
                break;

            default:
                throw new \Exception('TipserConnector: invalid fill mode configured');
        }

        return $products;
    }

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record, $customFields=[]): Relation {
        $record = $this->cleanTipserRecord($record);

        $item = new Relation();
        $item->id = $record['id'];
        $item->type = 'products';
        $item->service = $this->key;
        $item->teaser = [
            'title'       => $record['name'] ?? $record['title'] ?? '',
            'image'       => $this->getImage($record),
            'description' => $record['description'] ?? null,
            'date'        => $record['lastUpdateDate'] ?? null,
            'link'        => '#'
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    protected function record2Item($record, $customFields=[]): Item {
        $record = $this->cleanTipserRecord($record);

        $item = new Item();
        $item->id = $record['id'] ?? '';
        $item->type = 'product';
        $item->service = $this->key;
        $item->object = $record + ['type' => 'tipser'];
        $item->teaser = [
            'title'       => $record['name'] ?? $record['title'] ?? '',
            'image'       => $this->getImage($record),
            'description' => $record['description'] ?? null,
            'date'        => $record['lastUpdateDate'] ?? null,
            'link'        => '#',
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    /**
     * @param $record
     * @return mixed
     */
    protected function cleanTipserRecord($record): array {
        $record['description'] = strip_tags($record['description'] ?? '');
        $record['title']       = strip_tags($record['title'] ?? '');

        return $record;
    }

    /**
     * Get largest image for a desired aspect ration
     * @param Content $record
     * @param float $target
     * @return mixed
     */
    protected function getImage($record, $target = '450x') {
        $variants = $record['images'] ?? [];
        return reset($variants)[$target] ?? reset($variants)['original'] ?? false;
    }

    /**
     * A wrapper for requestTipser that split's request into multiple calls to get around Tipser's max limit of 100 items
     * @param $endpoint
     * @param array $query
     * @param string $mode
     * @param false $useToken
     * @param false $resultPath
     * @return array
     * @throws \Exception
     */
    protected function requestMultiTipser($endpoint, $query=[], $mode = 'query', $useToken = false, $resultPath = false){

        $MAXLIMIT = 100;
        $MAXTOTAL = 1000;
        $MAXCALLS = 20;

        $moreAvailable = true;
        $limit = min($MAXTOTAL, $query['limit'] ?? $MAXLIMIT);
        $call = 0;
        $products = [];

        while($call < $MAXCALLS && $moreAvailable && count($products) < $limit){
            $batchLimit = min($MAXLIMIT, $limit - count($products));
            $batchOffset = $call * $MAXLIMIT;

            $query = [
                'limit' => $batchLimit,
                'skip' => $batchOffset,
            ] + $query;

            $batchProducts = $this->requestTipser($endpoint, $query, $mode, $useToken, $resultPath) ?: [];
            $moreAvailable = count($batchProducts) >= $MAXLIMIT;
            $call ++;

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $products = array_merge($products, $batchProducts);
        }

        return $products;
    }

    /**
     * @param $endpoint
     * @param array $query
     * @param string $mode
     * @param bool $useToken
     * @param bool $resultPath
     * @return array|mixed
     * @throws \Exception
     */
    protected function requestTipser($endpoint, $query=[], $mode = 'query', $useToken = false, $resultPath = false){

        $result = false;
        $hash = md5($endpoint.serialize($query));
        $url = $this->endpoint.$endpoint;
        $headers = [];

        // Check Cache
        $hasCache   = $this->container["cache"]->contains($hash) && $this->container["cache"]->contains($hash.'.expires');
        $cachedData = $hasCache ? $this->container["cache"]->fetch($hash) : false;
        $isToExpire = time() - $this->container["cache"]->fetch($hash.'.expires') >= self::TTL_DATA;

        if($hasCache && !$isToExpire ) {
            $this->container['logger']->debug('Tipser request using cache');
            return $cachedData;
        }

        try {

            if ($useToken) {
                $token = $this->getAuthToken();
                $headers[] = 'Authorization: Bearer ' . $token;
            }

            switch ($mode) {
                case 'query':
                    $url .= '?' . http_build_query($query);
                    $result = $this->sendCurl($url, '', $headers, 'GET');
                    break;

                case 'json':
                    $body = json_encode($query);
                    $result = $this->sendCurl($url, $body, $headers, 'POST');
                    break;
            }

        } catch (\Exception $e) {
            $this->container['logger']->error('Tipser request - connection/parse error: '.$e->getMessage());
        }

        // Return the last known working cached Result
        if (!$result || $result && ($result['error'] ?? false)) {
            $this->container['logger']->warn('Tipser - possibly expired data retured, because of an error in Tipser response.');
            return $cachedData;
        }

        if($resultPath){
            $result = $result[$resultPath] ?? [];
        }

        $this->container['logger']->debug('Tipser request successfull');
        $this->container["cache"]->save($hash, $result, self::TTL_DATA_FALLBACK);
        $this->container["cache"]->save($hash.'.expires', time() + self::TTL_DATA, self::TTL_TOKEN);

        return $result;
    }

    /**
     * Process according to tipser devs:
     * 1 - POST user/pw to https://t3-dev-api.tipser.com/v4/auth as json in body {"password":"xxx","email":"xxx"}
     * 2 - Take token from response and PUT to https://t3-dev-api.tipser.com/v4/auth as header token
     * 3 - Take token from response and be happy
     * @return false|string
     */
    protected function getAuthToken(){
        $hash = 'tipser-auth-token';

        if($this->container["cache"]->contains($hash)) {
            $token = $this->container["cache"]->fetch($hash);
            if($this->validateToken($token)) {
                $this->container['logger']->debug('Tipser request for token using cache');
                return $token;
            }
        }

        // Get intermediate token
        $url = $this->endpoint.'v4/auth';
        $body = json_encode([
            'email' => $this->config['api']['user'],
            'password' => $this->config['api']['password'],
        ]);
        $pretoken = $this->sendCurl($url, $body, [], 'POST');

        // Link token to posId
        $url = $this->endpoint.'v4/auth';
        $body = json_encode([
            'posId' => $this->config['api']['posId'],
        ]);
        $token = $this->sendCurl($url, $body, ['authorization: Bearer '.$pretoken], 'PUT');

        $this->container['logger']->debug('Tipser request for token successfull');
        $this->container["cache"]->save($hash, $token, self::TTL_TOKEN);

        return $token;
    }

    protected function sendCurl($url, $body = '', $headers = [], $method = 'GET'){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2 );

        switch($method){
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1 );
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body );
                $headers[] = 'Content-Type:application/json';
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body );
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                $headers[] = 'Content-Type:application/json';
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, 1 );
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);

        if(curl_error($ch)){
            throw new \Exception('Curl request failed with '.curl_error($ch));
        }

        $json = json_decode($output, true);

        if(!$json){
            throw new \Exception('Curl request returned invalid json: '.$output);
        }

        if($json['error'] ?? false){
            throw new \Exception('Curl request returned error message: '.($json['error']['message'] ?? 'Unknown'));
        }

        curl_close($ch);

        return $json;
    }

    protected function validateToken($token){
        $parts = explode('.', $token);

        // Decode JWT token
        $header = base64_decode($parts[0]);
        $payload = json_decode(base64_decode($parts[1]), true);
        $signature = $parts[2];

        // Check expiriation time
        $expire = $payload['exp'] ?? false;
        if($expire > time())
            return false;

        return true;
    }
}