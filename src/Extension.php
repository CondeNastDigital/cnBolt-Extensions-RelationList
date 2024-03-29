<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\CND\RelationList\Controller\RelationController;
use Bolt\Extension\CND\RelationList\Controller\TipserSelectProxy;
use Bolt\Extension\CND\RelationList\Controller\ShopifySelectProxy;
use Bolt\Extension\CND\RelationList\Service\LegacyService;
use Bolt\Extension\CND\RelationList\Service\TwigService;
use Bolt\Extension\SimpleExtension;
use Pimple as Container;
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

    public function getServiceProviders(){
        return [
            $this,
            new Provider\RelationListProvider($this->getConfig()),
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
                    "data-extension-relationlist-config='".$config."'",
                    'data-root-url='.$extensionWebPath,
                    'data-extension-url='.$extensionUrl,
                    'data-extension-definitions='.$extensionDefinitions,
                ])
                ->setPriority(1),
            (new JavaScript('js/extension-for/sir-trevor.js'))
                ->setZone(Zone::BACKEND)
                ->setAttributes([
                    "data-extension-relationlist-config='".$config."'",
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
    protected function registerBackendControllers(){
        /* @var \Bolt\Application $app */
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/relationlist' => new RelationController($app, $config),
            '/relationlist/tipser' => new TipserSelectProxy($app, $config),
            '/relationlist/shopify' => new ShopifySelectProxy($app, $config),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Nut\MigrateCommand($container),
        ];
    }
}


