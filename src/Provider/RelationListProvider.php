<?php

namespace Bolt\Extension\CND\RelationList\Provider;

use Bolt\Extension\CND\RelationList\IConnector;
use Bolt\Extension\CND\RelationList\Service\FillService;
use Bolt\Extension\CND\RelationList\Service\LegacyService;
use Bolt\Extension\CND\RelationList\Service\RelationService;
use Bolt\Extension\CND\RelationList\Twig\FillTwig;
use Bolt\Extension\CND\RelationList\Twig\RelationTwig;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RelationListProvider implements ServiceProviderInterface {

    protected $config;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    public function register(Application $app) {

        $config = $this->config;

        // Services
        $app['cnd.relationlist.relation'] = $app->share(
            function ($app) use ($config) {
                return new RelationService($app, $config, $app['cnd.relationlist.connectors']);
            }
        );

        $app['cnd.relationlist.fill'] = $app->share(
            function ($app) use ($config) {
                return new FillService($app, $config, $app['cnd.relationlist.connectors']);
            }
        );

        $app['cnd.relationlist.legacy'] = $app->share(
            function ($app){
                return new LegacyService($app);
            }
        );

        // Connectors
        $app['cnd.relationlist.connectors'] = $app->share(
            function ($app) use ($config) {
                $connectors = [];
                foreach($config['connectors'] as $key => $connectorConfig){

                    if(!isset($connectorConfig['class']) || !class_exists($connectorConfig['class'])) {
                        continue;
                    }
                    $connector = new $connectorConfig['class']($key, $app, $connectorConfig);
                    if(!$connector instanceof IConnector) {
                        continue;
                    }

                    $connectors[$key] = $connector;
                }
                return $connectors;
            }
        );

        // Twig Extension Service
        $container['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig) use ($app){
                    $twig->addGlobal('RelationList', new RelationTwig($app));
                    $twig->addGlobal('RelationFill', new FillTwig($app));
                    return $twig;
                }
            )
        );

    }

    public function boot(Application $app) {
    }
}