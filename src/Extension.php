<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\CND\RelationList\Controller\RelationListController;
use Bolt\Extension\SimpleExtension;
use Pimple as Container;
use Silex\Application;

class Extension extends SimpleExtension
{
    const DEFAULT_EXCERPT_LENGTH = 125;

    /**
     * {@inheritdoc}
     */
    public function registerFields(){
        return [
            new Field\RelationListField(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerAssets(){

        $config = $this->getConfig();
        $config = json_encode($config);

        $resources    = $this->container['resources'];
        $extensionUrl = $resources->getUrl('bolt');
        $extensionWebPath = $resources->getUrl('root');
        $extensionDefinitions = '{}';


        return [
            (new JavaScript('js/bundle.web.js'))
                ->setZone(Zone::BACKEND)
                ->setAttributes([
                    "data-extension-relationlist-config=".$config,
                    "data-root-url=".$extensionWebPath,
                    "data-extension-url=".$extensionUrl,
                    "data-extension-definitions=".$extensionDefinitions,
                ])
                ->setPriority(1),
            (new JavaScript('js/extension-for/sir-trevor.js'))
                ->setZone(Zone::BACKEND)
                ->setAttributes([
                    "data-extension-relationlist-config=".$config,
                    "data-root-url=".$extensionWebPath,
                    "data-extension-url=".$extensionUrl,
                    "data-extension-definitions=".$extensionDefinitions,
                ])
                ->setLate(true)
                ->setPriority(1),

            (new Stylesheet('css/styles.min.css'))->setZone(Zone::BACKEND),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths(){
        return ['templates', 'templates/structured-content'];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'getRelatedGlobals' => 'getRelatedGlobals',
            'getRelatedItems'   => 'getRelatedItems',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        /* @var \Bolt\Application $app */
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/relationlist' => new RelationListController($app, $config),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedItems($relation){

        if (!$relation || $relation === '')
            return [];

        /* @var \Bolt\Application $app */
        $app = $this->getContainer();
        $relation = json_decode($relation, true);
        $items = [];

        $elements = isset($relation['items']) ? $relation['items'] : $relation;

        foreach ($elements as $idx => $value){
            list($contenttype, $id) = explode('/', $value);
            $items[] = $app["storage"]->getContent( $contenttype, ["id" => $id] );
        }

        return $items;

    }


    /**
     * {@inheritdoc}
     */
    public function getRelatedGlobals($relation){

        if (!$relation || $relation === '')
            return [];

        $relation = json_decode($relation, true);
        $globals = [];

        if (isset($relation['globals'])){
            $globals = $relation['globals'];
        }

        return $globals;
    }
}


