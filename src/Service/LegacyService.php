<?php

namespace Bolt\Extension\CND\RelationList\Service;

use Bolt\Application;
use Bolt\Extension\CND\ImageService\Content;
use Bolt\Extension\CND\RelationList\Connector\ContentConnector;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Extension\CND\RelationList\IConnector;

class LegacyService {

    /* @var Application $container */
    protected $container;

    public function __construct($container){
        $this->container = $container;
    }

    /**
     * convert a legacy configuration to new format @deprecated
     * @param array $legacy
     * @return array
     */
    public function getLegacyFieldConfig($legacy){

        $config = [];

        // old 'options' sub array
        if(isset($legacy['options'])){
            $config += $legacy['options'];
        }

        // old 'allowed-types' array using bolt's old content connector
        if(isset($config['allowed-types'])){
            $config['sources']['content'] = $config['allowed-types'];
            unset($config['allowed-types']);
        }

        return $config;
    }

    /**
     * Convert whatever we find inside a records field value to the current version of the data
     * @param array $value
     * @return array
     */
    public function convertLegacyValue($value){

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