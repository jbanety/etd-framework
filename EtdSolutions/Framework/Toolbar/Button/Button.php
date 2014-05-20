<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Toolbar\Button;

use Joomla\Language\Text;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

class Button {

    /**
     * @var string $text Le texte du bouton
     */
    protected $text = '';

    protected $attribs = array();

    protected $icon = '';

    public function __construct($text, $attribs = array(), $icon = '') {

        if (array_key_exists('title', $attribs)) {
            $attribs['title'] = Text::_($attribs['title']);
        }

        $this->attribs = $attribs;
        $this->icon    = $icon;
        $this->text    = $text;
    }

    public function setAttribute($name, $value) {
        $this->attribs[$name] = $value;
    }

    public function getAttribute($name) {
        return isset($this->attribs[$name]) ? $this->attribs[$name] : null;
    }

    public function setText($text) {
        $this->text = $text;
    }

    public function getText() {
        return $this->text;
    }

    public function render() {

        $html = '<a ' . ArrayHelper::toString($this->attribs, '=', ' ') . '>';

        if (!empty($this->icon)) {
            $html .= '<span class="fa fa-' . $this->icon . '"></span>&nbsp;';
        }

        if (!empty($this->text)) {
            $html .= Text::_($this->text);
        }

        $html .= '</a>';

        return $html;
    }

}