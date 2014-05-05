<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Toolbar;

use EtdSolutions\Framework\Toolbar\Button\Button;
use EtdSolutions\Framework\Toolbar\Button\ButtonDropdownSplit;
use EtdSolutions\Framework\Toolbar\Button\ButtonGroup;
use EtdSolutions\Framework\Toolbar\Button\ButtonDropdownSingle;



defined('_JEXEC') or die;


class Toolbar {

    private static $instance;
    protected $buttons = array();




    /**
     * Retourne une référence à l'objet global Toolbar, en le créant seulement si besoin.
     *
     * @return  Toolbar
     */
    public static function getInstance() {



        if (empty(self::$instance)) {
            self::$instance = new Toolbar;
        }

        return self::$instance;
    }

    /**
     * @param $label    Texte du bouton
     * @param $url      Lien du bouton
     * @param string $class     Class bootstrap du bouton
     * @param string $onclick   Action javascript
     * @param bool $disabled    Cacher un bouton (pas implémenté)
     * @return Button   objet de type bouton
     */

    public static function createButton($label, $url, $class = 'btn btn-default', $onclick = '', $disabled = false){

        $button = new Button($label, $url, $class, $onclick, $disabled);

        return $button;

    }

    /**
     * @param $components   Tableau des paramètres du bouton (voir paramètres de createButton)
     * @return ButtonGroup
     */
    public static function createButtonGroup($components){

        $button = new ButtonGroup($components);

        return $button;
    }

    /**
     * @param $label    Texte du bouton
     * @param $url      Lien du bouton
     * @param array $links      Tableau des paramètres des sous boutons
     * @param string $class     Class bootstrap du bouton
     * @param string $onclick   Action javascript
     * @param bool $disabled    Cacher un bouton (pas implémenté)
     * @return ButtonDropdownSplit      objet de type bouton
     */
    public static function createButtonDropdownSplit($label, $url, $links=array(), $class = 'btn btn-default', $onclick = '', $disabled = false){

        $button = new ButtonDropdownSplit($label, $url, $links, $class, $onclick, $disabled);

        return $button;
    }

    /**
     * @param $label    Texte du bouton
     * @param $url      Lien du bouton
     * @param array $links      Tableau des paramètres des sous boutons
     * @param string $class     Class bootstrap du bouton
     * @param string $onclick   Action javascript
     * @param bool $disabled    Cacher un bouton (pas implémenté)
     * @return ButtonDropdownSingle      objet de type bouton
     */
    public static function createButtonDropdownSingle($label, $url, $links=array(), $class = 'btn btn-default', $onclick = '', $disabled = false){

        $button = new ButtonDropdownSingle($label, $url, $links, $class, $onclick, $disabled);

        return $button;
    }


    /**
     * @param Button $button
     * @return $this
     */
    public function addButton($button){

        $this->buttons[] = $button;

        return $this;

    }

    /**
     * @return string code HTML du rendu
     */
    public function render() {
        $html = '';

        foreach($this->buttons as $button)
        {

            $html .= $button->render();

        }


        return $html;
    }

}
