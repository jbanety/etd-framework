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
use Joomla\Crypt\Crypt;
use Joomla\Crypt\Password\Simple;
use Joomla\Data\DataObject;
use Joomla\Date\Date;
use Joomla\Language\Text;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

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

        // On génère le mot de passe crypté si besoin.
        if (array_key_exists('password', $properties) && !empty($properties['password']) && substr($properties['password'], 0, 4) != '$2a$' && substr($properties['password'], 0, 4) != '$2y$') {
            $simpleAuth             = new Simple();
            $properties['password'] = $simpleAuth->create($properties['password']);
        }

        // On convertit le tableau de droit en JSON.
        if (array_key_exists('rights', $properties) && is_array($properties['rights'])) {
            $registry             = new Registry($properties['rights']);
            $properties['rights'] = $registry->toString();
        }

        // On convertit le tableau de paramètres en JSON.
        if (array_key_exists('params', $properties) && is_array($properties['params'])) {
            $registry             = new Registry($properties['params']);
            $properties['params'] = $registry->toString();
        }

        return parent::bind($properties, $updateNulls);
    }

    public function check() {

        $pk = $this->getProperty($this->getPk());

        $db = Web::getInstance()
                 ->getDb();

        // Date actuelle.
        $date = new Date();
        $now  = $date->format($db->getDateFormat());

        // On regarde si c'est un nouvel utilisateur ou non.
        if (empty($pk)) {

            // On contrôle le mot de passe et on crée le mot de passe crypté si besoin.
            if (empty($this->password)) {
                $simpleAuth = new Simple();
                $this->setProperty('password', $simpleAuth->create($this->genRandomPassword()));
            }

            // On définit la date d'inscription.
            $this->setProperty('registerDate', $now);

        }

        // On contrôle que le nom d'utilisateur n'est pas plus long que 150 caractères.
        $username = $this->getProperty('username');

        if (strlen($username) > 150) {
            $username = substr($username, 0, 150);
            $this->setProperty('username', $username);
        }

        return true;
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

    /**
     * Génère un mot de passe aléatoire.
     *
     * @param   integer $length Longueur du mot de passe à générer.
     *
     * @return  string  Le mot de passe aléatoire.
     */
    protected function genRandomPassword($length = 8) {

        $salt     = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $base     = strlen($salt);
        $makepass = '';

        /*
         * Start with a cryptographic strength random string, then convert it to
         * a string with the numeric base of the salt.
         * Shift the base conversion on each character so the character
         * distribution is even, and randomize the start shift so it's not
         * predictable.
         */
        $random = Crypt::genRandomBytes($length + 1);
        $shift  = ord($random[0]);

        for ($i = 1; $i <= $length; ++$i) {
            $makepass .= $salt[($shift + ord($random[$i])) % $base];
            $shift += ord($random[$i]);
        }

        return $makepass;
    }

}