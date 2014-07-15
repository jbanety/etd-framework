<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\User;

use EtdSolutions\Framework\Application\Web;
use EtdSolutions\Framework\Model\Model;
use EtdSolutions\Framework\Table\Table;
use Joomla\Data\DataObject;
use Joomla\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class User extends DataObject {

    /**
     * @var    array  Instances User.
     */
    protected static $instances = array();

    /**
     * Instancie l'objet utilisateur.
     *
     * @param   integer $id La clé primaire identifiant l'utilisateur à charger.
     */
    public function __construct($id = 0) {

        // On charge l'utilisateur s'il existe.
        if (!empty($id)) {
            $properties = $this->load($id);
        } else {
            // Initialise
            $properties = array(
                'id'        => 0,
                'sendEmail' => 0,
                'guest'     => 1,
                'rights'    => new Registry,
                'params'    => new Registry
            );
        }

        parent::__construct($properties);
    }

    /**
     * Retourne l'objet global User, en le créant seulement il n'existe pas déjà.
     *
     * @param   integer $id L'utilisateur à charger
     *
     * @return  User  L'objet User.
     */
    public static function getInstance($id = null) {

        $app      = Web::getInstance();
        $instance = $app->getSession()
                        ->get('user');

        if (is_null($id) || (!is_null($instance) && $instance->id == $id)) {

            if (!($instance instanceof User)) {
                return new User;
            }

            return $instance;
        } elseif (is_null($instance) || $instance->id != $id) {

            // On regarde si cet utilisateur est déjà en cache.
            if (empty(self::$instances[$id])) {
                self::$instances[$id] = new User($id);
            }

        }

        return self::$instances[$id];
    }

    public function isGuest() {

        $guest = $this->getProperty('guest');

        return ($guest == 1 || $guest === null);
    }

    /**
     * Méthode pour contrôler si l'utilisateur a le droit d'effectuer une action.
     *
     * @param   string $action  Le nom de l'action a contrôler.
     * @param   string $section La section sur laquelle on veut appliquer l'action.
     *
     * @return  boolean  True si autorisé, false sinon.
     */
    public function authorise($action, $section) {

        // On contruit le chemin dans le registre des droits.
        $path = $section . "." . $action;

        // On charge le registre.
        $rights = $this->getProperty('rights');

        // On contrôle que c'est bien un registre.
        if ($rights instanceof Registry) {

            // On récupère la valeur.
            $right = $rights->get($path, false);

            // On retourne la valeur en contrôllant qu'elle est bien égale à true.
            return ($right === true);

        }

        return false;

    }

    /**
     * Méthode proxy pour le modèle pour mettre à jour la date de visite.
     *
     * @param   integer $timestamp The timestamp, defaults to 'now'.
     *
     * @return  boolean  True en cas de succès.
     */
    public function setLastVisit($timestamp = null) {

        // On récupère le table.
        $table = Table::getInstance('user');

        // On met à jour la date.
        return $table->setLastVisit($timestamp, $this->getProperty('id'));
    }

    /**
     * Méthode proxy pour le modèle pour charger un utilisateur.
     *
     * @param   mixed $id The user id of the user to load
     *
     * @return  object  Les données de l'utilisteur.
     *
     * @throws  \RuntimeException
     */
    public function load($id) {

        // On récupère le table.
        $table = Table::getInstance('user');

        // On tente de charger l'utilisateur.
        if (!$table->load($id)) {

            // On déclenche une exception.
            throw new \RuntimeException(Text::sprintf('USER_ERROR_UNABLE_TO_LOAD_USER', $id));

        } else {

            // On récupère ses propriétés.
            $user = $table->dump(0);

            // Ce n'est plus un invité.
            $user->guest = 0;

            // On transforme les droits en objet registre.
            $user->rights = new Registry($user->rights);

            // On transforme les paramètres en registre.
            $user->params = new Registry($user->params);

            // On vire le mot de passe.
            $user->password = '';

        }

        return $user;
    }

    /**
     * Méthode pour déconnecter l'utilisateur.
     *
     * @return bool True si succès.
     */
    public function logout() {

        $my      = self::getInstance();
        $session = Web::getInstance()
                      ->getSession();
        $db      = Web::getInstance()
                      ->getDb();

        // Est-on en train de supprimer la session en cours ?
        if ($my->id == $this->id) {

            // On met à jour la dernière visite.
            $this->setLastVisit();

            // On supprime la session PHP.
            $session->destroy();
        }

        // On force la déconnexion de tous les utilisateurs avec cet id.
        $db->setQuery($db->getQuery(true)
                         ->delete($db->quoteName('#__session'))
                         ->where($db->quoteName('userid') . ' = ' . (int)$this->id))
           ->execute();

        return true;

    }

}