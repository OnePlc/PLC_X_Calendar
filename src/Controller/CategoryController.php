<?php
/**
 * Created by PhpStorm.
 * User: Praesidiarius
 * Date: 19.03.2019
 * Time: 23:13
 */

namespace Calendar\Controller;

use Application\Controller\CoreController;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;

class CategoryController extends CoreController
{
    public function __construct($oTableGateway,$aPluginTbls = [],$oDbAdapter,$aPlugins = [])
    {
        parent::__construct($oTableGateway,$aPluginTbls,$oDbAdapter,$aPlugins);
        $this->sFormSingle = 'article-category-single';
    }


    public function listAction() {
        $this->layout('layout/json');

        $iFilter = $this->params('id', 0);

        $oCategoriesDB = $this->oTableGateway->fetchAll(false,[]);
        if($iFilter != 0) {
            $oCategoriesDB = $this->oTableGateway->fetchAll(false,['parent_idfs'=>$iFilter]);
        }

        $aReturn = [
            'results' => [],
            'pagination' => (object)['more'=>false],
        ];

        foreach($oCategoriesDB as $oCat) {
            $aReturn['results'][] = (object)[
                'id'=>$oCat->getID(),
                'text'=>$oCat->getLabel(),
                'name'=>$oCat->getName(),
            ];
        }

        $aReturn = (object)$aReturn;

        echo json_encode($aReturn);

        return false;
    }

    public function getAction() {
        $this->layout('layout/json');

        $aInfo = explode('-',$this->params('id', '0-0'));
        $iCategoryID = $aInfo[0];
        $iStateID = 0;
        if(isset($aInfo[1])) {
            $iStateID = $aInfo[1];
        }
        if($iCategoryID == 0) {
            echo 'wrong category';
            return false;
        }

        $oCategory = $this->oTableGateway->getCategory($iCategoryID);

        $aActivePlugins=[];
        foreach(array_keys($this->aPlugins) as $sPlugin) {
            // only field plugins
            if(is_array($this->aPlugins[$sPlugin])) {
                $aPluginInfo = $this->aPlugins[$sPlugin];
                $aActivePlugins[$sPlugin] = $aPluginInfo;
            }
        }

        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        if(array_key_exists('ticket',$this->aPlugins)) {
            $oEvTicketTbl = new TableGateway('event_ticket',$this->oDbAdapter);
            $oTicketTbl = new TableGateway('article',$this->oDbAdapter);
        }

        $oEvSel = new Select($oEventTbl->getTable());
        $oEvSel->join(['plugin_category'=>'event_event_category'],'plugin_category.event_idfs = event.Event_ID');

        $oWh = new Where();
        $oWh->equalTo('plugin_category.category_idfs',$iCategoryID);
        $oWh->equalTo('web_show',1);
        $oEvSel->where($oWh);

        $oEvSel->order('event.date_start ASC');

        $oEvents = $oEventTbl->selectWith($oEvSel);

        $aMyEvents = [];
        foreach($oEvents as $oEv) {
            $aPublicEv = [];
            $aPublicEv['id'] = $oEv->Event_ID;
            $aPublicEv['title'] = $oEv->label;
            $aPublicEv['excerpt'] = $oEv->excerpt;
            $aPublicEv['description'] = $oEv->description;

            $sStart = date('Y-m-d',strtotime($oEv->date_start));
            if(date('H:i:s',strtotime($oEv->date_start)) != '00:00:00') {
                $sStart .= 'T'.date('H:i:s',strtotime($oEv->date_start));
            }
            $sEnd = date('Y-m-d',strtotime($oEv->date_end));
            if(date('H:i:s',strtotime($oEv->date_end)) != '00:00:00') {
                $sEnd .= 'T'.date('H:i:s',strtotime($oEv->date_end));
            }
            $aPublicEv['start'] = $sStart;
            $aPublicEv['end'] = $sEnd;
            $aPublicEv['is_allday_event'] = $oEv->is_daily_event;

            /**
             * Get all event information besides date from root
             * event for child / linked events
             */
            if($oEv->root_event_idfs != 0) {
                $oRoot = $oEventTbl->select(['Event_ID' => $oEv->root_event_idfs]);
                if (count($oRoot) > 0) {
                    $oRoot = $oRoot->current();
                    $aPublicEv['title'] = $oRoot->label;
                    $aPublicEv['excerpt'] = $oRoot->excerpt;
                    $aPublicEv['description'] = $oRoot->description;
                }
            }

            /**
             * Tickets Plugin
             */
            if(array_key_exists('ticket',$this->aPlugins)) {
                $aPublicEv['tickets'] = [];
                $aMyTickets = $oEvTicketTbl->select(['event_idfs'=>$oEv->Event_ID]);
                if(count($aMyTickets) > 0) {
                    foreach($aMyTickets as $oMyTick) {
                        $oTicket = new \Article\Model\Article($this->oDbAdapter,['history']);
                        $oTicketData = $oTicketTbl->select(['Article_ID'=>$oMyTick->article_idfs])->current();
                        $oTicket->exchangeArray((array)$oTicketData);
                        $aPublicEv['tickets'][] = (object)['id'=>$oTicket->getID(),'label'=>$oTicket->getLabel(),'price'=>$oTicket->getPrice(),'slots_total'=>$oMyTick->slots,'slots_free'=>1];
                    }
                }
            }

            $aMyEvents[] = $aPublicEv;
        }

        $aReturn = [
            'category'=>['id'=>$oCategory->getID(),'label'=>$oCategory->getLabel()],
            'events'=>$aMyEvents,
        ];

        echo json_encode($aReturn);

        return false;
    }

