<?php
/**
 * CalendarController.php - Calendar Main Controller
 *
 * Main Class for Module Calendar
 *
 * @category Controller
 * @package Calendar
 * @author Nathanael Kammermann
 * @copyright (C) 2019 Nathanael Kammermann <nathanael.kammermann@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 * @since File available since Version 1.0
 */

namespace Calendar\Controller;

use Calendar\Model\Address;
use Calendar\Model\CalendarTable;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Calendar\Form\CalendarForm;
use Calendar\Form\AddressForm;
use Calendar\Model\Calendar;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Where;

class CalendarController extends AbstractActionController
{
    private $oCalendarTbl;
    private $aPluginTbls;
    private $oDbAdapter;
    private $aPlugins;

    public function __construct(CalendarTable $oCalendarTbl,$aPluginTbls,$oDbAdapter,$aPlugins = [])
    {
        $this->oCalendarTbl = $oCalendarTbl;
        $this->aPluginTbls = $aPluginTbls;
        $this->oDbAdapter = $oDbAdapter;
        $this->aPlugins = $aPlugins;
    }

    public function indexAction() {
        return $this->redirect()->toRoute('calendar',['action'=>'general']);
    }

    public function generalAction() {
        $aCalendarsDB = $this->oCalendarTbl->fetchAll(false,[]);

        setlocale(LC_ALL ,'de_DE','de');

        $dJump = $this->params('id','');
        $iEventSelID = 0;
        if($dJump != '' && is_numeric($dJump)) {
            $iEventSelID = $dJump;
            $dJump = '';
        }

        $aEventSources = [];
        $aCalendars = [];
        foreach($aCalendarsDB as $oCal) {
            $aEventSources[] = (object)[
                'url' => '/calendar/load/' . $oCal->getID(),
                'color' => $oCal->getColor('background'),
                'textColor' => $oCal->getColor('text'),
            ];
            $aCalendars[] = $oCal;
        }

        return [
            'aCalendars'=>$aCalendars,
            'aEventSources'=>$aEventSources,
            'dJump'=>$dJump,
            'iEventSelID'=>$iEventSelID,
        ];
    }

    public function residentdayplanAction() {
        $aCalendarsDB = $this->oCalendarTbl->fetchAll(false,[]);
        $aResidents = $this->aPluginTbls['resident']->fetchAll(false,[]);
        $aResiList = [];
        $aEventSources = [];
        foreach($aResidents as $oResi) {
            $aResiList[] = (object)['id'=>$oResi->getID(),'title'=>$oResi->getLabel()];
        }

        $aCalendars = [];
        foreach($aCalendarsDB as $oCal) {
            $aEventSources[] = (object)[
                'url' => '/calendar/load/' . $oCal->getID().'-resident',
                'color' => $oCal->getColor('background'),
                'textColor' => $oCal->getColor('text'),
            ];
            $aCalendars[] = $oCal;
        }

        $oSettingsDB = new TableGateway('settings',$this->oDbAdapter);
        $sMinTime = $oSettingsDB->select(['settings_key'=>'resdayplan-mintime'])->current()->settings_value;
        $sMaxTime = $oSettingsDB->select(['settings_key'=>'resdayplan-maxtime'])->current()->settings_value;

        return [
            'aCalendars'=>$aCalendars,
            'aEventSources'=>$aEventSources,
            'aResiList'=>$aResiList,
            'aEventSources'=>$aEventSources,
            'sMinTime'=>$sMinTime,
            'sMaxTime'=>$sMaxTime,
        ];
    }

    public function roomdayplanAction() {
        $aCalendarsDB = $this->oCalendarTbl->fetchAll(false,[]);
        $aResidents = $this->aPluginTbls['room']->fetchAll(false,[]);
        $aResiList = [];
        $aEventSources = [];
        foreach($aResidents as $oResi) {
            $aResiList[] = (object)['id'=>$oResi->getID(),'title'=>$oResi->getLabel()];
        }

        $aCalendars = [];
        foreach($aCalendarsDB as $oCal) {
            $aEventSources[] = (object)[
                'url' => '/calendar/load/' . $oCal->getID().'-room',
                'color' => $oCal->getColor('background'),
                'textColor' => $oCal->getColor('text'),
            ];
            $aCalendars[] = $oCal;
        }

        return [
            'aCalendars'=>$aCalendars,
            'aEventSources'=>$aEventSources,
            'aResiList'=>$aResiList,
            'aEventSources'=>$aEventSources,
        ];
    }

