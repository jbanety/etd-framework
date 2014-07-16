<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Form;

use Joomla\Form\Field;
use Joomla\Language\Text;

class Field_Boolean extends Field {

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Boolean';

    /**
     * Method to get the radio button field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0
     */
    protected function getInput() {

        $class       = $this->element['class'] ? ' ' . (string)$this->element['class'] : '';
        $buttonClass = $this->element['buttonClass'] ? ' ' . (string)$this->element['buttonClass'] : '';

        $checked1 = '';
        $active1  = '';
        $checked0 = '';
        $active0  = '';

        if (isset($this->value)) {

            $value    = (bool)$this->value;
            $checked1 = $value ? ' checked="checked"' : '';
            $active1  = $value ? ' active' : '';
            $checked0 = !$value ? ' checked="checked"' : '';
            $active0  = !$value ? ' active' : '';

        }

        return '<div class="form-control-static"><div class="btn-group' . $class . '" data-toggle="buttons">
  <label class="btn' . $buttonClass . $active1 . '">
    <input type="radio" name="' . $this->name . '" id="' . $this->id . '1"' . $checked1 . '> ' . Text::_("APP_GLOBAL_YES") . '
  </label>
  <label class="btn' . $buttonClass . $active0 . '">
    <input type="radio" name="' . $this->name . '" id="' . $this->id . '0"' . $checked0 . '> ' . Text::_("APP_GLOBAL_NO") . '
  </label>
</div></div>';

    }
}
