<?php

namespace Bolt\Extension\CND\RelationList\Service;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;

class TwigService {

    /* @var Application $container */
    protected $container;
    /* @var RelationService $service */
    protected $service;

    public function __construct(Application $container, RelationService $service){
        $this->container = $container;
        $this->service = $service;
    }

    /**
     * Correct data from older versions of relationlist to new data
     * @param array|string $data
     * @return array
     */
    public function prepare($data): array {
        $data = !is_array($data) ? json_decode($data, true) : $data;

        return $this->service->prepare($data);
    }

    /**
     * Get the global attributes from the data inside a relationlist field
     * @param array $data
     * @return array
     */
    public function getGlobals($data): array {
        $data = $this->prepare($data);

        return $data['globals'] ?? [];
    }

    /**
     * Get the list of iitems from the data inside a relationlist field
     * @param array $data
     * @return Item[]
     */
    public function getItems($data): array {
        $data = $this->prepare($data);

        $relations = $data['items'] ?? [];

        return $this->service->getItems($relations);
    }

}