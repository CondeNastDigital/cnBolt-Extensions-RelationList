<?php
namespace Bolt\Extension\CND\RelationList\Entity;

use JsonSerializable;

/**
 * Class Item
 * @package Bolt\Extension\CND\RelationList
 */
class Relation extends Base implements JsonSerializable {

    public $teaser = [];

    public function __construct(array $data = []){
        parent::__construct($data);

        $this->teaser = $data['teaser'] ?? [];
    }

    /**
     * Return the string (json) encoded value
     * @return array
     */
    public function jsonSerialize() {
        return parent::jsonSerialize() + [
            'teaser'     => $this->teaser,
        ];
    }

}
