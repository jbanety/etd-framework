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
use Joomla\Registry\Registry;
use SimpleXMLElement;

class Field_Rights extends Field {

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Rights';

    /**
     * Method to get the radio button field input markup.
     *
     * @return  string  The field input markup.
     */
    protected function getInput() {

        // Initialisation.
        $actions = $this->getActions();
        $html = array();
        $value = false;

        if (!empty($this->value)) {
            $value = new Registry($this->value);
        }

        $class = $this->element['class'] ? ' ' . (string)$this->element['class'] : '';

        $html[] = '<table class="rights table table-condensed' . $class . '">';

        $html[] = '<thead>';
        $html[] = '<tr>';
        $html[] = '<th>' . Text::_('APP_GLOBAL_RIGHTS_HEADING_SECTION') . '</th>';
        $html[] = '<th>' . Text::_('APP_GLOBAL_RIGHTS_HEADING_ACTION') . '</th>';
        $html[] = '<th>' . Text::_('APP_GLOBAL_RIGHTS_HEADING_RIGHT') . '</th>';
        $html[] = '</tr>';
        $html[] = '</thead>';

        $html[] = '<tbody>';

        // On parcourt les sections.
        foreach ($actions as $section) {

            $rowspan = count($section->actions);

            foreach ($section->actions as $i => $action) {

                $labelClass1 = '';
                $labelClass0 = '';
                $checked1 = '';
                $checked0 = '';

                if ($value && $value->exists($section->name . "." . $action->name)) {
                    $v = $value->get($section->name . "." . $action->name, false);
                    if ($v) {
                        $labelClass1 = ' active';
                        $checked1 = ' checked';
                    } else {
                        $labelClass0 = ' active';
                        $checked0 = ' checked';
                    }
                }

                $html[] = '<tr>';

                if ($i == 0) {
                    $html[] = '<td class="section" rowspan="' . $rowspan . '"><span class="hasTooltip" title="' . Text::_($section->description) . '">' . Text::_($section->title) . '</td>';
                }

                $html[] = '<td class="action"><span class="hasTooltip" title="' . Text::_($action->description) . '">' . Text::_($action->title) . '</td>';

                $html[] = '<td class="right">';
                $html[] = '<div class="btn-group" data-toggle="buttons">';
                $html[] = '<label class="btn btn-default btn-sm' . $labelClass1 . '"><input name="' . $this->name . '[' . $section->name . '][' . $action->name . ']" value="1" type="checkbox"' . $checked1 . '> ' . Text::_('APP_GLOBAL_YES') . '</label>';
                $html[] = '<label class="btn btn-default btn-sm' . $labelClass0 . '"><input name="' . $this->name . '[' . $section->name . '][' . $action->name . ']" value="0" type="checkbox"' . $checked0 . '> ' . Text::_('APP_GLOBAL_NO') . '</label>';
                $html[] = '</div>';
                $html[] = '</td>';

                $html[] = '</tr>';

            }

        }


        $html[] = '';
        $html[] = '';
        $html[] = '';
        $html[] = '</tbody>';

        $html[] = '</table>';

        return implode("\n", $html);

    }

    protected function getActions() {

        // On charge les droits depuis le XML.
        $data = simplexml_load_file(JPATH_ROOT . "/rights.xml");

        // On contrôle que les données sont bien chargées.
        if ((!($data instanceof SimpleXMLElement)) && (!is_string($data))) {
            throw new \RuntimeException(Text::_('APP_ERROR_RIGHTS_NOT_LOADED'));
        }

        // On initialise les actions.
        $result = array();

        // On récupère les sections.
        $sections = $data->xpath("/rights/section");

        if (!empty($sections)) {

            foreach ($sections as $section) {

                $tmp = array(
                    'name' => (string) $section['name'],
                    'title' => (string) $section['title'],
                    'description' => (string) $section['description'],
                    'actions' => array()
                );

                $actions = $section->xpath("action[@name][@title][@description]");

                if (!empty($actions)) {

                    foreach ($actions as $action) {
                        $tmp['actions'][] = (object) array(
                            'name' => (string) $action['name'],
                            'title' => (string) $action['title'],
                            'description' => (string) $action['description']
                        );
                    }

                    $result[] = (object) $tmp;
                }

            }

        }

        return $result;

    }
}
