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
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class EventController extends AbstractActionController
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

    public function quickeditAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id',0);
        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEventTbl->select(['Event_ID'=>$iEventID])->current();
        $sMode = 'modal';
        $sGoTo = 'general';
        if(array_key_exists('goto',$_REQUEST)) {
            $sGoTo = $_REQUEST['goto'];
        }

        return [
            'oEvent'=>$oEvent,
            'sMode'=>$sMode,
            'sGoTo'=>$sGoTo,
        ];
    }

    public function updateAction() {
        $oRequest = $this->getRequest();
        if(!$oRequest->isPost()) {

        } else {
            $this->layout('layout/json');

            $iEventID = $oRequest->getPost('event_id');
            $sMode = $oRequest->getPost('mode');
            $sDate = $oRequest->getPost('quickedit_date').' '.$oRequest->getPost('quickedit_time');
            $sDateEnd = $oRequest->getPost('quickedit_date_end').' '.$oRequest->getPost('quickedit_time_end');
            $sGoTo = $oRequest->getPost('quickedit_goto');
            $sTitle = $oRequest->getPost('quickedit_title');
            $sExcerpt = $oRequest->getPost('quickedit_excerpt');
            $sDescription = $oRequest->getPost('quickedit_description');

            $oEventTbl = new TableGateway('event',$this->oDbAdapter);

            $aUpdData = [];
            if($sDate != '' && $sDateEnd != '' && $sExcerpt == '' && $sDescription == '' && $sTitle == '') {
                $aUpdData['date_start'] = date('Y-m-d H:i',strtotime($sDate));
                $aUpdData['date_end'] = date('Y-m-d H:i',strtotime($sDateEnd));
            }
            if($sTitle != '') {
                $aUpdData['label'] = $sTitle;
            }
            if($sExcerpt != '') {
                $aUpdData['excerpt'] = $sExcerpt;
            }
            if($sDescription != '') {
                $aUpdData['description'] = $sDescription;
            }
            $oEventTbl->update($aUpdData,'Event_ID = '.$iEventID);

            if($sMode == 'modal') {
                echo 'success';
                return false;
            } else {
                $this->flashMessenger()->addSuccessMessage('Event erfolgreich aktualisiert');
                return $this->redirect()->toRoute('calendar',['action'=>$sGoTo]);
            }

        }
    }

    public function deleteAction()
    {
        $oRequest = $this->getRequest();
        $oEventTbl = new TableGateway('event',$this->oDbAdapter);

        if ($oRequest->isPost()) {
            $del = $oRequest->getPost('del', 'No');
            $sGoTo = $oRequest->getPost('goto_link');

            if ($del == 'Yes') {
                $id = (int) $oRequest->getPost('id');
                $oEventTbl->delete(['Event_ID'=>$id]);
                $this->flashMessenger()->addSuccessMessage('Event erfolgreich gelöscht');
            }

            // Redirect to list of contacts
            return $this->redirect()->toRoute('calendar',['action'=>$sGoTo]);
        }

        $id = $this->params()->fromRoute('id', 0);
        $aInfo = explode('-',$id);
        $sGoTo = 'general';
        if(isset($aInfo[1])) {
            $sGoTo = $aInfo[1];
            $id = (int)$aInfo[0];
        }
        if (!$id) {
            return $this->redirect()->toRoute('calendar',['action'=>$sGoTo]);
        }

        return [
            'id'    => $id,
            'sGoTo'=>$sGoTo,
            'event' => $oEventTbl->select(['Event_ID'=>$id])->current(),
        ];
    }

    public function viewAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id',0);

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEventTbl->select(['Event_ID'=>$iEventID])->current();
        $oChildSel = new Select($oEventTbl->getTable());
        $oChildSel->where(['root_event_idfs'=>$iEventID]);
        $oChildSel->order('date_start ASC');
        $aChildren = $oEventTbl->selectWith($oChildSel);
        $sMode = 'modal';
        if(array_key_exists('mode',$_REQUEST)) {
            $sMode = $_REQUEST['mode'];
        }
        $sGoTo = 'general';
        if(array_key_exists('goto',$_REQUEST)) {
            $sGoTo = $_REQUEST['goto'];
        }

        $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);
        $oTicketTbl = new TableGateway('article',$this->oDbAdapter);

        /**
         * Tickets Plugin
         */
        $aTickets = [];
        if(array_key_exists('ticket',$this->aPlugins)) {
            $aMyTickets = $oEvTicketTbl->select(['event_idfs'=>$oEvent->Event_ID]);
            if(count($aMyTickets) > 0) {
                foreach($aMyTickets as $oMyTick) {
                    $oTicket = new \Article\Model\Article($this->oDbAdapter,['history']);
                    $oTicketData = $oTicketTbl->select(['Article_ID'=>$oMyTick->article_idfs])->current();
                    $oTicket->exchangeArray((array)$oTicketData);
                    $aTickets[] = (object)[
                        'id'=>$oTicket->getID(),
                        'label'=>$oTicket->getLabel(),
                        'price'=>$oTicket->getPrice(),
                        'slots_total'=>$oMyTick->slots,
                        'slots_free'=>1,
                        'fully_booked'=>$oMyTick->fully_booked,
                    ];
                }
            }
        }

        // Load info from parent if child event
        $oRoot = (object)[];
        if($oEvent->root_event_idfs != 0) {
            $oRoot = $oEventTbl->select(['Event_ID'=>$oEvent->root_event_idfs])->current();
            $oEvent->label = $oRoot->label;
            $oEvent->excerpt = $oRoot->excerpt;
            $oEvent->description = $oRoot->description;
        }

        return [
            'oEvent'=>$oEvent,
            'sMode'=>$sMode,
            'sGoTo'=>$sGoTo,
            'aPlugins'=>$this->aPlugins,
            'aTickets'=>$aTickets,
            'aChildren'=>$aChildren,
            'oRoot'=>$oRoot,
        ];
    }

    public function addAction() {
        $oRequest = $this->getRequest();
        if(!$oRequest->isPost()) {
            $sMode = 'default';
            if(isset($_REQUEST['mode'])) {
                if($_REQUEST['mode'] == 'modal') {
                    $sMode = 'modal';
                }
            }
            $sDate = '';
            $sTime = '';
            if(isset($_REQUEST['date'])) {
                $sDate = date('Y-m-d',strtotime($_REQUEST['date']));
                $sTime = date('H:i',strtotime($_REQUEST['date'])-7200);
            }

            if($sMode == 'modal') {
                $this->layout('layout/json');
            }

            $aInfo = explode('-',$this->params('id','0-default'));
            $iRefID = $aInfo[0];
            $sRefType = $aInfo[1];
            $oRef = false;

            # Load Calendars
            $oCalTbl = new TableGateway('event_calendar',$this->oDbAdapter);
            $aCalendarsDB = $this->oCalendarTbl->fetchAll(false,[]);
            $aCalendars = [];
            $iCalendarSelected = 0;
            $aEventSources = [];

            switch($sRefType) {
                case 'room':
                    $oRef = $this->aPluginTbls['room']->getRoom($iRefID);
                    $iCalendarSelected = 2;
                    break;
                case 'resident':
                    $oCal = $this->oCalendarTbl->getCalendar(3);
                    $oRef = $this->aPluginTbls['resident']->getResident($iRefID);
                    $iCalendarSelected = 3;
                    $aEventSources[] = (object)[
                        'url' => '/calendar/load/3-resident-'.$iRefID,
                        'color' => $oCal->getColor('background'),
                        'textColor' => $oCal->getColor('text'),
                    ];
                    break;
                default:
                    foreach($aCalendarsDB as $oCal) {
                        $aCalendars[] = $oCal;
                        $aEventSources[] = (object)[
                            'url' => '/calendar/load/'.$oCal->getID(),
                            'color' => $oCal->getColor('background'),
                            'textColor' => $oCal->getColor('text'),
                        ];
                    }
                    break;
            }

            return [
                'sMode'=>$sMode,
                'oRef' => $oRef,
                'sDate'=> $sDate,
                'sTime'=>$sTime,
                'sRefType'=>$sRefType,
                'aCalendars'=>$aCalendars,
                'iCalendarSelected'=>$iCalendarSelected,
                'aEventSources'=>$aEventSources,
                'aPlugins'=>$this->aPlugins,
            ];
        } else {
            $sMode = $oRequest->getPost('window_mode');
            $iRefID = 0;
            $sRefType = 'none';
            if(array_key_exists('resident_idfs',$_REQUEST)) {
                if($oRequest->getPost('resident_idfs') != 0 && is_numeric($oRequest->getPost('resident_idfs'))) {
                    $sRefType = 'resident';
                    $iRefID = $oRequest->getPost('resident_idfs');
                }
            }
            if(array_key_exists('room_idfs',$_REQUEST)) {
                if($oRequest->getPost('room_idfs') != 0 && is_numeric($oRequest->getPost('room_idfs'))) {
                    $sRefType = 'room';
                    $iRefID = $oRequest->getPost('room_idfs');
                }
            }
            $sTitle = $oRequest->getPost('label');
            $sExcerpt = strip_tags($oRequest->getPost('excerpt'));
            $sDescription = strip_tags($oRequest->getPost('description'));
            $iTaskID = $oRequest->getPost('task_idfs');
            $iCalendarID = $oRequest->getPost('calendar_idfs');
            $sDate = $oRequest->getPost('date_start');
            $bDaily = 1;
            if($oRequest->getPost('time_start') != '' && $oRequest->getPost('time_start') != '00:00') {
                $sDate .= ' '.$oRequest->getPost('time_start');
                $bDaily = 0;
            }
            $sDateEnd = $oRequest->getPost('date_end');
            if($oRequest->getPost('time_end') != '' && $oRequest->getPost('time_end') != '00:00') {
                $sDateEnd .= ' '.$oRequest->getPost('time_end');
                $bDaily = 0;
            }

            $sStart = date('Y-m-d',strtotime($sDate));

            $oEventTbl = new TableGateway('event',$this->oDbAdapter);
            $oEventTbl->insert([
                'date_start' => date('Y-m-d H:i:s',strtotime($sDate)),
                'date_end' => date('Y-m-d H:i:s',strtotime($sDateEnd)),
                'is_daily_event' => $bDaily,
                'label' => $sTitle,
                'excerpt'=>$sExcerpt,
                'description' => $sDescription,
                'ref_idfs' => $iRefID,
                'ref_type' => $sRefType,
                'calendar_idfs' => $iCalendarID,
                'task_idfs' => ($iTaskID == null) ? 0 : $iTaskID,
                'task_done' => 0,
                'created_by'=>$this->layout()->oUser->getID(),
                'created_date'=>date('Y-m-d H:i:s',time()),
                'modified_by'=>$this->layout()->oUser->getID(),
                'modified_date'=>date('Y-m-d H:i:s',time()),
            ]);

            // Back to Resident View
            $this->flashMessenger()->addSuccessMessage('Event erfolgreich erstellt');

            if($sMode == 'modal') {
                return $this->redirect()->toRoute('calendar',['action'=>'general']);
            } else {
                if($sRefType == 'none') {
                    return $this->redirect()->toRoute('calendar',['action'=>'general','id'=>$sStart]);
                } else {
                    return $this->redirect()->toRoute('calendar',['action'=>'general','id'=>$sStart]);
                    //return $this->redirect()->toRoute($sRefType,['action'=>'view','id'=>$iRefID]);
                }
            }
        }
    }

    public function togglewebAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id', 0);

        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEvTbl->select(['Event_ID'=>$iEventID]);
        if(count($oEvent) > 0) {
            $oEvent = $oEvent->current();
            $bNewVal = ($oEvent->web_show == 1) ? 0 : 1;
            $oEvTbl->update(['web_show'=>$bNewVal],['Event_ID'=>$iEventID]);
            $oEvTbl->update(['web_show'=>$bNewVal],['root_event_idfs'=>$iEventID]);
            $sMsg = 'Event wird nun auf Webseite angezeigt';
            if($bNewVal == 0) {
                $sMsg = 'Event wird nicht mehr auf Webseite angezeigt';
            }

            $aReturn = (object)[
                'state'=>'success',
                'message'=>$sMsg,
                'newstate'=>$bNewVal,
            ];
        } else {
            $aReturn = (object)[
                'state'=>'error',
                'message'=>'Not found',
            ];
        }

        echo json_encode($aReturn);

        return false;
    }

    public function togglewebspotlightAction() {
        $this->layout('layout/json');

        $oRequest = $this->getRequest();

        $iEventID = $this->params('id', 0);

        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEvTbl->select(['Event_ID'=>$iEventID]);
        if(count($oEvent) > 0) {
            $oEvent = $oEvent->current();
            $bNewVal = ($oEvent->web_spotlight == 1) ? 0 : 1;
            $oEvTbl->update(['web_spotlight'=>$bNewVal],['Event_ID'=>$iEventID]);
            $sMsg = 'Event wurde als Highlight markiert';
            if($bNewVal == 0) {
                $sMsg = 'Event ist kein Highlight mehr';
            }

            $aReturn = (object)[
                'state'=>'success',
                'message'=>$sMsg,
                'newstate'=>$bNewVal,
            ];
        } else {
            $aReturn = (object)[
                'state'=>'error',
                'message'=>'Not found',
            ];
        }

        if($oRequest->isPost()) {
            echo json_encode($aReturn);
        } else {
            return $this->redirect()->toRoute('events',['action'=>'spotlight']);
        }

        return false;
    }

    public function spotlightAction() {

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);

        $oEvSel = new Select($oEventTbl->getTable());
        $oEvSel->where(['web_spotlight'=>1]);
        $oEvSel->order('date_start ASC');

        $oEvents = $oEventTbl->selectWith($oEvSel);

        return [
            'oEvents'=>$oEvents,
        ];
    }

    public function tooglebookingAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id', 0);

        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);

        $oEvent = $oEvTbl->select(['Event_ID'=>$iEventID]);

        if(count($oEvent) > 0) {
            $oEvent = $oEvent->current();
            $aTickets = $oEvTicketTbl->select(['event_idfs'=>$oEvent->Event_ID]);
            if(count($aTickets) > 0) {
                foreach($aTickets as $oTk) {
                    $bNewVal = ($oTk->fully_booked == 1) ? 0 : 1;
                    $oEvTicketTbl->update(['fully_booked'=>$bNewVal],['event_idfs'=>$oTk->event_idfs,'article_idfs'=>$oTk->article_idfs]);
                }
            }

            $sMsg = 'Event kann nun gebucht werden';
            if($bNewVal == 1) {
                $sMsg = 'Event kann nicht mehr gebucht werden';
            }

            $aReturn = (object)[
                'state'=>'success',
                'message'=>$sMsg,
                'newstate'=>$bNewVal,
            ];
        } else {
            $aReturn = (object)[
                'state'=>'error',
                'message'=>'Not found',
            ];
        }

        echo json_encode($aReturn);

        return false;
    }

    public function addticketAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id',0);

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEventTbl->select(['Event_ID'=>$iEventID])->current();

        return [
            'oEvent'=>$oEvent,
        ];
    }

    public function editticketAction() {
        $this->layout('layout/json');

        $iTicketID = $this->params('id',0);

        $oTicketTbl = new TableGateway('article',$this->oDbAdapter);
        $oTicketEvTbl = new TableGateway('event_ticket',$this->oDbAdapter);
        $oTicket = $this->aPluginTbls['ticket']->getArticle($iTicketID);
        $oTicketInfo = $oTicketEvTbl->select(['article_idfs'=>$iTicketID])->current();
        $oTicket->slots = $oTicketInfo->slots;

        return [
            'oTicket'=>$oTicket,
        ];
    }

    public function saveticketAction() {
        $this->layout('layout/json');

        $oRequest = $this->getRequest();

        $iEventID = $oRequest->getPost('event_idfs');
        $sLabel = $oRequest->getPost('label');
        $dPrice = $oRequest->getPost('price');
        $iSlots = $oRequest->getPost('slots');

        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);
        $oTicketTbl = new TableGateway('article',$this->oDbAdapter);
        $oTicketPriceTbl = new TableGateway('article_pricehistory',$this->oDbAdapter);

        $oUser = $this->aPluginTbls['user']->getUser(0,$this->identity());

        if(isset($_REQUEST['article_idfs'])) {
            $iTicketID = $_REQUEST['article_idfs'];

            $oTicketTbl->update(['label'=>$sLabel],['Article_ID'=>$iTicketID]);

            $oEvTicketTbl->update(['slots'=>$iSlots],['article_idfs'=>$iTicketID]);
        } else {
            $oTicketTbl->insert([
                'unit_idfs'=>0,
                'show_on_web_idfs'=>0,
                'label'=>$sLabel,
                'excerpt'=>'',
                'article_ref_nr'=>'',
                'description'=>'',
                'price'=>0,
                'created_by'=>$oUser->getID(),
                'created_date'=>date('Y-m-d H:i:s',time()),
                'modified_by'=>$oUser->getID(),
                'modified_date'=>date('Y-m-d H:i:s',time()),
            ]);

            $iTicketID = $oTicketTbl->lastInsertValue;

            $oEvTicketTbl->insert([
                'event_idfs'=>$iEventID,
                'article_idfs'=>$iTicketID,
                'slots'=>$iSlots,
                'fully_booked'=>0,
            ]);

            // Add Tickets for child
            $oChildEvs = $oEvTbl->select(['root_event_idfs'=>$iEventID]);
            if(count($oChildEvs) > 0) {
                foreach($oChildEvs as $oChild) {
                    $oEvTicketTbl->insert([
                        'event_idfs'=>$oChild->Event_ID,
                        'article_idfs'=>$iTicketID,
                        'slots'=>$iSlots,
                        'fully_booked'=>0,
                    ]);
                }
            }
        }

        $oTicketPriceTbl->insert([
            'article_idfs'=>$iTicketID,
            'variant_id'=>0,
            'supplier_idfs'=>0,
            'amount'=>1,
            'date'=>date('Y-m-d H:i:s',time()),
            'price'=>(float)$dPrice,
            'created_by'=>$oUser->getID(),
            'created_date'=>date('Y-m-d H:i:s',time()),
            'modified_by'=>$oUser->getID(),
            'modified_date'=>date('Y-m-d H:i:s',time()),
        ]);

        echo 'success';

        return false;
    }

    public function saverepeatAction() {
        $this->layout('layout/json');

        $oRequest = $this->getRequest();

        // Get form data
        $iEventID = $oRequest->getPost('event_idfs');
        $dDateStart = $oRequest->getPost('date_start');
        $sTimeStart = $oRequest->getPost('time_start');
        $dDateEnd = $oRequest->getPost('date_end');
        $sTimeEnd = $oRequest->getPost('time_end');

        // init tables
        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEvTbl->select(['Event_ID'=>$iEventID])->current();
        $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);

        // add child event
        $oEvTbl->insert([
            'date_start'=>date('Y-m-d H:i',strtotime($dDateStart.' '.$sTimeStart)),
            'date_end'=>date('Y-m-d H:i',strtotime($dDateEnd.' '.$sTimeEnd)),
            'is_daily_event'=>0,
            'label'=>'Repeat Event',
            'excerpt'=>'Repeat',
            'ref_idfs'=>0,
            'ref_type'=>'',
            'task_idfs'=>0,
            'task_done'=>0,
            'description'=>'Repeat',
            'root_event_idfs'=>$iEventID,
            'calendar_idfs'=>1,
            'web_show'=>$oEvent->web_show,
            'web_spotlight'=>$oEvent->web_spotlight,
            'created_by'=>$this->layout()->oUser->getID(),
            'created_date'=>date('Y-m-d H:i:s',time()),
            'modified_by'=>$this->layout()->oUser->getID(),
            'modified_date'=>date('Y-m-d H:i:s',time()),
        ]);

        // get child id
        $iChildEvID = $oEvTbl->lastInsertValue;

        // add event tickets for child
        $aEventTickets = $oEvTicketTbl->select(['event_idfs'=>$iEventID]);
        if(count($aEventTickets) > 0) {
            foreach($aEventTickets as $oTk) {
                $oEvTicketTbl->insert([
                    'event_idfs'=>$iChildEvID,
                    // copy ticket from parent
                    'article_idfs'=>$oTk->article_idfs,
                    // todo: make slots dynamic per child
                    'slots'=>$oTk->slots,
                    'fully_booked'=>0,
                ]);
            }
        }

        echo 'success';

        return false;
    }

    public function addrepeatAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id',0);

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEventTbl->select(['Event_ID'=>$iEventID])->current();

        return [
            'oEvent'=>$oEvent,
        ];
    }

    public function rmchildAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id',0);

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEventTbl->select(['Event_ID'=>$iEventID])->current();

        // basic check
        if($oEvent->root_event_idfs != 0) {
            $oEventTbl->delete(['Event_ID'=>$iEventID]);
        }

        $aReturn = (object)[
            'state'=>'success',
            'message'=>'Done',
        ];

        echo json_encode($aReturn);

        return false;
    }

    public function quicksearchAction() {
        $this->layout('layout/json');

        $iFilterCategoryID = $this->params('id', 0);

        $sFilter = $this->params('filter','');
        $sTerm = ($sFilter != '') ? $sFilter : false;
        if($sTerm == 'undefined') {
            $sTerm = false;
        }
        if(isset($_REQUEST['term'])) {
            if($_REQUEST['term'] != '') {
                $sFilter = $_REQUEST['term'];
                $sTerm = $sFilter;
            }
        }

        $oEvTbl = new TableGateway('event',$this->oDbAdapter);

        $oEvSel = new Select($oEvTbl->getTable());
        $oEvWh = new Where();

        //$oWhere = new Where();
        $aWhere = [];
        $oEvWh->equalTo('root_event_idfs',0);
        if($sTerm) {
            $oEvWh->like('label','%'.trim($sFilter).'%');
        }
        if($iFilterCategoryID) {
            $oEvSel->join(['plugin_category'=>'event_event_category'],'plugin_category.event_idfs = event.Event_ID');
            $oEvWh->equalTo('plugin_category.category_idfs',$iFilterCategoryID);
        }

        $oEvSel->where($oEvWh);
        $oEvSel->order('date_start ASC');

        $oCategoriesDB = $oEvTbl->selectWith($oEvSel);

        $aReturn = [
            'results' => [],
            'pagination' => (object)['more'=>false],
        ];

        foreach($oCategoriesDB as $oCat) {
            $aReturn['results'][] = (object)[
                'id'=>$oCat->Event_ID,
                'text'=>$oCat->label.' - '.date('d.m.Y',strtotime($oCat->date_start)),
            ];
        }

        $aReturn = (object)$aReturn;

        echo json_encode($aReturn);

        return false;
    }

    public function getAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id', 0);

        $oTicketTbl = new TableGateway('article',$this->oDbAdapter);
        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);

        $oEvent = $oEvTbl->select(['Event_ID'=>$iEventID]);
        $aPublicEv = [];
        if(count($oEvent) > 0 ){
            // found
            $oEvent = $oEvent->current();
            $aPublicEv['id'] = $oEvent->Event_ID;
            $aPublicEv['label'] = $oEvent->label;
            $aPublicEv['excerpt'] = $oEvent->excerpt;
            $aPublicEv['description'] = $oEvent->description;
            $aPublicEv['date_start'] = $oEvent->date_start;
            $aPublicEv['date_end'] = $oEvent->date_end;

            /**
             * Child Events Plugin
             */
            if($oEvent->root_event_idfs != 0) {
                $oRoot = $oEvTbl->select(['Event_ID'=>$oEvent->root_event_idfs]);
                if(count($oRoot) > 0) {
                    $oRoot = $oRoot->current();
                    $aPublicEv['label'] = $oRoot->label;
                    $aPublicEv['excerpt'] = $oRoot->excerpt;
                    $aPublicEv['description'] = $oRoot->description;
                }
            }

            /**
             * Tickets Plugin
             */
            $aTickets = [];
            if(array_key_exists('ticket',$this->aPlugins)) {
                $aMyTickets = $oEvTicketTbl->select(['event_idfs'=>$oEvent->Event_ID]);
                if(count($aMyTickets) > 0) {
                    foreach($aMyTickets as $oMyTick) {
                        $oTicket = new \Article\Model\Article($this->oDbAdapter,['history']);
                        $oTicketData = $oTicketTbl->select(['Article_ID'=>$oMyTick->article_idfs])->current();
                        $oTicket->exchangeArray((array)$oTicketData);
                        $aTickets[] = (object)[
                            'id'=>$oTicket->getID(),
                            'label'=>$oTicket->getLabel(),
                            'price'=>$oTicket->getPrice(),
                            'slots_total'=>$oMyTick->slots,
                            'slots_free'=>1,
                            'fully_booked'=>$oMyTick->fully_booked,
                        ];
                    }
                }
                $aPublicEv['tickets'] = $aTickets;
            }

            $aReturn = ['state'=>'success','event'=>$aPublicEv];
        } else {
            // not found
            $aReturn = ['state'=>'error','message'=>'event not found'];
        }

        echo json_encode($aReturn);

        return false;
    }

    public function geticsAction() {
        $this->layout('layout/json');

        $iEventID = $this->params('id');

        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvent = $oEvTbl->select(['Event_ID'=>$iEventID]);

        if(count($oEvent) == 0) {
            echo 'Event not found';
            return false;
        }

        $oEvent = $oEvent->current();
        /**
         * Child Events Plugin
         */
        if($oEvent->root_event_idfs != 0) {
            $oRoot = $oEvTbl->select(['Event_ID'=>$oEvent->root_event_idfs]);
            if(count($oRoot) > 0) {
                $oRoot = $oRoot->current();
                $oEvent->label = $oRoot->label;
                $oEvent->excerpt = $oRoot->excerpt;
                $oEvent->description = $oRoot->description;
            }
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename=event.ics');

        echo "BEGIN:VCALENDAR\n";
        echo "VERSION:2.0\n";
        echo "PRODID:-//Verein onePlace//onePlace V10.0//DE\n";
        echo "CALSCALE:GREGORIAN\n";
        echo "METHOD:PUBLISH\n";
        echo "BEGIN:VEVENT\n";
        echo "DTSTART:" . date('Ymd', strtotime($oEvent->date_start)) . "T" . date('His', strtotime($oEvent->date_start)) . "Z\n";
        echo "DTEND:" . date('Ymd', strtotime($oEvent->date_end)) . "T" . date('His', strtotime($oEvent->date_end)) . "Z\n";
        echo "DTSTAMP:" . date('Ymd', strtotime($oEvent->date_start)) . "T" . date('His', strtotime($oEvent->date_start)) . "Z\n";
        echo "UID:" . md5(uniqid(mt_rand(), true)) . "@1plc.ch\n";
        echo "CREATED:20191109T101015Z\n";
        echo "DESCRIPTION:" . str_replace(['<br/>', '<br>', "\n","\r\n"], [" ", " ", " "," "], html_entity_decode($oEvent->description))."\n";
        echo "LAST-MODIFIED:" . date('Ymd', time()) . "T" . date('His', time()) . "Z\n";
        echo "SEQUENCE:0\n";
        echo "STATUS:CONFIRMED\n";
        echo "SUMMARY:" . $oEvent->label . "\n";
        echo "TRANSP:OPAQUE\n";
        echo "END:VEVENT\n";
        echo "END:VCALENDAR\n";

        return false;
    }

    public function geticsfeedAction() {
        $this->layout('layout/json');


        $oEvTbl = new TableGateway('event',$this->oDbAdapter);
        $oEvents = $oEvTbl->select(['web_show'=>1]);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename=calendar.ics');
        //header('Content-Disposition: attachment; filename=invite_'.strtolower(str_replace([' '],['_'],$oEvent->label)).'.ics');

        echo "BEGIN:VCALENDAR\n";
        echo "VERSION:2.0\n";
        echo "PRODID:-//Verein onePlace//onePlace V10.0//DE\n";
        echo "CALSCALE:GREGORIAN\n";
        echo "METHOD:PUBLISH\n";
        //echo "X-MS-OLK-FORCEINSPECTOROPEN:TRUE\n";
        foreach($oEvents as $oEvent) {
            /**
             * Child Events Plugin
             */
            if($oEvent->root_event_idfs != 0) {
                $oRoot = $oEvTbl->select(['Event_ID'=>$oEvent->root_event_idfs]);
                if(count($oRoot) > 0) {
                    $oRoot = $oRoot->current();
                    $oEvent->label = $oRoot->label;
                    $oEvent->excerpt = $oRoot->excerpt;
                    $oEvent->description = $oRoot->description;
                }
            }
            echo "BEGIN:VEVENT\n";
            echo "DTSTART:" . date('Ymd', strtotime($oEvent->date_start)) . "T" . date('His', strtotime($oEvent->date_start)) . "Z\n";
            echo "DTEND:" . date('Ymd', strtotime($oEvent->date_end)) . "T" . date('His', strtotime($oEvent->date_end)) . "Z\n";
            echo "DTSTAMP:" . date('Ymd', strtotime($oEvent->date_start)) . "T" . date('His', strtotime($oEvent->date_start)) . "Z\n";
            echo "UID:" . md5(uniqid(mt_rand(), true)) . "@1plc.ch\n";
            echo "CREATED:20191109T101015Z\n";
            echo "DESCRIPTION:" . str_replace(['<br/>', '<br>', "\n","\r\n"], [" ", " ", " "," "], html_entity_decode($oEvent->description))."\n";
            echo "LAST-MODIFIED:" . date('Ymd', time()) . "T" . date('His', time()) . "Z\n";
            echo "SEQUENCE:0\n";
            echo "STATUS:CONFIRMED\n";
            echo "SUMMARY:" . $oEvent->label . "\n";
            echo "TRANSP:OPAQUE\n";
            echo "END:VEVENT\n";
        }
        echo "END:VCALENDAR\n";

        return false;
    }
}
