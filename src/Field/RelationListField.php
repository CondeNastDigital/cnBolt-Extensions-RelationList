<?php

namespace Bolt\Extension\CND\RelationList\Field;

use Bolt\Storage\Field\FieldInterface;

class RelationListField implements FieldInterface
{

    public function getName()
    {
        return 'relationlist';
    }

    public function getTemplate()
    {
        return '_relationlist.twig';
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getStorageOptions()
    {
        return array('default'=>'');
    }

}