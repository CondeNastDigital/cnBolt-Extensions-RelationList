<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\CND\RelationList\Controller\RelationController;
use Bolt\Extension\CND\RelationList\Service\LegacyService;
use Bolt\Extension\CND\RelationList\Service\TwigService;
use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Bolt\Extension\CND\RelationList\Service\RelationService;

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
    public function registerServices(Application $app){
        $config = $this->getConfig();

        $app['cnd.relationlist.legacy'] = $app->share(
            function ($app) {
                return new LegacyService($app);
            }
        );

        /* @var \Bolt\Application $app */
        $app['cnd.relationlist.service'] = $app->share(
            function ($app) use ($config) {
                return new RelationService($app, $config, $app['cnd.relationlist.legacy']);
            }
        );

        $app['cnd.relationlist.twig'] = $app->share(
            function ($app) use ($config) {
                return new TwigService($app, $app['cnd.relationlist.service']);
            }
        );

        /* @var \Bolt\Application $app */
        $container['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig) use ($app){
                    $twig->addGlobal('RelationList', $app['cnd.relationlist.twig']);
                    return $twig;
                }
            )
        );
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
                    'data-extension-relationlist-config='.$config,
                    'data-root-url='.$extensionWebPath,
                    'data-extension-url='.$extensionUrl,
                    'data-extension-definitions='.$extensionDefinitions,
                ])
                ->setPriority(1),
            (new JavaScript('js/extension-for/sir-trevor.js'))
                ->setZone(Zone::BACKEND)
                ->setAttributes([
                    'data-extension-relationlist-config='.$config,
                    'data-root-url='.$extensionWebPath,
                    'data-extension-url='.$extensionUrl,
                    'data-extension-definitions='.$extensionDefinitions,
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
        return [
            'templates',
            'templates/structured-content'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions() {
        return [
            // @deprecated
            'getRelatedGlobals'    => 'getRelatedGlobals',
            'getRelatedItems'      => 'getRelatedItems',
            'getRelatedAttributes' => 'getRelatedAttributes',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers(){
        /* @var \Bolt\Application $app */
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/relationlist' => new RelationController($app, $config),
        ];
    }

    /**
     * Return the related objects within  attributes of a stored relationlist field
     * @deprecated use RelationList.getGlobals( value )
     */
    public function getRelatedItems($data){
        /* @var TwigService $relationList */
        $relationList = $this->getContainer()['cnd.relationlist.twig'];

        $result = [];
        foreach($relationList->getItems($data) as $item){
            $result[$item->id] = $item->object;
        }
        return $result;
    }


    /**
     * Return the global attributes of a stored relationlist field
     * @deprecated use RelationList.getGlobals( value )
     */
    public function getRelatedGlobals($data){
        /* @var TwigService $relationList */
        $relationList = $this->getContainer()['cnd.relationlist.twig'];
        return $relationList->getGlobals($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedAttributes($data){
        /* @var TwigService $relationList */
        $relationList = $this->getContainer()['cnd.relationlist.twig'];

        $result = [];
        foreach($relationList->prepare($data)['items'] ?? [] as $relation){
            $result[$relation->id] = $relation->attributes;
        }
        return $result;
    }

}


