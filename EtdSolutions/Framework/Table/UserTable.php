<?php
/**
 * @package     ProjectPipeline
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 Jean-Baptiste Alleaume. Tous droits réservés.
 * @license     http://alleau.me/LICENSE
 * @author      Jean-Baptiste Alleaume http://alleau.me
 */

namespace EtdSolutions\Framework\Table;

use EtdSolutions\Framework\Application\Web;
use Joomla\Data\DataObject;
use Joomla\Date\Date;
use Joomla\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class UserTable extends Table {

    public function __construct() {

        parent::__construct('#__users', 'id');
    }

    /**
     * Renvoi les colonnes de la table dans la base de données.
     * Doit être définit manuellement dans chaque instance.
     *
     * @return  array Un tableau des champs disponibles dans la table.
     */
    public function getFields() {

        return array(
            'id',
            'civility',
            'firstName',
            'lastName',
            'username',
            'email',
            'password',
            'block',
            'sendEmail',
            'registerDate',
            'lastvisitDate',
            'params',
            'lastResetTime',
            'resetCount',
            'rights',
            'company_id'
        );
    }

    public function bind($properties, $updateNulls = true) {

        // On convertit le tableau de droit en JSON.
        if (array_key_exists('rights', $properties) && is_array($properties['rights'])) {
            $registry = new Registry($properties['rights']);
            $properties['rights'] = $registry->toString();
        }

        return parent::bind($properties, $updateNulls);
    }

    public function setLastVisit($date = null, $pk = null) {

        // Pas de clé primaire, on prend celle de l'instance.
        if (is_null($pk)) {
            $pk = $this->getProperty($this->pk);
        }

        // Si la clé primaire est vide, on ne change rien.
        if (empty($pk)) {
            return false;
        }

        // On récupère la base de données.
        $db = Web::getInstance()
                 ->getDb();

        // On formate la date suivant le type.
        if (is_numeric($date)) { // Timestamp UNIX
            $date = new Date($date);
        } elseif (is_string($date)) { // Une chaine formatée.
            $date = new Date($date);
        } elseif (is_null($date)) { // Pas de date, on prend celle de maintenant
            $date = new Date();
        } elseif (!($date instanceof Date)) { // Si en dernier lieu, on a pas passé un objet Date, le paramètre est invalide.
            throw new \InvalidArgumentException('Bad date parameter.');
        }

        // On formate la date.
        $formated_date = $date->format($db->getDateFormat());

        // On met à jour la ligne.
        $db->setQuery($db->getQuery(true)
                         ->update($this->table)
                         ->set($db->quoteName('lastvisitDate'), $formated_date)
                         ->where($db->quoteName($this->pk) . ' = ' . $db->quote($pk)));

        $db->execute();

        return true;

    }

}