<?php
namespace Bolt\Extension\CND\RelationList\Entity;

use JsonSerializable;

/**
 * Class Item
 * @package Bolt\Extension\CND\RelationList
 */
class Item extends Base implements JsonSerializable {

    public $object = null;

    public function __construct(array $data = []){
        parent::__construct($data);

        $this->object = $data['object'] ?? null;
    }

    /**
     * Return the string (json) encoded value
     * @return array
     */
    public function jsonSerialize() {
        return parent::jsonSerialize() + [
            'object'     => $this->object,
        ];
    }

}
