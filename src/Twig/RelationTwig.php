<?php

namespace Bolt\Extension\CND\RelationList\Twig;

use Silex\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;

class RelationTwig {

    /* @var Application $container */
    protected $container;

    public function __construct(Application $container){
        $this->container = $container;
    }

    /**
     * Get full items from the data inside a relationlist field
     * @param string|array $relations
     * @return Item[]
     */
    public function getItems($data): array{
        $data = $this->container['cnd.relationlist.legacy']->convertValue($data);

        $relations = $data['items'] ?? [];
        return $this->container['cnd.relationlist.relation']->getItems($relations);
    }

    /**
     * Get the global attributes from the data inside a relationlist field
     * @param string|array $data
     * @return array
     */
    public function getGlobals($data): array {
        $value = !is_array($data) ? json_decode($data, true) : $data;
        return $value['globals'] ?? [];
    }

}