    public function manageAction()
    {
        $sorting = $this->params('id');
        // Default sort
        $sSort = 'label ASC';
        if(!empty($sorting)) {
            $aSortInfo = explode('-',$sorting);
            switch($aSortInfo[0]) {
                case 0:
                    $sSort = 'label';
                    break;
                case 1:
                    $sSort = 'description';
                    break;
                default:
                    break;
            }

            switch($aSortInfo[1]) {
                case 'A':
                    $sSort .= ' ASC';
                    break;
                case 'D':
                    $sSort .= ' DESC';
                    break;
                default:
                    break;
            }
        }

        // Grab the paginator from the AlbumTable:
        $oPaginator = $this->oCalendarTbl->fetchAll(true,[],$sSort);

        // Set the current page to what has been passed in query string,
        // or to 1 if none is set, or the page is invalid:
        $page = (int) $this->params()->fromQuery('page', 1);
        $page = ($page < 1) ? 1 : $page;
        $oPaginator->setCurrentPageNumber($page);

        // Set the number of items per page to 10:
        $oPaginator->setItemCountPerPage(10);

        return new ViewModel(['paginator' => $oPaginator,'aPlugins'=>$this->aPlugins,'sSort'=>$sSort]);
    }

    public function addAction()
    {
        $oForm = new CalendarForm(null,$this->aPlugins);
        $oForm->get('submit')->setValue('Add');

        // Plugins
        $aPluginForms = [];

        $oRequest = $this->getRequest();

        if (! $oRequest->isPost()) {
            return ['form' => $oForm,'aPlugins'=>$this->aPlugins,'aPluginForms'=>$aPluginForms];
        }

        $oCalendar = new Calendar($this->oDbAdapter,$this->aPlugins,$this->aPluginTbls['user']);
        $oForm->setInputFilter($oCalendar->getInputFilter());
        $oForm->setData($oRequest->getPost());

        if (! $oForm->isValid()) {
            return ['form' => $oForm,'aPlugins'=>$this->aPlugins,'aPluginForms'=>$aPluginForms];
        }

        $oCalendar->exchangeArray($oForm->getData());

        $iCalendarID = $this->oCalendarTbl->saveCalendar($oCalendar);

        return $this->redirect()->toRoute('calendar',['action'=>'view','id'=>$iCalendarID]);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('calendar', ['action' => 'add']);
        }

        // Retrieve the calendar with the specified id. Doing so raises
        // an exception if the calendar is not found, which should result
        // in redirecting to the landing page.
        try {
            $oCalendar = $this->oCalendarTbl->getCalendar($id);
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('calendar', ['action' => 'index']);
        }

        $oForm = new CalendarForm(null,$this->aPlugins);
        $oForm->bind($oCalendar);
        $oForm->get('submit')->setAttribute('value', 'View');

        // Plugins
        $aPluginForms = [];
        $aPluginData = [];

        $oRequest = $this->getRequest();
        $viewData = [
            'id' => $id,
            'form' => $oForm,
            'oCalendar'=>$oCalendar,
            'aPlugins'=>$this->aPlugins,
            'aPluginForms'=>$aPluginForms,
            'aPluginData'=>$aPluginData,
        ];

