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

defined('_JEXEC') or die;

class Button {

    /**
     * @var string $label Le texte du bouton
     */
    protected $label = '';

    /**
     * @var string $class Classe CSS
     */
    protected $class = '';

    /**
     * @var string $url url bouton
     */
    protected $url = '';

    /**
     * @var string $onclick action du bouton
     */
    protected $onclick = '';

    /**
     * @var bool $disabled Si True le bouton est désactivé. False par défaut.
     */
    protected $disabled = false;



    public function render(){
        $html='<a ';

        if($this->url != ''){
            $html .='href="'.$this->url.'"';
        }

        if($this->class != ''){
            $html .='class="'.$this->class.'"';
        }

        $html .= '> ' . Text::_($this->label) . ' </a>';

        return $html;
    }

    function __construct($label, $url, $class = '', $onclick = '', $disabled = false)
    {

        $this->class    = $class;
        $this->disabled = $disabled;
        $this->label    = $label;
        $this->onclick  = $onclick;
        $this->url      = $url;
    }

}