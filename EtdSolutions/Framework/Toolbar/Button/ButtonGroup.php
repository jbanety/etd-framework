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


class ButtonGroup extends Button{

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
        $html.='<div class="btn-group btn-toolbar"> ';

        foreach($this->components as $component){

            $html.=$component->render();

        }

        $html.='</div>';



        return $html;
    }





}