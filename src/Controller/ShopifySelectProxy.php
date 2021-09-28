<?php

namespace Bolt\Extension\CND\RelationList\Controller;


use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\Service\RelationService;
use Exception;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShopifySelectProxy implements ControllerProviderInterface{

    const CATEGORIES_TTL = 24*3600;

    protected $app;
    protected $config;
    protected $collection;
    protected $endpoint = false;

    /* @var RelationService $service */
    protected $service;

    public function __construct (Application $app, array $config){
        $this->app = $app;
        $this->config = $config['connectors']['shopify-product'];
        $this->endpoint = $this->config['api']['url'].$this->config['api']['endpoint'];
        $this->collection = $this->config['collection'];
    }

    public function connect(\Silex\Application $app){
        $ctr = $app['controllers_factory'];

        $ctr->get('/autocomplete/categories', array($this, 'categories'));

        return $ctr;
    }

    /**
     * Get a list of matching items based on the pool
     *
     * @param Request $request
     * @param $type
     * @param $slug
     * @return JsonResponse
     * @throws Exception
     */
    public function categories( Request $request ){

        $query = 'query {
            collections(first: '.$this->collection['limit'].') {
                edges {
                    node {
                        id
                        title
                        handle
                        descriptionHtml
                        products(first: 5) {
                            edges {
                                node {
                                    id
                                    title
                                    handle
                                }          
                            }
                        }
                    }
                }  
            }
        }';
        $body = json_encode([
            "query" => $query
        ]);

        $search = $request->get('search') ?: false;

        /* @var \Bolt\Cache $cache */
        $cache = $this->app['cache'];

        $categories = $cache->fetch('shopify-categories');
        if(!$categories){
            $categories = $this->loadCategories($body);
            $categories = $this->convertCategories($categories);
            $cache->save('shopify-categories', $categories, self::CATEGORIES_TTL);
        }
        $categories = $this->filterCategories($categories, $search);

        return new JsonResponse([
            'items' => $categories,
            'stats' => [
                'total' => count($categories)
            ],
            'status' => true,
        ]);
    }

    protected function loadCategories($body, $headers = []){

        // headers
        $headers[] = 'Content-Type:application/json';
        $token = $this->config['api']['token'] ?? false;
        if($token) {
            $headers[] = 'X-Shopify-Storefront-Access-Token: ' . $token;
        }

        // curl request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt($ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);

        // error handling
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

    protected function convertCategories($input){

        $items = [];
        foreach($input['data']['collections']['edges'] ?? [] as $collection) {
            $key = $collection['node']['handle'];
            $items[$key] = [
                'value' => $collection['node']['id'],
                'label' => $collection['node']['title'],
                'info' => $collection['node']['descriptionHtml'] ?? '',
            ];
        }

        return $items;
    }

    protected function filterCategories($items, $search){
        foreach($items as $idx => $item){
            if(stripos($item['label'], $search) === false){
                unset($items[$idx]);
            }
        }
        return array_values($items);
    }

}
