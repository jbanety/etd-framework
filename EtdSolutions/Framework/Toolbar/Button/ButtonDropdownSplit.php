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
use EtdSolutions\Framework\Application\Web;

$app = Web::getInstance();

defined('_JEXEC') or die;

class ButtonDropdownSplit extends Button {

    /**
     * @var array $links tableau avec les sous boutons et leur lien
     */
    protected $links = array();

    protected $button = '';

    public function render(){



        $html='<div class="btn-group btn-toolbar">';
        $html.='<a type="button"';

        if($this->button->url !=''){
            $html .='href="'.$this->button->getUrl().'"';
        }

        if($this->button->class !=''){
            $html .='class="btn btn-' . $this->button->getClass().'"';
        }
        else{
            $html .='class="btn btn-success"';
        }

        if($this->button->onclick !=''){
            $html .='onclick="' . $this->button->getOnclick() .'"';
        }
        $html .= '>';

        if($this->button->icon !=''){
            $html .='<span class="fa fa-'.$this->button->getIcon().'"></span>&nbsp';
        }

        if($this->button->label !=''){
            $html .= Text::_($this->button->getLabel());
        }

        $html .='</a>
                <a type="button" ';

        if($this->button->class !=''){
            $html .='class="btn btn-' . $this->button->getClass() . ' dropdown-toggle"';}

        else {
            $html .='class="btn btn-success dropdown-toggle"';
        }

        $html .=  ' data-toggle="dropdown">';
        $html .= '<span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
                </a><ul class="dropdown-menu" role="menu">';

        foreach($this->links as $link){
            $link->setClass('');
            $html.= '<li>'.$link->render().'</li>';
        }
        $html.='</ul></div>';


        return $html;
    }

    function __construct($links, $button)
    {

        $this->button = $button;
        $this->links  = $links;
    }

}