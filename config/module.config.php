<?php
/**
 * module.config.php - Calendar Module Config
 *
 * Config file for Calendar Module
 *
 * @category Config
 * @package Calendar
 * @author Nathanael Kammermann
 * @copyright (C) 2019 Nathanael Kammermann <nathanael.kammermann@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 * @since File available since Version 1.0
 */

namespace Calendar;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;

return [
    // Basic Route for Module
    'router' => [
        'routes' => [
            'calendar' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/calendar[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CalendarController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'events' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/calendar/event[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\EventController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'event-category' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/event/category[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CategoryController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'ical-feed' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/getics',
                    'defaults' => [
                        'controller' => Controller\EventController::class,
                        'action'     => 'geticsfeed',
                    ],
                ],
            ],
        ],
    ],
    // Extend Navigation
    'navigation' => [
        'default' => [
            [
                'label' => 'Calendars',
                'route' => 'calendar',
                'icon' => 'fas fa-calendar',
                'pages' => [
                    [
                        'label'  => 'Add',
                        'route'  => 'calendar',
                        'action' => 'add',
                    ],
                    [
                        'label'  => 'Edit',
                        'route'  => 'calendar',
                        'action' => 'edit',
                    ],
                    [
                        'label'  => 'Delete',
                        'route'  => 'calendar',
                        'action' => 'delete',
                    ],
                    [
                        'label'  => 'View',
                        'route'  => 'calendar',
                        'action' => 'view',
                    ],
                    [
                        'label'  => 'Bewohner Tagespläne',
                        'route'  => 'calendar',
                        'action' => 'residentdayplan',
                    ],
                    [
                        'label'  => 'Putzplan',
                        'route'  => 'calendar',
                        'action' => 'roomdayplan',
                    ],
                    [
                        'label'  => 'Einstellungen',
                        'route'  => 'calendar',
                        'action' => 'settings',
                    ],
                ],
            ],
        ],

        'admin' => [
            [
                'label' => 'Calendars',
                'route' => 'calendar',
                'icon' => 'fas fa-calendar',
                'pages' => [
                    [
                        'label'  => 'Add',
                        'route'  => 'calendar',
                        'action' => 'add',
                    ],
                    [
                        'label'  => 'Edit',
                        'route'  => 'calendar',
                        'action' => 'edit',
                    ],
                    [
                        'label'  => 'Delete',
                        'route'  => 'calendar',
                        'action' => 'delete',
                    ],
                    [
                        'label'  => 'View',
                        'route'  => 'calendar',
                        'action' => 'view',
                    ],
                    [
                        'label'  => 'Bewohner Tagespläne',
                        'route'  => 'calendar',
                        'action' => 'residentdayplan',
                    ],
                    [
                        'label'  => 'Putzplan',
                        'route'  => 'calendar',
                        'action' => 'roomdayplan',
                    ],
                ],
            ],
        ],
    ],

    'translator' => [
        'locale' => 'de_CH',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'calendar' => __DIR__ . '/../view',
        ],
    ],

    // Define restricted access for this module
    'access_filter' => [
        'options' => [
            // The access filter can work in 'restrictive' (recommended) or 'permissive'
            // mode. In restrictive mode all controller actions must be explicitly listed
            // under the 'access_filter' config key, and access is denied to any not listed
            // action for users not logged in. In permissive mode, if an action is not listed
            // under the 'access_filter' key, access to it is permitted to anyone (even for
            // users not logged in. Restrictive mode is more secure and recommended.
            'mode' => 'restrictive'
        ],
        'controllers' => [
            Controller\CalendarController::class => [
                // Allow anyone to visit "index" and "about" actions
                ['actions' => ['load','check'], 'allow' => '*'],
                // Allow authenticated users to visit "settings" action
                ['actions' => ['add','edit','view','save','index','list','delete'], 'allow' => '@']
            ],
            Controller\CategoryController::class => [
                // Allow anyone to visit "index" and "about" actions
                ['actions' => ['get','list'], 'allow' => '*'],
                // Allow authenticated users to visit "settings" action
                ['actions' => ['add','edit','view','save','index','list','delete'], 'allow' => '@']
            ],
            Controller\EventController::class => [
                // Allow anyone to visit "index" and "about" actions
                ['actions' => ['get','getics','geticsfeed'], 'allow' => '*'],
                // Allow authenticated users to visit "settings" action
                ['actions' => ['add','edit','view','save','index','list','delete'], 'allow' => '@']
            ],
        ]
    ],

    // Load Plugins for this module
    'plcx_plugins' => [
        'user_xp' => '0.1',
        //'resident' => '0.1',
        'room' => '0.1',
        'ticket' => '0.1',
        'wordpress'=>'0.1',
    ],

    'plcx_category_plugins' => [
        'label'=>[
            'type'=>'text',
            'tab'=>'category-base',
            'label'=>'Title',
            'class'=>'col-md-3',
            'hide'=>'view',
            'view_url'=>'/event/category/view/##ID##',
        ],
        'featured_image'=>[
            'type'=>'image',
            'tab'=>'none',
            'label'=>'Avatar',
            'class'=>'col-md-3',
            'hide'=>'view',
            'view_url'=>'/event/category/view/##ID##',
        ],
        'variant'=>[
            'type'=>'extension',
            'tab'=>'none',
            'label'=>'Variant',
            'class'=>'col-md-12',
        ],
        'ticket'=>[
            'type'=>'extension',
            'tab'=>'none',
            'label'=>'Ticket',
            'class'=>'col-md-12',
        ],
    ],

    'plcx_category_options' => [
        'icon' => 'fas fa-tags',
    ],
];
