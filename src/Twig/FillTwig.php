<?php

namespace Bolt\Extension\CND\RelationList\Twig;

use Bolt\Content;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Silex\Application;

class FillTwig {

    /* @var Application $container */
    protected $container;

    public function __construct(Application $container){
        $this->container = $container;
    }

    public function getItems($poolKey, $count, $parameters = [], $fixedItems = [], $positionField = false){
        return $this->container['cnd.relationlist.fill']->getItems($poolKey, $count, $parameters, $fixedItems, $positionField);
    }
    
    public function addShownItems($items, $bucket = 'default'){
        return $this->container['cnd.relationlist.fill']->addShownItems($items, $bucket);
    }

    public function addShownId($id, $service, $bucket = 'default') {
        $stub = new Item();
        $stub->id = $id;
        $stub->service = $service;

        $this->addShownItems($stub, $bucket);
    }

    public function getShownIds($bucket = 'default'){
        return $this->container['cnd.relationlist.fill']->getShownIds($bucket);
    }

}
