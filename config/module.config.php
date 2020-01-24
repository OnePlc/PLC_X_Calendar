<?php
/**
 * module.config.php - Calendar Config
 *
 * Main Config File for Calendar Module
 *
 * @category Config
 * @package Calendar
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace OnePlace\Calendar;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    # Calendar Module - Routes
    'router' => [
        'routes' => [
            # Module Basic Route
            'calendar' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/calendar[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CalendarController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'calendar-api' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/calendar/api[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    # View Settings
    'view_manager' => [
        'template_path_stack' => [
            'calendar' => __DIR__ . '/../view',
        ],
    ],
];
