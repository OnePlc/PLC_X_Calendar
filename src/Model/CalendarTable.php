<?php
/**
 * CalendarTable.php - Calendar Table
 *
 * Table Model for Calendar
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

use Application\Controller\CoreController;
use Application\Model\CoreEntityTable;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Paginator\Paginator;
use Laminas\Paginator\Adapter\DbSelect;

class CalendarTable extends CoreEntityTable {

    /**
     * CalendarTable constructor.
     *
     * @param TableGateway $tableGateway
     * @since 1.0.0
     */
    public function __construct(TableGateway $tableGateway) {
        parent::__construct($tableGateway);

        # Set Single Form Name
        $this->sSingleForm = 'calendar-single';
    }

    /**
     * Get Calendar Entity
     *
     * @param int $id
     * @param string $sKey
     * @return mixed
     * @since 1.0.0
     */
    public function getSingle($id,$sKey = 'Calendar_ID') {
        # Use core function
        return $this->getSingleEntity($id,$sKey);
    }

    /**
     * Save Calendar Entity
     *
     * @param Calendar $oCalendar
     * @return int Calendar ID
     * @since 1.0.0
     */
    public function saveSingle(Calendar $oCalendar) {
        $aDefaultData = [
            'label' => $oCalendar->label,
        ];

        return $this->saveSingleEntity($oCalendar,'Calendar_ID',$aDefaultData);
    }

    /**
     * Generate new single Entity
     *
     * @return Calendar
     * @since 1.0.0
     */
    public function generateNew() {
        return new Calendar($this->oTableGateway->getAdapter());
    }
}