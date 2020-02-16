<?php
/**
 * CalendarForm.php - Calendar Form
 *
 * Definition for Calendar Form
 *
 * @category Form
 * @package Calendar
 * @author Nathanael Kammermann
 * @copyright (C) 2019 Nathanael Kammermann <nathanael.kammermann@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 * @since File available since Version 1.0
 */

namespace Calendar\Form;

use Zend\Form\Form;
use Zend\Form\Element;

class CalendarForm extends Form
{
    public function __construct($name = null,$aPlugins = [])
    {
        // We will ignore the name provided to the constructor
        parent::__construct('calendar');

        $this->add([
            'name' => 'Calendar_ID',
            'type' => 'hidden',
        ]);
        $this->add([
            'name' => 'label',
            'type' => 'text',
            'options' => [
                'label' => 'Label',
            ],
        ]);
        // field plugins
        if(array_key_exists('fieldname',$aPlugins)) {
        }
        $this->add([
            'name' => 'description',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Description',
            ],
        ]);
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Go',
                'id'    => 'submitbutton',
            ],
        ]);
    }
}
