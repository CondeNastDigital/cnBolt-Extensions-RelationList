<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;

class ShopifyProductConnector extends BaseConnector {

    const TTL_TOKEN = 3600 * 24 * 7; // 7 days cache for auth token
    const TTL_DATA = 60 * 10; // 10 minutes cache for product data

    protected $config = [];
    protected $endpoint = false;



    /**
     * KrakenConnector constructor.
     * @param $key
     * @param Application $container
     * @param $config
     * @throws \Exception
     */
    public function __construct($key, Application $container, $config){
        parent::__construct($key, $container, $config);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function searchRecords($config, $text): array{

        $cleaned = preg_replace('/[^a-z0-9\_\-\s]+/i','*', $text);

        $query = self::GRAPHQL_FRAGMENT_PRODUCT.'
        query {
            products(first: 20, query: "title:*'.$cleaned.'*", sortKey: PUBLISHED_AT) {
                edges {
                    node {
                        ... Properties
                    }
                }
            }
        }';

        return $this->requestShopify($query)['data']['products']['edges'] ?: [];
    }

    /**
     * This function uses an export endpoint of tipser, to save requests.
     * However it requires a market!!!
     * @param Relation[] $relations
     * @return array
     */
    protected function getRecords($relations): array{

        $queries = [];
        $result = [];
        $endPoint = $this->config['api']['endpoint'];

        foreach ($relations as $relation) {
            $id = md5($relation->id);
            $queries[] = $id.': product(id:"'.$relation->id.'"){ ... Properties }';
        }

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.' query {'. implode("\n", $queries) . '}';
        $products = $this->requestShopify($endPoint, $query)['data'] ?: [];

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

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.'
        query {
            products(first: '.(int)$config['limit'].', query: "'.$config['query'].'", sortKey: PUBLISHED_AT) {
                edges {
                    node {
                        ... Properties
                    }
                }
            }
        }';

        return $this->requestShopify($query)['data']['products']['edges'] ?: [];

    }

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record, $customFields=[]): Relation {
        $record = $this->cleanRecord($record);

        $item = new Relation();
        $item->id = $record['id'];
        $item->type = 'products';
        $item->service = $this->key;
        $item->teaser = [
            'title'       => $record['node']['title'] ?? '',
            'image'       => $this->getImage($record['node']),
            'description' => $record['node']['description'] ?? null,
            'date'        => $record['node']['publishedAt'] ?? null,
            'link'        => '#',
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    protected function record2Item($record, $customFields=[]): Item {
        $record = $this->cleanRecord($record);

        $item = new Item();
        $item->id = $record['id'] ?? '';
        $item->type = 'product';
        $item->service = $this->key;
        $item->object = $record['node'] + ['type' => 'shopify-product'];
        $item->teaser = [
            'title'       => $record['node']['title'] ?? '',
            'image'       => $this->getImage($record['node']),
            'description' => $record['node']['description'] ?? null,
            'date'        => $record['node']['publishedAt'] ?? null,
            'link'        => '#',
        ];

        $this->applyCustomFields($customFields, $item->object, $item->teaser);

        return $item;
    }

    /**
     * @param $record
     * @return mixed
     */
    protected function cleanRecord($record): array {

        $record['node']['description'] = strip_tags($record['node']['description'] ?? '');
        $record['node']['title']       = strip_tags($record['node']['title'] ?? '');

        return $record;
    }

    /**
     * Get largest image for a desired aspect ration
     * @param Content $record
     * @param float $target
     * @return mixed
     */
    protected function getImage($record) {
        return $record['featuredMedia']['preview']['image']['transformedSrc'] ?? false;
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
    protected function requestShopify($query){

        $endpoint = $this->config['api']['url'].$this->config['api']['endpoint'];
        $hash = md5($endpoint.serialize($query));
        $url = $this->endpoint.$endpoint;
        $headers = [];

        // Check Cache
        if(false && $this->container["cache"]->contains($hash)) {
            $this->container['logger']->debug('Tipser request using cache');
            return $this->container["cache"]->fetch($hash);
        }

        $body = json_encode([
            "query" => $query
        ]);

        $result = $this->sendCurl($url, $body, $headers, 'POST');

        if ($result && ($result['error'] ?? false))
            return false;

        $this->container['logger']->debug('Tipser request successfull');
        $this->container["cache"]->save($hash, $result, self::TTL_DATA);

        return $result;
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

    // GraphQl
    const GRAPHQL_FRAGMENT_PRODUCT = "
       fragment Properties on Product {
        id
        title
        description
        vendor
        storefrontId
        onlineStoreUrl
        publishedAt
        tags
        priceRangeV2 {
          minVariantPrice {
            amount
            currencyCode
          }
          maxVariantPrice {
            amount
            currencyCode
          }
        }
        featuredMedia {
          preview {
            image {
              id
              altText
              originalSrc
              transformedSrc(maxWidth: 800, maxHeight: 800)
            }
          }
        }
    }
    ";

}