    public function indexAction()
    {
        $rustart = getrusage();

        // Get paginated filtered results
        $oPaginator = $this->oTableGateway->fetchAll(true,[]);
        // get paginated total results
        $oPaginatorTotal = $this->oTableGateway->fetchAll(true,[]);

        $oUser = $this->aPluginTbls['user']->getUser(0,$this->identity());

        // Set the current page to what has been passed in query string,
        // or to 1 if none is set, or the page is invalid:
        $page = (int) $this->params()->fromQuery('page', 1);
        $page = ($page < 1) ? 1 : $page;
        $oPaginator->setCurrentPageNumber($page);
        // Set the number of items per page to 10:
        $iItemsPerPage = $oUser->getItemsPerPage();
        $oPaginator->setItemCountPerPage($iItemsPerPage);

        $aFilterSelected = [];

        $ru = getrusage();
        $this->logPerfomance('event-category-index',$this->rutime($ru,RUFIRST,"utime"),$this->rutime($ru,RUFIRST,"stime"));

        return new ViewModel([
            'paginator' => $oPaginator,
            'oPaginatorTotal'=>$oPaginatorTotal,
            'aPlugins'=>$this->aPlugins,
            'aButtons' => $this->getButtons('event-category-index'),
            'aFilterSelected'=>$aFilterSelected,
            'aTableColums'=>$this->getTableColums(true,'event-category-index'),
            'aConfig'=>$this->getConfig('Calendar')['plcx_category_options'],
        ]);
    }

