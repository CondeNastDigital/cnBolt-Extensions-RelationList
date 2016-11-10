<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\CND\RelationList\Controller\RelationListController;
use Bolt\Extension\SimpleExtension;

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

        return [
            (new JavaScript('js/RelationList.js'))
                ->setZone(Zone::BACKEND)
                ->setAttributes([
                    "data-extension-relationlist-config=".$config,
                    "data-root-url=".$extensionWebPath,
                    "data-extension-url=".$extensionUrl,
                ])
                ->setLate(true)
                ->setPriority(1),
            (new JavaScript('js/extension-for/sir-trevor.js'))
                ->setZone(Zone::BACKEND)
                ->setAttributes([
                    "data-extension-relationlist-config=".$config,
                    "data-root-url=".$extensionWebPath,
                    "data-extension-url=".$extensionUrl,
                ])
                ->setLate(true)
                ->setPriority(1),

            (new Stylesheet('css/styles.css'))->setZone(Zone::BACKEND)->setLate(true),
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
    protected function registerBackendControllers()
    {
        /* @var \Bolt\Application $app */
        $app = $this->getContainer();
        $config = $this->getConfig();

        return [
            '/relationlist' => new RelationListController($app, $config),
        ];
    }

}


