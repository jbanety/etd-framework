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
use Joomla\Form\Field_Text;
use Joomla\Language\Text;

class Field_MaskedText extends Field_Text {

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'MaskedText';

    protected function getInput() {

        $mask = $this->element['mask'];

        $lang = Web::getInstance()->getLanguage();
        if ($lang->hasKey($mask)) {
            $mask = Text::_($mask);
        }

        if (!empty($mask)) {

            $app = Web::getInstance();
            $doc = $app->getDocument();
            $options = array();

            $doc->addScript($app->get('uri.base.full') . "theme/js/vendor/jquery.maskedinput.min.js");

            $js = "$('#" . $this->id . "').mask('" . addslashes($mask) . "'";

            if (!empty($this->element['placeholder'])) {
                $options['placeholder'] = (string)$this->element['placeholder'];
            }

            if (!empty($this->element['autoclear'])) {
                $options['autoclear'] = ($this->element['autoclear'] == "true");
            } else {
                $options['autoclear'] = false;
            }

            if(!empty($options)) {
                $js .= "," . json_encode($options);
            }

            $js .= ");";

            $doc->addDomReadyJS($js);
        }

        return parent::getInput();
    }

}
