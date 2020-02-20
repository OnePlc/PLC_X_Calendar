<?php
/**
 * Calendar.php - Calendar Entity
 *
 * Entity Model for Calendar
 *
 * @category Model
 * @package Calendar
 * @author Verein onePlace
 * @copyright (C) 2020 Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace OnePlace\Calendar\Model;

use Application\Model\CoreEntityModel;

class Calendar extends CoreEntityModel {
    public $label;

    /**
     * Calendar constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @since 1.0.0
     */
    public function __construct($oDbAdapter) {
        parent::__construct($oDbAdapter);

        # Set Single Form Name
        $this->sSingleForm = 'calendar-single';

        # Attach Dynamic Fields to Entity Model
        $this->attachDynamicFields();
    }

    /**
     * Set Entity Data based on Data given
     *
     * @param array $aData
     * @since 1.0.0
     */
    public function exchangeArray(array $aData) {
        $this->id = !empty($aData['Calendar_ID']) ? $aData['Calendar_ID'] : 0;
        $this->label = !empty($aData['label']) ? $aData['label'] : '';

        $this->updateDynamicFields($aData);
    }

    public function getLabel() {
        $sLabel = $this->label;
        return $sLabel;
    }


//    public function getDescription() {
//        return $this->description;
//    }
//
//    public function getEvents($aFilters = []) {
//        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
//        $aEvents = [];
//        $oEventsSel = new Select($oEventTbl->getTable());
//        $aWhere = ['calendar_idfs'=>$this->getID()];
//        if(array_key_exists('web_show',$aFilters)) {
//            $aWhere['web_show'] = 1;
//        }
//        if(array_key_exists('web_spotlight',$aFilters)) {
//            $aWhere['web_spotlight'] = 1;
//        }
//        $oEventsSel->where($aWhere);
//        $oEventsSel->order('date_start ASC');
//
//        $oEventsDB = $oEventTbl->selectWith($oEventsSel);
//        foreach($oEventsDB as $oEv) {
//            $aEvents[] = $oEv;
//        }
//        return $aEvents;
//    }
//
    public function getColor($sType) {
        switch($sType) {
            case 'text':
                return $this->color_text;
                break;
            case 'background':
                return $this->color_background;
                break;
            default:
                return '';
                break;
        }
    }


}