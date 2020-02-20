<?php
/**
 * ExportController.php - Calendar Export Controller
 *
 * Main Controller for Calendar Export
 *
 * @category Controller
 * @package Calendar
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace OnePlace\Calendar\Controller;

use Application\Controller\CoreController;
use Application\Controller\CoreExportController;
use OnePlace\Calendar\Model\CalendarTable;
use Laminas\Db\Sql\Where;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\View\Model\ViewModel;


class ExportController extends CoreExportController
{
    /**
     * ApiController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @param CalendarTable $oTableGateway
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,CalendarTable $oTableGateway,$oServiceManager) {
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);
    }


    /**
     * Dump Calendars to excel file
     *
     * @return ViewModel
     * @since 1.0.0
     */
    public function dumpAction() {
        $this->layout('layout/json');

        # Use Default export function
        $aViewData = $this->exportData('Calendars','calendar');

        # return data to view (popup)
        return new ViewModel($aViewData);
    }
}