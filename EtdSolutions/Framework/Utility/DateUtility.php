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

use EtdSolutions\Framework\Application\Web;
use Joomla\Date\Date;
use Joomla\Language\Text;

defined('_JEXEC') or die;

class DateUtility {

    /**
     * Méthode pour formater une date en gérant le fuseau horaire et
     * la langue choisis dans la conf de l'utilisateur.
     *
     * @param string $date      La date à formater
     * @param string $format    Le format à utiliser
     * @return string           La date formatée
     */
    public static function format($date, $format) {

        // On initialise les variables.
        $app = Web::getInstance();
        $lang = $app->getLanguage();
        $tz = $app->get('timezone');

        // Si ce n'est un objet Date, on le crée.
        if (!($date instanceof Date)) {
            $date = new Date($date);
        }

        // Si un fuseau horaire utilisateur est spécifié dans l'appli.
        if (!empty($tz)) {
            $date->setTimezone(new \DateTimeZone($tz));
        }

        // Si le format est une chaine traduisible (format différent suivant la langue de l'utilisateur)
        if ($lang->hasKey($format)) {
            $format = Text::_($format);
        }

        return $date->format($format, true);

    }

    /**
     * Méthode pour déplacer une date suivant un interval (e.g. -P7D, -P1DT1H ou +PT5H)
     *
     * @param string $date
     * @param string $interval
     *
     * @return Date
     */
    public static function moveDate($date, $interval) {

        // Si ce n'est un objet Date, on le crée.
        if (!($date instanceof Date)) {
            $date = new Date($date);
        }

        // On transforme l'intervale en format ISO.
        $iso = strtoupper(substr($interval, 1));

        // On crée l'intervale de date
        $dateint = new \DateInterval($iso);

        // On ajoute si on a un "+"
        if (strpos($interval, '+') === 0) {
            $date->add($dateint);
        } elseif (strpos($interval, '-') === 0) { // On retire si on a un "-"
            $date->sub($dateint);
        }

        return $date;

    }

}