    /**
     * Category Edit Form
     *
     * @return ViewModel
     */
    public function editAction()
    {
        $rustart = getrusage();

        /**
         * Get Category ID
         */
        $id = (int) $this->params()->fromRoute('id', 0);
        if (0 === $id) {
            return $this->redirect()->toRoute('event-category', ['action' => 'add']);
        }

        /**
         * Load Skeleton
         */
        try {
            $oCategory = $this->oTableGateway->getCategory($id);
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('event-category', ['action' => 'index']);
        }

        $oRequest = $this->getRequest();

        /**
         * Show Category Edit Form
         */
        $sFormName = 'event-category-single';
        if (! $oRequest->isPost()) {
            return new ViewModel([
                'id'=>$oCategory,
                'oCategory'=>$oCategory,
                'aPlugins'=>$this->aPlugins,
                'iNextSkeletonID'=>0,
                'aTabs' => $this->getTabs($sFormName),
                'aButtons' => $this->getButtons('event-category-edit',$oCategory),
                'aPluginsByTabs'=>$this->sortPluginsByTabs(true),
            ]);
        }

        /**
         * Save Category
         */
        $aBaseData = $this->parseFormDataToPlugins($oRequest,$sFormName);
        $aBaseData['Category_ID'] = $oRequest->getPost($sFormName.'ID');
        $oCategory->exchangeArray($aBaseData);
        $iSkeletonID = $this->oTableGateway->saveCategory($oCategory);

        /**
         * Upload Skeleton Images
         */
        if(array_key_exists('category_images',$this->aPlugins)) {
            if(array_key_exists($sFormName.'_category_images',$_FILES)) {
                $sTargetDir = $_SERVER['DOCUMENT_ROOT'].'/data/event-category/'.$iSkeletonID;
                if(!is_dir($sTargetDir)) {
                    mkdir($sTargetDir);
                }
                $iCount = 0;
                foreach($_FILES as $aImages) {
                    foreach($aImages['name'] as $sImg) {
                        $sTargetName = $sTargetDir.'/'.$sImg;
                        if (move_uploaded_file($aImages['tmp_name'][$iCount], $sTargetName)) {
                        }
                        $iCount++;
                    }
                }
            }
        }

        $ru = getrusage();
        $this->logPerfomance('event-category-edit',$this->rutime($ru,RUFIRST,"utime"),$this->rutime($ru,RUFIRST,"stime"));

        /**
         * Redirect to Skeleton View Form
         */
        return $this->redirect()->toRoute('event-category', ['action' => 'view','id'=>$oCategory->getID()]);
    }

    /**
     * Category View Form
     *
     * @return ViewModel
     */
    public function viewAction()
    {
        $rustart = getrusage();

        /**
         * Get Category ID
         */
        $id = (int) $this->params()->fromRoute('id', 0);
        if (0 === $id) {
            return $this->redirect()->toRoute('event-category', ['action' => 'add']);
        }

        /**
         * Load Category
         */
        try {
            $oCategory = $this->oTableGateway->getCategory($id);
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('event-category', ['action' => 'index']);
        }

        $oRequest = $this->getRequest();
        $sFormName = 'event-category-single';
        $viewData = [
            'id' => $id,
            'oCategory'=>$oCategory,
            'aPlugins'=>$this->aPlugins,
            'aTabs' => $this->getTabs($sFormName),
            'aButtons' => $this->getButtons('event-category-view',$oCategory),
            'aPluginsByTabs'=>$this->sortPluginsByTabs(true),
        ];

        // Category View Plugins
        $aViewPlugins = [];

        $viewData['aViewPlugins'] = $aViewPlugins;

        $ru = getrusage();
        $this->logPerfomance('event-category-view',$this->rutime($ru,RUFIRST,"utime"),$this->rutime($ru,RUFIRST,"stime"));

        if (! $oRequest->isPost()) {
            return new ViewModel($viewData);
        }
    }

    public function avatarAction() {
        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            $iCategoryID = $this->params('id', 0);
            $oCategory = $this->oTableGateway->getCategory($iCategoryID);

            return [
                'oCategory' => $oCategory,
            ];
        } else {
            $iCategoryID = $oRequest->getPost('category_idfs');

            // Upload new image
            $sReportDir = $_SERVER['DOCUMENT_ROOT'].'/data/event-category/'.$iCategoryID.'/';
            if(!is_dir($sReportDir)) {
                mkdir($sReportDir,0777,true);
            }
            $sFileName = 'avatar.png';
            foreach($oRequest->getFiles() as $oFile) {
                $sFinalname = $sReportDir.'/'.$sFileName;
                if (move_uploaded_file($oFile['tmp_name'], $sFinalname)) {
                    // done
                }
            }

            // Back to Category View
            $this->flashMessenger()->addSuccessMessage('avatar updated successfully');
            return $this->redirect()->toRoute('event-category',['action'=>'view','id'=>$iCategoryID]);
        }
    }
}