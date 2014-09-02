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

use EtdSolutions\Framework\Application\Web;
use Joomla\Form\Field_List;
use Joomla\Form\Html\Select as HtmlSelect;

abstract class Field_InlineEditList extends Field_List {

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = "InlineEditList";

    protected $viewURI = "";

    protected $icon = "";

    protected function getInput() {

        // On récupère les attributs.
        $ajax = isset($this->element['ajax']) ? (bool)$this->element['ajax'] : false;

        // Est-on en mode "ajax" ?
        if ($ajax) {

            $app = Web::getInstance();
            $doc = $app->getDocument();
            $html = array();

            $doc->addDomReadyJS("Form.prepareEditRow('#" . $this->id . "_row');");

            $this->element['class'] = isset($this->element['class']) ? $this->element['class'] . ' inline-edit-input' : 'inline-edit-input';

            $html[] = '<div id="' . $this->id . '_row" class="row inline-edit-row">';
            $html[] = '<div class="col-xs-9">';
            $html[] = '<a class="inline-edit-value" href="' . $app->get('uri.base.full') . $this->viewURI . '/' . $this->value . '"><i class="fa fa-' . $this->icon . '"></i> <span class="caption">' . $this->getCaption($this->value) . '</span></a>';
            $html[] = parent::getInput();
            $html[] = '</div>';
            $html[] = '<div class="col-xs-3">';
            $html[] = '<a href="#" class="inline-edit-btn"><span class="fa fa-pencil"></span></a>';
            $html[] = '<a href="#" class="inline-save-btn"><span class="fa fa-check-circle"></span></a>';
            $html[] = '<a href="#" class="inline-cancel-btn"><span class="fa fa-times-circle"></span></a>';
            $html[] = '</div>';
            $html[] = '</div>';

            return implode("\n", $html);
        }

        return parent::getInput();
    }

    protected function getOptions() {

        $options = array();

        $items = $this->loadItems();

        if (!empty($items)) {
            foreach ($items as $item) {
                $options[] = HtmlSelect::option($item->value, $item->text);
            }
        }

        return array_merge(parent::getOptions(), $options);
    }

    abstract protected function getCaption($value);

    abstract protected function loadItems();

}