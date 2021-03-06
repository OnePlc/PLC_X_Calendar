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
     * Fetch All Calendar Entities based on Filters
     *
     * @param bool $bPaginated
     * @return Paginator Paginated Table Connection
     * @since 1.0.0
     */
    public function fetchAll($bPaginated = false) {
        $oSel = new Select($this->oTableGateway->getTable());

        # Return Paginator or Raw ResultSet based on selection
        if ($bPaginated) {
            # Create result set for user entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Calendar($this->oTableGateway->getAdapter()));

            # Create a new pagination adapter object
            $oPaginatorAdapter = new DbSelect(
            # our configured select object
                $oSel,
                # the adapter to run it against
                $this->oTableGateway->getAdapter(),
                # the result set to hydrate
                $resultSetPrototype
            );
            # Create Paginator with Adapter
            $oPaginator = new Paginator($oPaginatorAdapter);
            return $oPaginator;
        } else {
            $oResults = $this->oTableGateway->selectWith($oSel);
            return $oResults;
        }
    }

    /**
     * Get Calendar Entity
     *
     * @param int $id
     * @return mixed
     * @since 1.0.0
     */
    public function getSingle($id) {
        $id = (int) $id;
        $rowset = $this->oTableGateway->select(['Calendar_ID' => $id]);
        $row = $rowset->current();
        if (! $row) {
            throw new \RuntimeException(sprintf(
                'Could not find calendar with identifier %d',
                $id
            ));
        }

        return $row;
    }

    /**
     * Save Calendar Entity
     *
     * @param Calendar $oCalendar
     * @return int Calendar ID
     * @since 1.0.0
     */
    public function saveSingle(Calendar $oCalendar) {
        $aData = [
            'label' => $oCalendar->label,
        ];

        $aData = $this->attachDynamicFields($aData,$oCalendar);

        $id = (int) $oCalendar->id;

        if ($id === 0) {
            # Add Metadata
            $aData['created_by'] = CoreController::$oSession->oUser->getID();
            $aData['created_date'] = date('Y-m-d H:i:s',time());
            $aData['modified_by'] = CoreController::$oSession->oUser->getID();
            $aData['modified_date'] = date('Y-m-d H:i:s',time());

            # Insert Calendar
            $this->oTableGateway->insert($aData);

            # Return ID
            return $this->oTableGateway->lastInsertValue;
        }

        # Check if Calendar Entity already exists
        try {
            $this->getSingle($id);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(sprintf(
                'Cannot update calendar with identifier %d; does not exist',
                $id
            ));
        }

        # Update Metadata
        $aData['modified_by'] = CoreController::$oSession->oUser->getID();
        $aData['modified_date'] = date('Y-m-d H:i:s',time());

        # Update Calendar
        $this->oTableGateway->update($aData, ['Calendar_ID' => $id]);

        return $id;
    }
}