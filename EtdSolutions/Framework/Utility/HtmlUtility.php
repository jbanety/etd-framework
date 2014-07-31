<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Utility;

use Joomla\Filesystem\Path;

defined('_JEXEC') or die;

/**
 * Classe utilitaire pour faire des rendus HTML de petites parties répétitives.
 *
 * @package EtdSolutions\Framework\Utility
 */
class HtmlUtility {

    /**
     * Méthode pour trier une colonne dans un tableau.
     *
     * @param   string $title         Le titre de la colonne
     * @param   string $order         Le champ sur lequel le tri se fera
     * @param   string $direction     La direction actuelle
     * @param   mixed  $selected      Le tri sélectionné
     * @param   string $task          Un override optionnel de la tâche
     * @param   string $new_direction Une direction optionnelle pour la colonne
     * @param   string $tip           Un texte optionnel affiché comme infobulle au lieu de $title
     * @param   string $icon          L'icône à afficher
     * @param   string $formName      Le nom du formulaire à envoyer
     *
     * @return  string
     */
    public static function sort($title, $order, $direction = 'asc', $selected = 0, $task = null, $new_direction = 'asc', $tip = '', $icon = null, $formName = 'form-admin') {

        $direction  = strtolower($direction);
        $orderIcons = array(
            'fa-toggle-up',
            'fa-toggle-down'
        );
        $index      = (int)($direction == 'desc');

        if ($order != $selected) {
            $direction = $new_direction;
        } else {
            $direction = ($direction == 'desc') ? 'asc' : 'desc';
        }

        // On crée un objet pour le passer au layout.
        $data            = new \stdClass;
        $data->order     = $order;
        $data->direction = $direction;
        $data->selected  = $selected;
        $data->task      = $task;
        $data->tip       = $tip;
        $data->title     = $title;
        $data->orderIcon = $orderIcons[$index];
        $data->icon      = $icon;
        $data->formName  = $formName;

        return self::render('sort', $data);
    }

    /**
     * Méthode pour effectuer le rendu.
     *
     * @param string $layout Le nom du layout.
     * @param object $data   Les paramètres et données à passer au layout.
     *
     * @return string Le rendu.
     * @throws \InvalidArgumentException Si le layout est introuvable.
     */
    protected static function render($layout, $data) {

        // On récupère le chemin vers le layout.
        $path = Path::clean(JPATH_THEME . '/html/utility/' . $layout . '.php');

        // On contrôle que le chemin existe.
        if (!file_exists($path)) {
            throw new \InvalidArgumentException('HtmlUtility Layout Path Not Found : ' . $layout, 404);
        }

        // On crée un buffer de sortie.
        ob_start();

        // On charge le layout.
        include $path;

        // On récupère le contenu.
        $output = ob_get_clean();

        return $output;

    }

}