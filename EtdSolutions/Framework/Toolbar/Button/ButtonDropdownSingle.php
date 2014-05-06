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

class ButtonDropdownSingle extends Button {


    /**
     * @var array $links tableau avec les sous boutons et leur lien
     */
    protected $links = array();

    protected $button ='';

    public function render(){


        $html='<div class="btn-group btn-toolbar"><a type="button" ';

        if($this->button->class !=''){
            $html .= 'class="btn btn-' . $this->button->getClass() . ' dropdown-toggle" ';
        }

        $html .= 'dropdown-toggle" data-toggle="dropdown" >';

        if($this->button->label != ''){
            $html .= Text::_($this->button->getLabel());
        }
        $html .='<span class="caret"></span>
                 </a><ul class="dropdown-menu pull-right" role="menu">';

        foreach($this->links as $link){
            $link->setClass('');
            $html.= '<li>'.$link->render().'</li>';
        }
        $html.='</ul></div>';


        return $html;
    }


    function __construct($button, $links)
    {

        $this->button = $button;
        $this->links  = $links;
    }


}