<?php
/**
 * Module.php - Calendar Init
 *
 * Bootstrap File for Calendar Module
 *
 * @category Config
 * @package Calendar
 * @author Nathanael Kammermann
 * @copyright (C) 2019 Nathanael Kammermann <nathanael.kammermann@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 0.3-stable
 * @since File available since Version 0.1-dev
 */

namespace Calendar;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use User\Model\UserTable;
use User\Service\AuthManager;

class Module implements ConfigProviderInterface
{
    // Module Version
    const VERSION = '0.3-stable';

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    public function getServiceConfig()
    {
        return [
            'factories' => [
                Model\CalendarTable::class => function($container) {
                    // Get Tablegateway
                    $tableGateway = $container->get(Model\CalendarTableGateway::class);

                    // Get logged in user info
                    $userTable = $container->get(UserTable::class);
                    $authManager = $container->get(AuthManager::class);
                    $oUser = $userTable->getUser(0,$authManager->getIdentity());

                    // return table object
                    return new Model\CalendarTable($tableGateway,$this->getConfig()['plcx_plugins'],$oUser,$userTable);
                },
                Model\CalendarTableGateway::class => function ($container) {
                    // get database
                    $dbAdapter = $container->get(AdapterInterface::class);
                    // get config
                    $oConfig = $this->getConfig();
                    $userTable = $container->get(UserTable::class);

                    // attach Calendar Entity Model to Resultset
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Calendar($dbAdapter,$oConfig['plcx_plugins'],$userTable));
                    return new TableGateway('event_calendar', $dbAdapter, null, $resultSetPrototype);
                },
                Model\CategoryTable::class => function($container) {
                    $tableGateway = $container->get(Model\CategoryTableGateway::class);
                    $userTable = $container->get(UserTable::class);
                    $authManager = $container->get(AuthManager::class);
                    return new Model\CategoryTable($tableGateway,$this->getConfig()['plcx_plugins'],$userTable->getUser(0,$authManager->getIdentity()));
                },
                Model\CategoryTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $oConfig = $this->getConfig();
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Category($dbAdapter,$oConfig['plcx_plugins']));
                    return new TableGateway('event_category', $dbAdapter, null, $resultSetPrototype);
                },
                Model\EventTable::class => function($container) {
                    $tableGateway = $container->get(Model\EventTableGateway::class);
                    $userTable = $container->get(UserTable::class);
                    $authManager = $container->get(AuthManager::class);
                    return new Model\EventTable($tableGateway,$this->getConfig()['plcx_plugins'],$userTable->getUser(0,$authManager->getIdentity()));
                },
                Model\EventTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $oConfig = $this->getConfig();
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Event($dbAdapter,$oConfig['plcx_plugins']));
                    return new TableGateway('event', $dbAdapter, null, $resultSetPrototype);
                },
            ],
        ];
    }

    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\CalendarController::class => function($container) {
                    // get database
                    $dbAdapter = $container->get(AdapterInterface::class);
                    // get config
                    $oConfig = $this->getConfig();

                    // Plugin Tables
                    $aPluginTbls = [];
                    $aPluginTbls['user'] = $container->get(UserTable::class);
                    if(array_key_exists('resident',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['resident'] = $container->get(\Resident\Model\ResidentTable::class);
                    }
                    if(array_key_exists('room',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['room'] = $container->get(\Room\Model\RoomTable::class);
                    }

                    return new Controller\CalendarController(
                        $container->get(Model\CalendarTable::class),
                        $aPluginTbls,
                        $dbAdapter,
                        $oConfig['plcx_plugins']
                    );
                },
                Controller\CommunityController::class => function($container) {
                    // get database
                    $dbAdapter = $container->get(AdapterInterface::class);
                    // get config
                    $oConfig = $this->getConfig();

                    // Plugin Tables
                    $aPluginTbls = [];
                    $aPluginTbls['user'] = $container->get(UserTable::class);
                    if(array_key_exists('room',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['room'] = $container->get(\Room\Model\RoomTable::class);
                    }
                    if(array_key_exists('community',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['community'] = $container->get(\Community\Model\CommunityTable::class);
                    }

                    return new Controller\CommunityController(
                        $container->get(Model\CalendarTable::class),
                        $aPluginTbls,
                        $dbAdapter,
                        $oConfig['plcx_plugins']
                    );
                },
                Controller\EventController::class => function($container) {
                    // get database
                    $dbAdapter = $container->get(AdapterInterface::class);
                    // get config
                    $oConfig = $this->getConfig();

                    // Plugin Tables
                    $aPluginTbls = [];
                    $aPluginTbls['user'] = $container->get(UserTable::class);
                    if(array_key_exists('room',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['room'] = $container->get(\Room\Model\RoomTable::class);
                    }
                    if(array_key_exists('resident',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['resident'] = $container->get(\Resident\Model\ResidentTable::class);
                    }
                    if(array_key_exists('ticket',$oConfig['plcx_plugins'])) {
                        $aPluginTbls['ticket'] = $container->get(\Article\Model\ArticleTable::class);
                    }

                    return new Controller\EventController(
                        $container->get(Model\CalendarTable::class),
                        $aPluginTbls,
                        $dbAdapter,
                        $oConfig['plcx_plugins']
                    );
                },
                Controller\CategoryController::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $oConfig = $this->getConfig();

                    // Plugin Tables
                    $aPluginTbls = [];
                    $aPluginTbls['user'] = $container->get(UserTable::class);

                    return new Controller\CategoryController(
                        $container->get(Model\CategoryTable::class),
                        $aPluginTbls,
                        $dbAdapter,
                        $oConfig['plcx_category_plugins']
                    );
                },
            ],
        ];
    }
}
