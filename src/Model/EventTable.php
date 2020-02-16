<?php
/**
 * EventTable.php - Event Table Model
 *
 * Table Entity Model for Event
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

class EventTable
{
    private $tableGateway;
    private $aPlugins;
    private $oUser;

    public function __construct(TableGatewayInterface $tableGateway,$aPlugins = [],$oUser)
    {
        $this->tableGateway = $tableGateway;
        $this->aPlugins = $aPlugins;
        $this->oUser = $oUser;
    }

    public function fetchAll($paginated = false,$aWhere = [])
    {
        if ($paginated) {
            return $this->fetchPaginatedResults($aWhere);
        }

        // Create a new Select object for the table:
        $select = new Select($this->tableGateway->getTable());
        // Filter
        $select->where($aWhere);
        // Sort
        $select->order('label ASC');

        return $this->tableGateway->selectWith($select);
    }

    private function fetchPaginatedResults($aWhere = [])
    {
        // Create a new Select object for the table:
        $select = new Select($this->tableGateway->getTable());
        // Filter
        $select->where($aWhere);
        // Sort
        $select->order('label ASC');

        // Create a new result set based on the Album entity:
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Category($this->tableGateway->getAdapter(),$this->aPlugins));

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

    public function getEvent($id)
    {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(['Event_ID' => $id]);
        $row = $rowset->current();
        if (! $row) {
            throw new RuntimeException(sprintf(
                'Could not find contact category with identifier %d',
                $id
            ));
        }

        return $row;
    }

    public function saveEvent(Event $oCat)
    {
        $aData = [
            'label' => $oCat->getLabel(),
        ];

        $id = (int) $oCat->getID();

        if ($id === 0) {
            // Add internal fields
            $aData['created_by'] = $this->oUser->getID();
            $aData['created_date'] = date('Y-m-d H:i:s',time());

            // Insert new Contact
            $this->tableGateway->insert($aData);

            // Save id for plugins and view
            $id = $this->tableGateway->lastInsertValue;

            // Return Contact id
            return $id;
        }

        try {
            $this->getCategory($id);
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf(
                'Cannot update event with identifier %d; does not exist',
                $id
            ));
        }

        // Add internal fields
        $aData['modified_by'] = $this->oUser->getID();
        $aData['modified_date'] = date('Y-m-d H:i:s',time());

        // Update Category
        $this->tableGateway->update($aData, ['Category_ID' => $id]);

        // Return Category ID
        return $id;
    }

    public function deleteEvent($id)
    {
        $this->tableGateway->delete(['Event_ID' => (int) $id]);
    }
}