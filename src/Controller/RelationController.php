<?php

namespace Bolt\Extension\CND\RelationList\Controller;


use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\Service\RelationService;
use Exception;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RelationController implements ControllerProviderInterface{

    protected $app;
    protected $config;

    /* @var RelationService $service */
    protected $service;

    public function __construct (Application $app, array $config){
        $this->app = $app;
        $this->config = $config;

        $this->service = $this->app['cnd.relationlist.service'];
    }

    public function connect(\Silex\Application $app){
        $ctr = $app['controllers_factory'];

        $ctr->get('/search/{contenttype}/{field}/{search}', array($this, 'search'));
        $ctr->get('/search/{contenttype}/{field}/{subfield}/{search}', array($this, 'search'));

        $ctr->match('/fetch', array($this, 'fetch'));

        return $ctr;
    }

    /**
     * Get a list of matching contents per type
     *
     * @param string $contenttype
     * @param string $field
     * @param string $search
     *
     * @return JsonResponse
     */
    public function search($contenttype, $field, $subfield = null, $search = null){
        $contenttype = preg_replace('/[^a-z0-9\\-_]+/i', '', $contenttype);
        $field       = preg_replace('/[^a-z0-9\\-_]+/i', '', $field);
        $subfield    = preg_replace('/[^a-z0-9\\-_]+/i', '', $subfield);

        if(!$this->app['users']->isValidSession()) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Insufficient access rights'
            ]);
        }

        $items = $this->service->searchRelations($search, $contenttype, $field, $subfield);

        return new JsonResponse([
            'status' => true,
            'items' => $items
        ]);
    }

    /**
     * Fetch a JSON object list of elements
     * This method will normaly expect a json array of relation objects, which will be updated with new teaser data
     * LEGACY: If called with old style array of ids in elements, it will convert them to proper relation objects.
     * Result will allways be a list of relation objects
     *
     * @param Request $request
     * @return JsonResponse  Relation objects as JSON
     * @throws Exception
     */
    public function fetch( Request $request ) {
        $items = json_decode($request->get('items'), true) ?? [];

        if( !$this->app["users"]->isValidSession()){
            return new JsonResponse([
                'status' => false,
                'message' => 'Insufficient access rights'
            ]);
        }

        // Convert items to relation objects
        $relations = [];
        foreach($items as $item){
            if (is_array($item) && isset($item['id']))
                $relations[] = new Relation($item);
        }

        $results = $this->service->updateRelations($relations);

        return new JsonResponse([
            'status' => true,
            'items' => $results
        ]);
    }

}
