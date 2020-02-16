<?php
/**
 * Calendar.php - Calendar Model
 *
 * Single Entity Model for Calendar
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

use DomainException;
use Zend\Filter\StringTrim;
use Zend\Filter\StripTags;
use Zend\Filter\ToInt;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Validator\StringLength;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Select;

class Calendar
{
    protected $id;
    protected $label;
    protected $description;
    protected $color_text;
    protected $color_background;

    private $inputFilter;
    private $oDbAdapter;
    private $aPlugins;
    private $userTable;

    public function __construct($oDbAdapter,$aPlugins = [],$userTable) {
        $this->oDbAdapter = $oDbAdapter;
        $this->aPlugins = $aPlugins;
        $this->userTable = $userTable;
    }

    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['Calendar_ID']) ? $data['Calendar_ID'] : 0;
        $this->label = !empty($data['label']) ? $data['label'] : '';
        $this->description  = !empty($data['description']) ? $data['description'] : '';
        $this->color_background  = !empty($data['color_background']) ? $data['color_background'] : '';
        $this->color_text  = !empty($data['color_text']) ? $data['color_text'] : '';

        // Plugins
        if(array_key_exists('fieldname',$this->aPlugins)) {
            //$this->fieldname  = !empty($data['fieldname']) ? $data['fieldname'] : '';
        }
    }

    public function getArrayCopy()
    {
        $aCopy = [
            'id'     => $this->id,
            'label' => $this->label,
            'description'  => $this->description,
        ];

        // Plugins
        if(array_key_exists('fieldname',$this->aPlugins)) {
            //$aCopy['fieldname'] = $this->fieldname;
        }

        return $aCopy;
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new DomainException(sprintf(
            '%s does not allow injection of an alternate input filter',
            __CLASS__
        ));
    }

    public function getInputFilter()
    {
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'Calendar_ID',
            'required' => true,
            'filters' => [
                ['name' => ToInt::class],
            ],
        ]);

        $inputFilter->add([
            'name' => 'label',
            'required' => false,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'description',
            'required' => false,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 255,
                    ],
                ],
            ],
        ]);

        if(array_key_exists('fieldname',$this->aPlugins)) {
            $inputFilter->add([
                'name' => 'fieldname',
                'required' => false,
                'filters' => [
                    ['name' => StripTags::class],
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ]);
        }

        $this->inputFilter = $inputFilter;
        return $this->inputFilter;
    }

    public function getLabel() {
        $sLabel = $this->label;
        return $sLabel;
    }

    public function getID() {
        return $this->id;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getEvents($aFilters = []) {
        $oEventTbl = new TableGateway('event',$this->oDbAdapter);
        $aEvents = [];
        $oEventsSel = new Select($oEventTbl->getTable());
        $aWhere = ['calendar_idfs'=>$this->getID()];
        if(array_key_exists('web_show',$aFilters)) {
            $aWhere['web_show'] = 1;
        }
        if(array_key_exists('web_spotlight',$aFilters)) {
            $aWhere['web_spotlight'] = 1;
        }
        $oEventsSel->where($aWhere);
        $oEventsSel->order('date_start ASC');

        $oEventsDB = $oEventTbl->selectWith($oEventsSel);
        foreach($oEventsDB as $oEv) {
            $aEvents[] = $oEv;
        }
        return $aEvents;
    }

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
