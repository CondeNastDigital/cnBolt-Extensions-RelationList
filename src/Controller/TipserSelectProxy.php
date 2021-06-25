<?php

namespace Bolt\Extension\CND\RelationList\Controller;


use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\Service\RelationService;
use Exception;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TipserSelectProxy implements ControllerProviderInterface{

    const CATEGORIES_TTL = 24*3600;
    const CATEGORIES_URL = 'https://t3-dev-api.tipser.com/v4/categories?market=de';

    protected $app;
    protected $config;

    /* @var RelationService $service */
    protected $service;

    public function __construct (Application $app, array $config){
        $this->app = $app;
        $this->config = $config;
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

        $search = $request->get('search') ?: false;

        /* @var \Bolt\Cache $cache */
        $cache = $this->app['cache'];

        $categories = $cache->fetch('tipser-categories');
        if(!$categories){
            $tipser = $this->loadCategories();
            $categories = $this->convertCategories($tipser);
            $cache->save('tipser-categories', $categories, self::CATEGORIES_TTL);
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

    protected function loadCategories(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::CATEGORIES_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2 );
        $output = curl_exec($ch);

        if(curl_error($ch)){
            throw new \Exception('Tipser request failed with '.curl_error($ch));
        }

        $json = json_decode($output, true);

        if(!$json){
            throw new \Exception('Tipser request returned invalid json: '.$output);
        }

        if($json['error'] ?? false){
            throw new \Exception('Tipser request returned error message: '.($json['error']['message'] ?? 'Unknown'));
        }

        curl_close($ch);

        return $json;
    }

    protected function convertCategories($input){

        $items = [];

        foreach($input['departments'] ?? [] as $department){
            foreach($department['sections'] ?? [] as $section){
                $key = $department['name'].'#'.$section['name'];
                $items[$key] = [
                    'value' => $section['id'],
                    'label' => $section['name'],
                    'info' => $department['name'],
                ];
            }
        }
        ksort($items);
        $items = array_values($items);

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
