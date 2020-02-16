<?php
/**
 * CalendarTable.php - Calendar Table Model
 *
 * Table Entity Model for Calendar
 * Provides all necessary functions for interaction
 * with the database
 *
 * @category Model
 * @package Calendar
 * @author Nathanael Kammermann
 * @copyright (C) 2019 Nathanael Kammermann <nathanael.kammermann@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 * @since File available since Version 1.0
 */

namespace Calendar\Model;

use RuntimeException;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\TableGateway\TableGateway;

class CalendarTable
{
    private $tableGateway;
    private $aPlugins;
    private $oUser;
    private $userTable;

    public function __construct(TableGatewayInterface $tableGateway,$aPlugins = [],$oUser,$userTable)
    {
        $this->tableGateway = $tableGateway;
        $this->aPlugins = $aPlugins;
        $this->oUser = $oUser;
        $this->userTable = $userTable;
    }

    public function fetchAll($paginated = false,$aWhere = [],$sSort = 'label ASC')
    {
        if ($paginated) {
            return $this->fetchPaginatedResults($aWhere,$sSort);
        }

        // Create a new Select object for the table:
        $select = new Select($this->tableGateway->getTable());
        // Filter
        $select->where($aWhere);
        // Sort
        $select->order($sSort);

        return $this->tableGateway->selectWith($select);
    }

    private function fetchPaginatedResults($aWhere = [],$sSort = '')
    {
        // Create a new Select object for the table:
        $select = new Select($this->tableGateway->getTable());
        // Filter
        $select->where($aWhere);
        // Sort
        $select->order($sSort);

        // Create a new result set based on the Album entity:
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Calendar($this->tableGateway->getAdapter(),$this->aPlugins,$this->userTable));

        // Create a new pagination adapter object:
        $paginatorAdapter = new DbSelect(
        // our configured select object:
            $select,
            // the adapter to run it against:
            $this->tableGateway->getAdapter(),
            // the result set to hydrate:
            $resultSetPrototype
        );

        $paginator = new Paginator($paginatorAdapter);
        return $paginator;
    }

    public function getCalendar($id,$sKey = 'Calendar_ID')
    {
        $id = (int) $id;
        $oRowset = $this->tableGateway->select([$sKey => $id]);
        $oRow = $oRowset->current();
        // Calendar Plugins
        if(array_key_exists('plugin_name',$this->aPlugins)) {

        }
        if (! $oRow) {
            throw new RuntimeException(sprintf(
                'Could not find row with identifier %d',
                $id
            ));
        }

        return $oRow;
    }

    public function saveCalendar(Calendar $oCalendar)
    {
        $aData = [
            'label' => $oCalendar->getLabel(),
            'description'  => $oCalendar->getDescription(),
        ];

        // field plugins
        if(array_key_exists('fieldname',$this->aPlugins)) {
            //$aData['fieldname'] = $oCalendar->fieldname;
        }

        $id = (int) $oCalendar->getID();

        if ($id === 0) {
            // Add internal fields
            $aData['created_by'] = $this->oUser->getID();
            $aData['created_date'] = date('Y-m-d H:i:s',time());

            // Insert new Calendar
            $this->tableGateway->insert($aData);

            // Save id for plugins and view
            $id = $this->tableGateway->lastInsertValue;

            // User XP Plugin
            if(array_key_exists('user_xp',$this->aPlugins)) {
                $this->oUser->addXP(150);
            }

            // Return Calendar id
            return $id;
        }

        try {
            $this->getCalendar($id);
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf(
                'Cannot update calendar with identifier %d; does not exist',
                $id
            ));
        }

        // Update Calendar
        // Add internal fields
        $aData['modified_by'] = $this->oUser->getID();
        $aData['modified_date'] = date('Y-m-d H:i:s',time());
        $this->tableGateway->update($aData, ['Calendar_ID' => $id]);

        // Position Plugin - Update
        if(array_key_exists('plugin_name',$this->aPlugins)) {

        }

        // Return Calendar ID
        return $id;
    }

    public function deleteCalendar($id)
    {
        $this->tableGateway->delete(['Calendar_ID' => (int) $id]);
    }
}
