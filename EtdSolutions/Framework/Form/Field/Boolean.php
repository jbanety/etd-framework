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
        $readonly = isset($this->element['readonly']) ? (bool)$this->element['readonly'] : false;

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

        $html = '<div class="form-control-static">';

        if ($readonly) {

            $html .= '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '">';

            if (empty($checked0)) {
                $html .= Text::_('APP_GLOBAL_YES');
            } else {
                $html .= Text::_('APP_GLOBAL_NO');
            }

        } else {

            $html .= '<div class="btn-group' . $class . '" data-toggle="buttons">
  <label class="btn' . $buttonClass . $active1 . '">
    <input type="radio" name="' . $this->name . '" id="' . $this->id . '1"' . $checked1 . ' value="1"> ' . Text::_("APP_GLOBAL_YES") . '
  </label>
  <label class="btn' . $buttonClass . $active0 . '">
    <input type="radio" name="' . $this->name . '" id="' . $this->id . '0"' . $checked0 . ' value="0"> ' . Text::_("APP_GLOBAL_NO") . '
  </label>
</div>';

        }

        $html .= '</div>';

        return $html;

    }
}
