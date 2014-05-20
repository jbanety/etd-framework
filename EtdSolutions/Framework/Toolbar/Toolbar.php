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
use Joomla\Form\Form;

defined('_JEXEC') or die;

class Toolbar {

    /**
     * @var Toolbar L'instance générale de la barre d'outils.
     */
    private static $instance;

    /**
     * @var array Tableau des boutons d'actions.
     */
    protected $buttons = array();

    /**
     * @var Form Le formulaire utilisé pour filtrer les enregistrements.
     */
    protected $filterForm = null;

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
     * Méthode pour créer un bouton
     *
     * @param string $text    Texte du bouton
     * @param array  $attribs Tableau des attributs supplémentaires
     * @param string $icon    Classe CSS Font Awesome (sans le fa-)
     *
     * @return Button
     */
    public static function createButton($text, $attribs = array(), $icon = '') {

        return new Button($text, $attribs, $icon);

    }

    /**
     * Méthode pour créer un groupe de bouton.
     *
     * @param array $components Tableau des boutons à ajouter au groupe.
     *
     * @return ButtonGroup
     */
    public static function createButtonGroup($components) {

        $button = new ButtonGroup($components);

        return $button;
    }

    /**
     * Méthode pour créer un Split Dropdown
     *
     * @param array  $links
     * @param Button $button
     *
     * @return ButtonDropdownSplit
     */
    public static function createButtonDropdownSplit($links, $button = null) {

        // Si le bouton n'est pas spécifié, on prend le premier du tableau.
        if (is_null($button)) {
            $button = array_shift($links);
        }

        return new ButtonDropdownSplit($links, $button);
    }

    /**
     * Méthode pour créer un dropdown
     *
     * @param array  $links
     * @param Button $button
     *
     * @return ButtonDropdownSingle
     */
    public static function createButtonDropdownSingle($links, $button = null) {

        return new ButtonDropdownSingle($links, $button);
    }

    /**
     * Méthode pour ajouter un bouton à la toolbar.
     *
     * @param mixed $button
     *
     * @return $this
     */
    public function addButton($button) {

        $this->buttons[] = $button;

        return $this;

    }

    /**
     * Méthode pour ajouter un filtre à la toolbar.
     *
     * @param Form $form
     *
     * @return $this
     */
    public function setFilterForm($form) {

        $this->filterForm = $form;

        return $this;

    }

    /**
     * Retourne le rendu de la barre d'outils.
     *
     * @return string code HTML du rendu
     * @throws \RuntimeException
     */
    public function render() {

        // Get the layout path.
        $path = JPATH_THEME . "/html/toolbar.php";

        // Check if the layout path was found.
        if (!$path) {
            throw new \RuntimeException('Toolbar Layout Path Not Found');
        }

        // Start an output buffer.
        ob_start();

        // Load the layout.
        include $path;

        // Get the layout contents.
        $output = ob_get_clean();

        return $output;

    }

}
