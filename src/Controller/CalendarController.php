<?php
/**
 * CalendarController.php - Main Controller
 *
 * Main Controller Calendar Module
 *
 * @category Controller
 * @package Calendar
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace OnePlace\Calendar\Controller;

use Application\Controller\CoreEntityController;
use Application\Model\CoreEntityModel;
use OnePlace\Calendar\Model\Calendar;
use OnePlace\Calendar\Model\CalendarTable;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;

class CalendarController extends CoreEntityController {
    /**
     * Calendar Table Object
     *
     * @since 1.0.0
     */
    protected $oTableGateway;

    /**
     * CalendarController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @param CalendarTable $oTableGateway
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,CalendarTable $oTableGateway,$oServiceManager) {
        $this->oTableGateway = $oTableGateway;
        $this->sSingleForm = 'calendar-single';
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);

        if($oTableGateway) {
            # Attach TableGateway to Entity Models
            if(!isset(CoreEntityModel::$aEntityTables[$this->sSingleForm])) {
                CoreEntityModel::$aEntityTables[$this->sSingleForm] = $oTableGateway;
            }
        }
    }

    /**
     * Calendar Index
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function indexAction() {
        # You can just use the default function and customize it via hooks
        # or replace the entire function if you need more customization
        return $this->generateIndexView('calendar');
    }

    /**
     * Calendar Add Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function addAction() {
        /**
         * You can just use the default function and customize it via hooks
         * or replace the entire function if you need more customization
         *
         * Hooks available:
         *
         * calendar-add-before (before show add form)
         * calendar-add-before-save (before save)
         * calendar-add-after-save (after save)
         */
        return $this->generateAddView('calendar');
    }

    /**
     * Calendar Edit Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function editAction() {
        /**
         * You can just use the default function and customize it via hooks
         * or replace the entire function if you need more customization
         *
         * Hooks available:
         *
         * calendar-edit-before (before show edit form)
         * calendar-edit-before-save (before save)
         * calendar-edit-after-save (after save)
         */
        return $this->generateEditView('calendar');
    }

    /**
     * Calendar View Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function viewAction() {
        /**
         * You can just use the default function and customize it via hooks
         * or replace the entire function if you need more customization
         *
         * Hooks available:
         *
         * calendar-view-before
         */
        return $this->generateViewView('calendar');
    }
}
