<?php
return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        'controller' => 'index',
                        'action' => 'index',
                    ),
                ),
            ),
            'login' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/login',
                    'defaults' => array(
                        'controller' => 'index',
                        'action' => 'login',
                    ),
                ),
            ),
            'flightzilla' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/flightzilla[/:controller[/:action]]',
                    'constraints' => array(
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        'controller' => 'index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Wildcard',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            '_session' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                $oSessionStorage = new \Zend\Session\SaveHandler\Cache($oServiceManager->get('_cache'));
                $oManager = new \Zend\Session\SessionManager();
                $oManager->setSaveHandler($oSessionStorage);

                $oSession = new \Zend\Session\Container('flightzilla');
                $oSession->setDefaultManager($oManager);
                return $oSession;
            },
            '_log' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                $oLogger = new \Zend\Log\Logger;
                $oLogger->addWriter(new Zend\Log\Writer\Stream(sprintf('./log/%s-error.log', date('Y-m-d'))));

                return $oLogger;
            },
            '_cache' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                $sAdapter = (extension_loaded('memcached') === true) ? 'memcached' : '\Flightzilla\Cache\Storage\Adapter\Memcache';
                return Zend\Cache\StorageFactory::factory(
                    array(
                         'adapter' => array(
                             'name' => $sAdapter,
                             'options' => array(
                                 'ttl' => 86400,
                                 // 1 day
                                 'servers' => array(
                                     array(
                                         'host' => '127.0.0.1',
                                         'port' => 11211,
                                     )
                                 ),
                             )
                         ),
                         'plugins' => array(
                             'serializer'
                         ),
                    )
                );
            },
            '_serviceConfig' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                $aConfig = $oServiceManager->get('config');
                if (empty($aConfig['flightzilla']) === true) {
                    throw new \InvalidArgumentException('Missing flightzilla-config-section!');
                }

                return new \Zend\Config\Config($aConfig['flightzilla']);
            },
            '_bugzilla' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                $oResource = new \Flightzilla\Model\Resource\Manager;
                $oHttpClient = new \Zend\Http\Client();
                $oConfig = $oServiceManager->get('_serviceConfig');
                $oSession = $oServiceManager->get('session');

                $oTicketSource = new \Flightzilla\Model\Ticket\Source\Bugzilla($oResource, $oHttpClient, $oConfig);
                $oTicketSource->setCache($oServiceManager->get('_cache'))
                    ->setAuth($oServiceManager->get('_auth'))
                    ->setLogger($oServiceManager->get('_log'))
                    ->setProject($oSession->sCurrentProduct)
                    ->initHttpClient();

                return $oTicketSource;
            },
            '_analytics' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                $oGdataHttpClient = new \ZendGData\HttpClient();
                $oGdataHttpClient->setOptions(
                    array(
                         'sslverifypeer' => false
                    )
                );
                $oConfig = $oServiceManager->get('_serviceConfig');

                $oAnalytics = new \Flightzilla\Model\Analytics\Service($oGdataHttpClient, $oConfig);
                $oAnalytics->setCache($oServiceManager->get('_cache'))
                            ->setAuth($oServiceManager->get('_auth'));

                return $oAnalytics;
            },
            'notifyy' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                $aConfig = $oServiceManager->get('_serviceConfig')->toArray();
                $sCurrentUser = $oServiceManager->get('_auth')->getLogin();

                $aNotifyConfig = array();
                if (empty($aConfig['notifyy'][$sCurrentUser]) !== true) {
                    $aNotifyConfig = $aConfig['notifyy'][$sCurrentUser];
                }

                return \notifyy\Builder::build($aNotifyConfig);
            },
            'mergy' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                $oConfig = $oServiceManager->get('_serviceConfig')->bugzilla->projects;
                $oSession = $oServiceManager->get('session');

                $oMergyConfig = new \Zend\Config\Config(array());
                if (empty($oConfig->{$oSession->sCurrentProduct}->mergy) !== true) {
                    $oMergyConfig = $oConfig->{$oSession->sCurrentProduct}->mergy;
                }

                return $oMergyConfig;
            }
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'index' => 'Flightzilla\Controller\IndexController',
            'kanban' => 'Flightzilla\Controller\KanbanController',
            'project' => 'Flightzilla\Controller\ProjectController',
            'mergy' => 'Flightzilla\Controller\MergyController',
            'ticket' => 'Flightzilla\Controller\TicketController',
            'analytics' => 'Flightzilla\Controller\AnalyticsController',
            'team' => 'Flightzilla\Controller\TeamController',
            'watchlist' => 'Flightzilla\Controller\WatchlistController',
            'source' => 'Flightzilla\Controller\SourceController',
            'stats' => 'Flightzilla\Controller\StatsController'
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            \Flightzilla\Controller\Plugin\Authenticate::NAME => 'Flightzilla\Controller\Plugin\Authenticate',
            \Flightzilla\Controller\Plugin\TicketService::NAME => 'Flightzilla\Controller\Plugin\TicketService',
            \Flightzilla\Controller\Plugin\AnalyticsService::NAME => 'Flightzilla\Controller\Plugin\AnalyticsService',
            \Flightzilla\Controller\Plugin\ProjectService::NAME => 'Flightzilla\Controller\Plugin\ProjectService',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'buggradient' => 'Flightzilla\View\Helper\Buggradient',
            'deadlinestatus' => 'Flightzilla\View\Helper\Deadlinestatus',
            'finishstatus' => 'Flightzilla\View\Helper\Finishstatus',
            'estimation' => 'Flightzilla\View\Helper\Estimation',
            'prioritycolor' => 'Flightzilla\View\Helper\Prioritycolor',
            'ticketicons' => 'Flightzilla\View\Helper\Ticketicons',
            'collectiontime' => 'Flightzilla\View\Helper\CollectionTime',
            'userfilter' => 'Flightzilla\View\Helper\Userfilter',
        ),
        'factories' => array(
            'workflow' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                return new \Flightzilla\View\Helper\Workflow($oServiceManager->getServiceLocator()->get('_serviceConfig'));
            },
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'flightzilla/index/index' => __DIR__ . '/../view/flightzilla/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
