<?php
namespace Bolt\Extension\CND\RelationList\Entity;

use JsonSerializable;

/**
 * Class Item
 * @package Bolt\Extension\CND\RelationList
 */
abstract class Base implements JsonSerializable {
    // Core fields
    public $id;
    public $service;
    public $type;
    public $attributes;

    public function __construct($data = []){

        $this->id = $data['id'] ?? false;
        $this->service = $data['service'] ?? false;
        $this->type = $data['type'] ?? false;
        $this->attributes = $data['attributes'] ?? [];
    }

    /**
     * Return the string (json) encoded value
     * @return array
     */
    public function jsonSerialize() {
        return [
            'id'         => $this->id,
            'service'    => $this->service,
            'type'       => $this->type,
            'attributes' => $this->attributes,
        ];
    }
}
