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

class ButtonDropdownSingle {


    /**
     * @var array $links tableau avec les sous boutons et leur lien
     */
    protected $links = array();

    /**
     * @var Button
     */
    protected $button = null;

    public function render(){


        $html = '<div class="btn-group">';

        $this->button->setAttribute('class', $this->button->getAttribute('class') . " dropdown-toggle");
        $this->button->setText($this->button->getText() . '<span class="caret"></span>');
        $this->button->setAttribute('data-toggle', 'dropdown');

        $html .= $this->button->render();

        $html .= '<ul class="dropdown-menu pull-right" role="menu">';

        foreach($this->links as $link) {
            $link->setAttribute('class', '');
            $html .= '<li>' . $link->render() . '</li>';
        }

        $html .= '</ul></div>';

        return $html;
    }


    function __construct($links, $button = null)
    {

        // Si le bouton n'est pas spécifié, on prend le premier du tableau.
        if (is_null($button)) {
            $button = array_shift($links);
        }

        $this->button = $button;
        $this->links  = $links;
    }


}