        if (! $oRequest->isPost()) {
            return $viewData;
        }
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('calendar', ['action' => 'add']);
        }

        // Retrieve the calendar with the specified id. Doing so raises
        // an exception if the calendar is not found, which should result
        // in redirecting to the landing page.
        try {
            $oCalendar = $this->oCalendarTbl->getCalendar($id);
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('calendar', ['action' => 'index']);
        }

        $oForm = new CalendarForm(null,$this->aPlugins);
        $oForm->bind($oCalendar);
        $oForm->get('submit')->setAttribute('value', 'Edit');

        // Plugins
        $aPluginForms = [];
        $aPluginData = [];

        $oRequest = $this->getRequest();
        $viewData = [
            'id' => $id,
            'form' => $oForm,
            'aPlugins'=> $this->aPlugins,
            'aPluginForms'=>$aPluginForms,
            'aPluginData'=>$aPluginData,
            'oCalendar'=>$oCalendar,
        ];

        if (! $oRequest->isPost()) {
            return $viewData;
        }

        $oForm->setInputFilter($oCalendar->getInputFilter());
        $oForm->setData($oRequest->getPost());

        if (! $oForm->isValid()) {
            return $viewData;
        }

        // save calendar (& attached plugins)
        $iCalendarID = $this->oCalendarTbl->saveCalendar($oCalendar);

        // Redirect to calendar view
        return $this->redirect()->toRoute('calendar', ['action' => 'view','id'=>$iCalendarID]);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('calendar');
        }

        $oRequest = $this->getRequest();
        if ($oRequest->isPost()) {
            $del = $oRequest->getPost('del', 'No');

            if ($del == 'Yes') {
                $id = (int) $oRequest->getPost('id');
                $this->oCalendarTbl->deleteCalendar($id);
                $this->flashMessenger()->addSuccessMessage('calendar deleted successfully');
            }

            // Redirect to list of calendars
            return $this->redirect()->toRoute('calendar');
        }

        return [
            'id'    => $id,
            'calendar' => $this->oCalendarTbl->getCalendar($id),
        ];
    }

    public function listAction() {
        $this->layout('layout/json');

        $aCalendars = [];
        $oWhere = new Where();
        $oWhere->like('label', trim($_GET['term']).'%');
        $oCalendarsDB = $this->oCalendarTbl->fetchAll(false,$oWhere,'label ASC');
        foreach($oCalendarsDB as $oCalendar) {
            $aCalendars[] = [
                'id'=>$oCalendar->getID(),
                'value'=>$oCalendar->getLabel(),
            ];
        }

        echo json_encode($aCalendars);

        return false;
    }

    public function loadAction() {
        $this->layout('layout/json');

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);

        $iCalendarID = $this->params('id', 0);
        $aInfo = explode('-',$iCalendarID);
        $sFilter = '';
        $iFilterID = 0;
        if(isset($aInfo[1])) {
            $sFilter = $aInfo[1];
            $iCalendarID = $aInfo[0];
        }
        if(isset($aInfo[2])) {
            $iFilterID = $aInfo[2];
        }
        $aFilters = [];
        $bWebShow = 0;
        if(isset($aInfo[3])) {
            $bWebShow = (int)$aInfo[3];
            if($bWebShow == 1) {
                $aFilters['web_show'] = 1;
            }
        }
        $bWebSpot = 0;
        if(isset($aInfo[4])) {
            $bWebSpot = (int)$aInfo[4];
            if($bWebSpot == 1) {
                $aFilters['web_spotlight'] = 1;
            }
        }
        $oCalendar = $this->oCalendarTbl->getCalendar($iCalendarID);
        if(array_key_exists('ticket',$this->aPlugins)) {
            $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);
            $oTicketTbl = new TableGateway('article',$this->oDbAdapter);
        }

        $oEventsDB = $oCalendar->getEvents($aFilters);
        $aEventsPrinted = [];
        $aEvents = [];
        foreach($oEventsDB as $oEvent) {
            if($bWebShow && strtotime($oEvent->date_start) <= time()) {
                continue;
            }

            $sStart = date('Y-m-d',strtotime($oEvent->date_start));
            if(date('H:i:s',strtotime($oEvent->date_start)) != '00:00:00') {
                $sStart .= 'T'.date('H:i:s',strtotime($oEvent->date_start));
            }
            $sEnd = date('Y-m-d',strtotime($oEvent->date_end));
            if(date('H:i:s',strtotime($oEvent->date_end)) != '00:00:00') {
                $sEnd .= 'T'.date('H:i:s',strtotime($oEvent->date_end));
            }

            $aNewEv = [];
            $aNewEv['id'] = $oEvent->Event_ID;

            if(array_key_exists('ticket',$this->aPlugins)) {
                $aNewEv['tickets'] = [];
                $aMyTickets = $oEvTicketTbl->select(['event_idfs'=>$oEvent->Event_ID]);
                if(count($aMyTickets) > 0) {
                    foreach($aMyTickets as $oMyTick) {
                        $oTicket = new \Article\Model\Article($this->oDbAdapter,['history']);
                        $oTicketData = $oTicketTbl->select(['Article_ID'=>$oMyTick->article_idfs])->current();
                        $oTicket->exchangeArray((array)$oTicketData);

                        $iSlotsFree = 1;
                        if($oMyTick->fully_booked == 1) {
                            $iSlotsFree = 0;
                        }
                        $aNewEv['tickets'][] = (object)[
                            'id'=>$oTicket->getID(),
                            'label'=>$oTicket->getLabel(),
                            'price'=>$oTicket->getPrice(),
                            'slots_total'=>$oMyTick->slots,
                            'slots_free'=>$iSlotsFree];
                    }
                }
            }

            if($oEvent->root_event_idfs != 0) {
                $oRoot = $oEventTbl->select(['Event_ID'=>$oEvent->root_event_idfs]);
                if(count($oRoot) > 0) {
                    $oRoot = $oRoot->current();
                    $aNewEv['title'] = $oRoot->label;
                    $aNewEv['start'] = $sStart;
                    $aNewEv['excerpt'] = $oRoot->excerpt;
                    $aNewEv['description'] = $oRoot->description;
                    $aNewEv['end'] = $sEnd;
                    $aNewEv['is_allday_event'] = $oEvent->is_daily_event;
                    $aEvents[] = $aNewEv;
                }
            } else {
                $aNewEv['title'] = $oEvent->label;
                $aNewEv['start'] = $sStart;
                $aNewEv['excerpt'] = $oEvent->excerpt;
                $aNewEv['description'] = $oEvent->description;
                $aNewEv['end'] = $sEnd;
                $aNewEv['is_allday_event'] = $oEvent->is_daily_event;
                $aEvents[] = $aNewEv;
            }
        }
        /**
        foreach($oEventsDB as $oEvent) {
            if(!array_key_exists($oEvent->Event_ID,$aEventsPrinted)) {
                $aEventsPrinted[$oEvent->Event_ID] = true;
            } else {
                echo 'event already done';
                continue;
            }
            if($bWebShow && strtotime($oEvent->date_start) <= time()) {
                continue;
            }
            $sStart = date('Y-m-d',strtotime($oEvent->date_start));
            if(date('H:i:s',strtotime($oEvent->date_start)) != '00:00:00') {
                $sStart .= 'T'.date('H:i:s',strtotime($oEvent->date_start));
            }
            $sEnd = date('Y-m-d',strtotime($oEvent->date_end));
            if(date('H:i:s',strtotime($oEvent->date_end)) != '00:00:00') {
                $sEnd .= 'T'.date('H:i:s',strtotime($oEvent->date_end));
            }
            if($oEvent->root_event_idfs != 0) {
                $oRoot = $oEventTbl->select(['Event_ID'=>$oEvent->root_event_idfs]);
                if(count($oRoot) > 0) {
                    $oRoot = $oRoot->current();
                    $aNewEv = [
                        'id'=>$oEvent->Event_ID,
                        'title'=>$oRoot->label,
                        'start'=>$sStart,
                        'excerpt'=>$oRoot->excerpt,
                        'description'=>$oRoot->description,
                        'end'=>$sEnd,
                        'is_allday_event'=>$oEvent->is_daily_event,
                    ];
                }
            } else {
                $aNewEv = [
                    'id'=>$oEvent->Event_ID,
                    'title'=>$oEvent->label,
                    'start'=>$sStart,
                    'excerpt'=>$oEvent->excerpt,
                    'description'=>$oEvent->description,
                    'end'=>$sEnd,
                    'is_allday_event'=>$oEvent->is_daily_event,
                ];
            }

            if(array_key_exists('ticket',$this->aPlugins)) {
                $aNewEv['tickets'] = [];
                $aMyTickets = $oEvTicketTbl->select(['event_idfs'=>$oEvent->Event_ID]);
                if(count($aMyTickets) > 0) {
                    foreach($aMyTickets as $oMyTick) {
                        $oTicket = new \Article\Model\Article($this->oDbAdapter,['history']);
                        $oTicketData = $oTicketTbl->select(['Article_ID'=>$oMyTick->article_idfs])->current();
                        $oTicket->exchangeArray((array)$oTicketData);

                        $iSlotsFree = 1;
                        if($oMyTick->fully_booked == 1) {
                            $iSlotsFree = 0;
                        }
                        $aNewEv['tickets'][] = (object)[
                            'id'=>$oTicket->getID(),
                            'label'=>$oTicket->getLabel(),
                            'price'=>$oTicket->getPrice(),
                            'slots_total'=>$oMyTick->slots,
                            'slots_free'=>$iSlotsFree];
                    }
                }
            }
            if($sFilter != '') {
                if($oEvent->ref_type == $sFilter) {
                    if($iFilterID != 0) {
                        if($iFilterID == $oEvent->ref_idfs) {
                            $aNewEv['resourceId'] = $oEvent->ref_idfs;
                            $aEvents[] = (object)$aNewEv;
                        }
                    } else {
                        $aNewEv['resourceId'] = $oEvent->ref_idfs;
                        $aEvents[] = (object)$aNewEv;
                    }
                }
            } else {
                if(isset($_REQUEST['q'])) {
                    $bMatch = strpos($aNewEv['title'],$_REQUEST['q']);
                    if($bMatch === false) {

                    } else {
                        $aEvents[] = (object)$aNewEv;
                    }
                } else {
                    $aEvents[] = (object)$aNewEv;
                }
            }
        } **/

        echo json_encode($aEvents);

        return false;
    }

    public function settingsAction() {
        $oRequest = $this->getRequest();

        $oUser = $this->aPluginTbls['user']->getUser(0,$this->identity());

        if(!$oRequest->isPost()) {
            $oSettingsDB = new TableGateway('settings',$this->oDbAdapter);
            $sMinTime = $oSettingsDB->select(['settings_key'=>'resdayplan-mintime'])->current()->settings_value;
            $sMaxTime = $oSettingsDB->select(['settings_key'=>'resdayplan-maxtime'])->current()->settings_value;

            return [
                'sMinTime'=>$sMinTime,
                'sMaxTime'=>$sMaxTime,
            ];
        } else {
            $this->layout('layout/json');

            $sMinTime = $oRequest->getPost('min_time');
            $sMaxTime = $oRequest->getPost('max_time');
            $sMaxTime = str_replace('00:00:00','24:00:00',$sMaxTime);

            $oSettingsDB = new TableGateway('settings',$this->oDbAdapter);
            $oSettingsDB->update([
                'settings_value'=>$sMinTime,
            ],['settings_key'=>'resdayplan-mintime']);
            $oSettingsDB->update([
                'settings_value'=>$sMaxTime,
            ],['settings_key'=>'resdayplan-maxtime']);


            $this->flashMessenger()->addSuccessMessage('settings saved successfully');

            // Redirect to list of contacts
            return $this->redirect()->toRoute('controlpanel');
        }
    }

    public function checkAction() {
        $this->layout('layout/json');

        $sParams = $this->params('id');
        $aInfo = explode('-',$sParams);

        $iAmount = $aInfo[1];

        $aResponse = (object)['ok'=>'all good','login'=>'Testlogin','amount'=>$iAmount];

        echo json_encode($aResponse);

        return false;
    }
}
