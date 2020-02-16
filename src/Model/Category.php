<?php
/**
 * Created by PhpStorm.
 * User: Praesidiarius
 * Date: 19.03.2019
 * Time: 23:22
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

class Category
{
    public $id;
    public $label;
    public $name;

    private $inputFilter;
    private $oDbAdapter;
    private $aPlugins;

    public function __construct($oDbAdapter,$aPlugins = []) {
        $this->oDbAdapter = $oDbAdapter;
        $this->aPlugins = $aPlugins;
    }

    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['Category_ID']) ? $data['Category_ID'] : 0;
        $this->label = !empty($data['label']) ? $data['label'] : '';
        $this->name = !empty($data['name']) ? $data['name'] : '';
    }

    public function getArrayCopy()
    {
        $aCopy = [
            'id'     => $this->id,
            'label' => $this->label,
        ];

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
            'name' => 'Category_ID',
            'required' => false,
            'filters' => [
                ['name' => ToInt::class],
            ],
        ]);

        $inputFilter->add([
            'name' => 'label',
            'required' => true,
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

    public function getName() {
        return $this->name;
    }

    public function getFeaturedImage($bFullPath = false) {
        $sPath = '/data/event-category/'.$this->getID().'/avatar.png';
        if($bFullPath) {
            $sPath = $_SERVER['DOCUMENT_ROOT'].$sPath;
        }
        return $sPath;
    }
}