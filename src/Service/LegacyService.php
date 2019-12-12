<?php

namespace Bolt\Extension\CND\RelationList\Service;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\IConnector;

class LegacyService {

    /* @var Application $container */
    protected $container;

    public function __construct(Application $container){
        $this->container = $container;
    }

    /**
     * Convert whatever we find inside a records field value to the current version of the data
     * @param string|array $value
     * @return array
     */
    public function convertValue($value){
        $value = !is_array($value) ? json_decode($value, true) : $value;

        $result = [
            'globals' => $value['globals'] ?? [],
            'items' => []
        ];
        // All ids directly in $value
        if(!isset($value['items']) && is_array($value)){
            $value = [ 'items' => $value ];
        }
        // Convert any non relations to proper relation objects
        foreach($value['items'] ?? [] as $idx => $item){
            if(!$item instanceof Relation){
                // new relationlist style [{id:'article/12'},{id:'article/34'}]
                if (is_array($item)) {
                    $id = $item['id'];
                    $contenttype = $item['type'] ?? false;
                    $attributes = $item['attributes'] ?? [];
                    $service = $item['service'];
                }
                // old relationlist style ['article/12','article/34']
                else{
                    $id = $item;
                    list($contenttype) = explode('/', $item);
                    $attributes = $value['attributes'][$item] ?? [];
                    $service = 'content'; // Hardcoded value - This can only work if your legacy service is also called 'content'!
                }
                $relation = new Relation([
                    'id' => $id,
                    'type' => $contenttype,
                    'service' => $service,
                    'attributes' => $attributes,
                ]);
            }
            $result['items'][] = $relation;
        }
        return $result;
    }

}