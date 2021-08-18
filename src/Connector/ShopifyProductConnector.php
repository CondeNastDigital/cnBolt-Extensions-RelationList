<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;

class ShopifyProductConnector extends BaseConnector {

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
            '.self::GRAPHQL_QUERY_SHOP.'
        }';

        $data = $this->requestShopify($query)['data'];
        $shop = $data['shop'] ?? [];
        array_walk($data['products']['edges'], function (&$el) use ($shop){
            $el['affiliate'] = $shop;
        });

        return $data['products']['edges'] ?: [];
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

        foreach ($relations as $relation) {
            // GraphQL aliases can only start with a letter
            $alias = '_'.md5($relation->id);
            $queries[] = $alias.': product(id:"'.$relation->id.'"){ ... Properties }';
        }

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.' query {'. implode("\n", $queries) . ' '.self::GRAPHQL_QUERY_SHOP.' }';
        $products = $this->requestShopify($query)['data'] ?: [];

        // Extracts the shop form the results
        $shop     = $products['shop'];
        unset($products['shop']);

        // Applies the shop and transfers the Products to the results array
        foreach ($products as $key => $product) {
            $product['affiliate']   = $shop;
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

        $fill   = $config['fill'] ?? [];
        $filter = $this->buildQuery($fill['filter'] ?? [], $exclude);
        $limit  = (int)($fill['limit'] ?: 4);

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.'
        query {
            products(first: '.$limit.', query: "'.$filter.'", sortKey: PUBLISHED_AT) {
                edges {
                    node {
                        ... Properties
                    }
                }
            }
            
            '.self::GRAPHQL_QUERY_SHOP.'
            
        }';

        $data = $this->requestShopify($query)['data'];
        $shop = $data['shop'] ?? [];

        array_walk($data['products']['edges'], function (&$el) use ($shop){
            $el['affiliate'] = $shop;
        });

        return $data['products']['edges'] ?: [];

    }

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record, $customFields=[]): Relation {
        $record = $this->cleanRecord($record);

        $item = new Relation();
        $item->id = $record['id'];
        $item->type = 'products';
        $item->service = $this->key;
        $item->teaser = [
            'title'       => $record['title'] ?? '',
            'image'       => $this->getImage($record),
            'description' => $record['description'] ?? null,
            'date'        => $record['publishedAt'] ?? null,
            'link'        => $this->getLink($record),
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
        $item->object = $record + [
            'type' => 'shopify-product',
            'link' => $this->getLink($record)
        ];
        $item->teaser = [
            'title'       => $record['title'] ?? '',
            'image'       => $this->getImage($record),
            'description' => $record['description'] ?? null,
            'date'        => $record['publishedAt'] ?? null,
            'link'        => $this->getLink($record),
        ];

        $this->applyCustomFields($customFields, $item->object, $item->teaser);

        return $item;
    }

    /**
     * @param $record
     * @return mixed
     */
    protected function getLink($record) {
        return $record['onlineStoreUrl'] ?: $record['onlineStorePreviewUrl'];
    }

    /**
     * @param $record
     * @return mixed
     */
    protected function cleanRecord($record): array {

        $record = $record['node'] ?? $record;

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
    protected function getImage($record) {
        return $record['featuredMedia']['preview']['w800h800']['src'] ?? false;
    }

    /**
     * @param $params
     * @param array $exclude
     * @return string
     */
    protected function buildQuery($params, $exclude = []) {
        $query = [];
        foreach ($params as $key => $value) {
            switch ($key) {
                case '-id':
                case 'id':
                    $value = explode('/', $value);
                    $query[] = $key.':'.end($value);
                    break;
                default:
                   $query[] = $key.':'.preg_replace('/[^a-z0-9\-\_]+/i','*', $value);
            }
        }

        foreach ($exclude as $value) {
            $value = explode('/', $value);
            $query[] = '-id:'.end($value);
        }

        return implode(' AND ', $query);
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
        if($this->container["cache"]->contains($hash)) {
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

    const GRAPHQL_QUERY_SHOP = "
      shop {
        id
        name
      }
    ";

    // GraphQl
    const GRAPHQL_FRAGMENT_PRODUCT = "
       fragment Properties on Product {
        id
        handle
        vendor
        title
        description
        vendor
        storefrontId
        onlineStoreUrl
        onlineStorePreviewUrl
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
            original: image {
              id
              altText
              src: originalSrc
            }
            w800h800: image {
              id
              altText
              originalSrc
              src: transformedSrc(maxWidth: 800, maxHeight: 800, crop: CENTER)
            }
            w1024h576: image {
              id
              altText
              src: transformedSrc(maxWidth: 1024, maxHeight: 576, crop: CENTER)
            }
            w600h900: image {
              id
              altText
              src: transformedSrc(maxWidth: 600, maxHeight: 900, crop: CENTER)
            }
            w800h533: image {
              id
              altText
              src: transformedSrc(maxWidth: 800, maxHeight: 533, crop: CENTER)
            }
          }
        }
    }
    ";

}