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
     * @inheritdoc
     * @throws \Exception
     */
    public function searchRecords($config, $text): array{

        $cleaned = preg_replace('/[^a-z0-9\_\-\s]+/i','*', $text);

        $query = self::GRAPHQL_FRAGMENT_PRODUCT.'
        query {
            products(first: 20, query: "title:*'.$cleaned.'*", sortKey: UPDATED_AT) {
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
            $el['node']['affiliate'] = $shop;
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
            $queries[] = $alias.': node(id:"'.$relation->id.'"){ ... Properties }';
        }

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.' query {'. implode("\n", $queries) . ' '.self::GRAPHQL_QUERY_SHOP.' }';
        $products = $this->requestShopify($query)['data'] ?: [];

        // Extracts the shop form the results
        $shop     = $products['shop'];
        unset($products['shop']);

        // Applies the shop and transfers the Products to the results array
        foreach ($products as $product) {
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
        $mode = $fill['mode'] ?? 'similar';
        $fillItems = [];

        if($mode === 'similar') {
            $fillItems = $this->fillSimilar($config['fill']);
        }

        if($mode === 'products') {
            $fillItems = $this->fillProduct($config['fill'], $exclude);
        }

        return $fillItems;
    }

    /**
     *
     * @param array $fillConfig
     * @return array
     * @throws \Exception
     */
    protected function fillSimilar(array $fillConfig): array {
        $productId = $fillConfig['productid'];
        if(!$productId = $this->cleanProductId($productId)) {
            return [];
        }

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.'
            query {
                productRecommendations(productId: '.$productId.') {
                    ... Properties
                }
            
            '.self::GRAPHQL_QUERY_SHOP.'
            
            }';

        $data = $this->requestShopify($query)['data'];
        $shop = $data['shop'] ?? [];

        array_walk($data['productRecommendations'], function (&$el) use ($shop) {
            $el['affiliate'] = $shop;
        });
        return $data['productRecommendations'] ?: [];

    }

    /**
     * @param array $fillConfig
     * @param $exclude
     * @return array
     * @throws \Exception
     */
    protected function fillProduct(array $fillConfig, $exclude): array {

        $filter = $this->buildQuery($fillConfig['filter'] ?? [], $exclude);
        $limit  = (int)($fillConfig['limit'] ?: 4);

        $query =  self::GRAPHQL_FRAGMENT_PRODUCT.'
            query {
                products(first: '.$limit.', query: "'.$filter.'", sortKey: UPDATED_AT) {
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

        array_walk($data['products']['edges'], function (&$el) use ($shop) {
            $el['node']['affiliate'] = $shop;
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

    /* ------- Helper functions ------ */

    /**
     * @param $record
     * @return mixed
     */
    protected function getLink($record) {
        return $record['onlineStoreUrl'] ?? null;
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
     * @param array $record
     * @return mixed
     */
    protected function getImage($record) {
        $images = $record['featuredMedia']['preview'] ?? [];
        return reset($images)['node']['w800h800']['src'] ?? false;
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
                # Shopify has a thing called global ids, which hold the ID that we need adter the slash
                # -id means: id != ...
                case '-id':
                case 'id':
                    $query[] = $key.':'.$this->cleanProductId($value);
                    break;
                default:
                   $query[] = $key.':'.$this->filterValue($value);
            }
        }

        foreach ($exclude as $value) {
            $value = explode('/', $value);
            $query[] = '-id:'.end($value);
        }

        return implode(' AND ', $query);
    }

    /**
     * @param string $id
     * @return array|string|string[]|null
     */
    protected function cleanProductId(string $id): string {
        $id = explode('/', $id);
        return preg_replace('/[^a-z0-9\=]+/i','*', end($id));
    }

    /**
     * @param string $value
     * @return array|string|string[]|null
     */
    protected function filterValue(string $value): string {
        return preg_replace('/[^a-z0-9\-\_]+/i','*', $value);
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
    protected function requestShopify(string $query){

        $apiUrl = $this->config['api']['url'].$this->config['api']['endpoint'];
        $hash = md5($apiUrl.serialize($query));
        $url = $this->endpoint.$apiUrl;

        $token = $this->config['api']['token'] ?? false;
        if($token) {
            $headers = [
                'X-Shopify-Storefront-Access-Token: ' . $token
            ];
        }

        // Check Cache
        if($this->container["cache"]->contains($hash)) {
            $this->container['logger']->debug('Tipser request using cache');
            return $this->container["cache"]->fetch($hash);
        }

        $body = json_encode([
            "query" => $query
        ]);

        $result = $this->sendCurl($url, $body, $headers, 'POST');

        if ($result && ($result['error'] ?? false)) {
            return false;
        }

        $this->container['logger']->debug('Tipser request successfull');
        $this->container["cache"]->save($hash, $result, self::TTL_DATA);

        return $result;
    }

    /**
     * @param $url
     * @param string $body
     * @param array $headers
     * @param string $method
     * @return mixed
     * @throws \Exception
     */
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

    // --- GraphQL Fragments ---

    const GRAPHQL_QUERY_SHOP = "
      shop {
        name
        primaryDomain {
          url
          host
          sslEnabled
        }
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
        onlineStoreUrl
        publishedAt
        updatedAt
        tags
        priceRangeV2: priceRange {
          minVariantPrice {
            amount
            currencyCode
          }
          maxVariantPrice {
            amount
            currencyCode
          }
        }
        featuredMedia: media(first: 10) {
           preview: edges {
            node {
                original: previewImage {
                  id
                  altText
                  src: originalSrc
                }
                w800h800: previewImage {
                  id
                  altText
                  originalSrc
                  src: transformedSrc(maxWidth: 800, maxHeight: 800, crop: CENTER)
                }
                w1024h576: previewImage {
                  id
                  altText
                  src: transformedSrc(maxWidth: 1024, maxHeight: 576, crop: CENTER)
                }
                w600h900: previewImage {
                  id
                  altText
                  src: transformedSrc(maxWidth: 600, maxHeight: 900, crop: CENTER)
                }
                w800h533: previewImage {
                  id
                  altText
                  src: transformedSrc(maxWidth: 800, maxHeight: 533, crop: CENTER)
                }
            }
          }
        }
    }
    ";

}