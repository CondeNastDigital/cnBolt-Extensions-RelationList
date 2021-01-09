<?php

namespace Bolt\Extension\CND\RelationList\Connector;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Extension\CND\RelationList\Entity\Relation;
use Bolt\Legacy\Content;
use CND\KrakenSDK\Services\AuthService;
use CND\KrakenSDK\Services\KrakenService;

class KrakenImageConnector extends KrakenConnector {

    /* @var KrakenService $store */
    protected $store;

    const TTL = 60;

    // ----------------------------------------------------------------------------------------------

    protected function record2Relation($record, $customFields=[]): Relation {
        $item = new Relation();
        $item->id = $record['uid'];
        $item->service = $this->key;
        $item->teaser = [];
        $item->type = 'Image';

        $item->teaser = [
            'title' => strtoupper($record['source']['name'] ?? '') . ' - ' . $record['meta']['alt'] ?? $record['meta']['title'],
            'image' => $this->getImage($record),
            'description' => $record['meta']['description'] . ' copyright:' . $record['meta']['copyright'] ,
            'date' => date('c', strtotime($record['control']['publishDate'] ?? '')),
            'link' => $record['origin']['url'] ?? $record['control']['url']
        ];


        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

    protected function record2Item($record, $customFields=[]): Item {
        $item = new Item();
        $item->id = $record['uid'];
        $item->service = $this->key;
        $item->teaser = [];
        $item->type = 'Image';
        $item->object = $record;

        $item->teaser = [
            'title' => strtoupper($record['source']['name'] ?? '') . ' - ' . $record['meta']['alt'] ?? $record['meta']['title'],
            'image' => $this->getImage($record),
            'description' => $record['meta']['description'] . ' copyright:' . $record['meta']['copyright'] ,
            'date' => date('c', strtotime($record['control']['publishDate'] ?? '')),
            'link' => $record['origin']['url'] ?? $record['control']['url']
        ];

        $this->applyCustomFields($customFields, $record, $item->teaser);

        return $item;
    }

}