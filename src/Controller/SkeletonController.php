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

use Application\Controller\CoreController;
use Application\Model\CoreEntityModel;
use OnePlace\Calendar\Model\Calendar;
use OnePlace\Calendar\Model\CalendarTable;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;

class CalendarController extends CoreController {
    /**
     * Calendar Table Object
     *
     * @since 1.0.0
     */
    private $oTableGateway;

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
        # Set Layout based on users theme
        $this->setThemeBasedLayout('calendar');

        # Add Buttons for breadcrumb
        $this->setViewButtons('calendar-index');

        # Set Table Rows for Index
        $this->setIndexColumns('calendar-index');

        # Get Paginator
        $oPaginator = $this->oTableGateway->fetchAll(true);
        $iPage = (int) $this->params()->fromQuery('page', 1);
        $iPage = ($iPage < 1) ? 1 : $iPage;
        $oPaginator->setCurrentPageNumber($iPage);
        $oPaginator->setItemCountPerPage(3);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('calendar-index',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'sTableName'=>'calendar-index',
            'aItems'=>$oPaginator,
        ]);
    }

    /**
     * Calendar Add Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function addAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('calendar');

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Add Form
        if(!$oRequest->isPost()) {
            # Add Buttons for breadcrumb
            $this->setViewButtons('calendar-single');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance('calendar-add',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            return new ViewModel([
                'sFormName' => $this->sSingleForm,
            ]);
        }

        # Get and validate Form Data
        $aFormData = $this->parseFormData($_REQUEST);

        # Save Add Form
        $oCalendar = new Calendar($this->oDbAdapter);
        $oCalendar->exchangeArray($aFormData);
        $iCalendarID = $this->oTableGateway->saveSingle($oCalendar);
        $oCalendar = $this->oTableGateway->getSingle($iCalendarID);

        # Save Multiselect
        $this->updateMultiSelectFields($_REQUEST,$oCalendar,'calendar-single');

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('calendar-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New Calendar
        $this->flashMessenger()->addSuccessMessage('Calendar successfully created');
        return $this->redirect()->toRoute('calendar',['action'=>'view','id'=>$iCalendarID]);
    }

    /**
     * Calendar Edit Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function editAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('calendar');

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Edit Form
        if(!$oRequest->isPost()) {

            # Get Calendar ID from URL
            $iCalendarID = $this->params()->fromRoute('id', 0);

            # Try to get Calendar
            try {
                $oCalendar = $this->oTableGateway->getSingle($iCalendarID);
            } catch (\RuntimeException $e) {
                echo 'Calendar Not found';
                return false;
            }

            # Attach Calendar Entity to Layout
            $this->setViewEntity($oCalendar);

            # Add Buttons for breadcrumb
            $this->setViewButtons('calendar-single');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance('calendar-edit',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            return new ViewModel([
                'sFormName' => $this->sSingleForm,
                'oCalendar' => $oCalendar,
            ]);
        }

        $iCalendarID = $oRequest->getPost('Item_ID');
        $oCalendar = $this->oTableGateway->getSingle($iCalendarID);

        # Update Calendar with Form Data
        $oCalendar = $this->attachFormData($_REQUEST,$oCalendar);

        # Save Calendar
        $iCalendarID = $this->oTableGateway->saveSingle($oCalendar);

        $this->layout('layout/json');

        # Save Multiselect
        $this->updateMultiSelectFields($_REQUEST,$oCalendar,'calendar-single');

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('calendar-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New User
        $this->flashMessenger()->addSuccessMessage('Calendar successfully saved');
        return $this->redirect()->toRoute('calendar',['action'=>'view','id'=>$iCalendarID]);
    }

    /**
     * Calendar View Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function viewAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('calendar');

        # Get Calendar ID from URL
        $iCalendarID = $this->params()->fromRoute('id', 0);

        # Try to get Calendar
        try {
            $oCalendar = $this->oTableGateway->getSingle($iCalendarID);
        } catch (\RuntimeException $e) {
            echo 'Calendar Not found';
            return false;
        }

        # Attach Calendar Entity to Layout
        $this->setViewEntity($oCalendar);

        # Add Buttons for breadcrumb
        $this->setViewButtons('calendar-view');

        # Load Tabs for View Form
        $this->setViewTabs($this->sSingleForm);

        # Load Fields for View Form
        $this->setFormFields($this->sSingleForm);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('calendar-view',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'sFormName'=>$this->sSingleForm,
            'oCalendar'=>$oCalendar,
        ]);
    }
}
