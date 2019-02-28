<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\CND\RelationList\Controller\RelationListController;
use Bolt\Extension\SimpleExtension;
use Bolt\Storage\Entity\Content;

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

        /* @var \Bolt\Application $app */
        $app = $this->getContainer();
        $config = $this->getConfig();
        $controller = new RelationListController($app, $config);
        $relation = json_decode($relation, true);
        $items = [];

        foreach ($relation as $idx => $value){

            // migration of old relationlist json string
            if ($idx !== 'items' && $idx !== 'globals'){
                list($contenttype, $id) = explode('/', $value);

                $element = $app["storage"]->getContent( $contenttype, ["id" => $id] );

                $items[] = $controller->filterElement($element);
            }

            if ($idx === 'items')
                $items = $relation[$idx];


        }
        $relations['items'] = $items;

        return $relations;

    }


    /**
     * {@inheritdoc}
     */
    public function getRelatedGlobals($relation){

        $relation = json_decode($relation, true);
        $globals = '';

        foreach ($relation as $idx => $value){

            if ($idx === 'globals')
                $globals = $relation[$idx];

        }

        $relations['globals'] = $globals;

        return $relations;
    }







}


