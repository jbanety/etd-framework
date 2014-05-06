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

    protected $logo = '';

    /**
     * @return string
     */
    public function getLabel()
    {

        return $this->label;
    }

    /**
     * @return string
     */
    public function getUrl()
    {

        return $this->url;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {

        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {

        return $this->class;
    }

    /**
     * @return string
     */
    public function getOnclick()
    {

        return $this->onclick;
    }

    /**
     * @return string
     */
    public function getIcon()
    {

        return $this->icon;
    }






    public function render(){
        $html='<a ';

        if($this->url != ''){
            $html .='href="'.$this->url.'"';
        }

        if($this->class != ''){
            $html .='class="btn btn-'.$this->class.'"';
        }

        if($this->onclick != ''){
            $html .='onclick="'.$this->onclick.'"';
        }


        $html .= '> ';
        if ($this->icon !=''){
            $html .='<span class="fa fa-'.$this->icon.'"></span>&nbsp;';
        }
        $html .= Text::_($this->label) . ' </a>';

        return $html;
    }

    function __construct($label, $url, $class = '',$icon = '' , $onclick = '', $disabled = false)
    {

        $this->class    = $class;
        $this->disabled = $disabled;
        $this->label    = $label;
        $this->onclick  = $onclick;
        $this->url      = $url;
        $this->icon     = $icon;
    }

}