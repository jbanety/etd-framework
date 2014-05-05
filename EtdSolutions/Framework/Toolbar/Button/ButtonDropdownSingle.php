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

    /**
     * @var array $links tableau avec les sous boutons et leur lien
     */
    protected $links = array();

    public function render(){


        $html='<div class="btn-group btn-toolbar"><a type="button" ';

        if($this->class !=''){
            $html .= 'class="' . $this->class . ' dropdown-toggle" ';
        }

        $html .= 'dropdown-toggle" data-toggle="dropdown" >';

        if($this->label != ''){
            $html .= Text::_($this->label);
        }
        $html .='<span class="caret"></span>
                 </a><ul class="dropdown-menu pull-right" role="menu">';

        foreach($this->links as $link){
            $html.= '<li>'.$link->render().'</li>';
        }
        $html.='</ul></div>';


        return $html;
    }

    function __construct($label, $url, $links, $class = '', $onclick = '', $disabled = false)
    {

        $this->class    = $class;
        $this->disabled = $disabled;
        $this->label    = $label;
        $this->onclick  = $onclick;
        $this->url      = $url;
        $this->links    = $links;
    }

}