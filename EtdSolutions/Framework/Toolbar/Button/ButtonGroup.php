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

use EtdSolutions\Framework\Toolbar\Toolbar;
use Joomla\Language\Text;

defined('_JEXEC') or die;


class ButtonGroup extends ButtonDropdownSingle{

    /**
     * @var array Les boutons du btn-group
     */
    protected $components = array();

    function __construct(array $components)
    {

        $this->components = $components;

    }


    public function render(){

        $html='';
        $html.='<div id="" class="btn-group"> ';

        foreach($this->components as $component){


            //$html.='<button id="" class="' . $component->class . '"> ' . Text::_($component->label) . '</button>';
            $html.=$component->render();
        }

        $html.='</div>';



        return $html;
    }





}