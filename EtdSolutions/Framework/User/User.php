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
                'rights'    => new Registry
            );
        }

        parent::__construct($properties);
    }

    /**
     * Retourne l'objet global User, en le créant seulement il n'existe pas déjà.
     *
     * @param   integer $id L'utilisateur à charger (optionnel). Si null renvoi l'utilisateur de la session courante.
     *
     * @return  User  L'objet User.
     */
    public static function getInstance($id = null) {

        // Si l'id est null on renvoi l'utilisateur de la session.
        if (is_null($id)) {
            $app = Web::getInstance();
            $instance = $app->getSession()->get('user');

            if (!($instance instanceof User)) {
                return new User;
            }

           return $instance;
        }

        // On regarde si cet utilisateur est déjà en cache.
        if (empty(self::$instances[$id])) {
            self::$instances[$id] = new User($id);
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
     * @param   string $action Le nom de l'action a contrôler.
     * @param   string $section La section sur laquelle on veut appliquer l'action.
     *
     * @return  boolean  True si autorisé, false sinon.
     */
    public function authorise($action, $section) {

        //@TODO: faire un vrai check !!!!!!!!
        return true;

    }

    /**
     * Méthode proxy pour le modèle pour mettre à jour la date de visite.
     *
     * @param   integer $timestamp The timestamp, defaults to 'now'.
     *
     * @return  boolean  True en cas de succès.
     */
    public function setLastVisit($timestamp = null) {

        // Create the user table object
        $model = $this->getModel();

        // On met à jour la date.
        return $model->setLastVisit($timestamp, $this->getProperty('id'));
    }

    /**
     * Méthode proxy pour le modèle pour charger un utilisateur.
     *
     * @param   mixed $id The user id of the user to load
     *
     * @return  object  Les données de l'utilisateur.
     *
     * @throws  \RuntimeException
     */
    public function load($id) {

        // On crée le modèle.
        $model = $this->getModel();

        // On charge l'utilisateur.
        $user = $model->getItem($id);

        // Si le modèle n'a pas chargé l'utilisateur.
        if (!isset($user)) {

            // On déclenche une exception.
            throw new \RuntimeException(Text::sprintf('USER_ERROR_UNABLE_TO_LOAD_USER', $id));

        } else {

            // Ce n'est plus un invité.
            $user->guest = 0;

        }

        return $user;
    }

    /**
     * Méthode pour charger le modèle UserModel.
     *
     * @see Model::getInstance
     */
    protected function getModel() {
        return Model::getInstance('User');
    }

}