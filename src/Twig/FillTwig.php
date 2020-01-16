<?php

namespace Bolt\Extension\CND\RelationList\Twig;

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

}