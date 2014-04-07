<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Model;

use EtdSolutions\Framework\Application\Web;
use Joomla\Model\AbstractDatabaseModel;

defined('_JEXEC') or die;

/**
 * Modèle de base
 */
abstract class Model extends AbstractDatabaseModel {

    /**
     * @var  Model  L'instance de l'application.
     */
    private static $instance;

    public function __construct() {

        parent::__construct(Web::getInstance()
                               ->getDb());
    }

    /**
     * Méthode pour récupérer une instance d'un modèle, la créant si besoin.
     *
     * @param   string $name Le nom du modèle
     *
     * @return  mixed  L'instance.
     *
     * @throws   \RuntimeException
     */
    public static function getInstance($name) {

        $name = ucfirst($name);
        if (empty(self::$instance[$name])) {

            // On définit la liste des espaces de noms dans laquelle le modèle peut se trouver.
            $namespaces = array(
                '\\EtdSolutions\\Framework',
                Web::getInstance()->get('app_namespace')
            );

            $className = "";

            // On cherche le modèle dans ces espaces de nom.
            foreach($namespaces as $namespace) {

                $className = $namespace . '\\Model\\' . ucfirst($name) . 'Model';

                // Si on a trouvé la classe, on arrête.
                if (class_exists($className)) {
                    break;
                }

            }

            // On vérifie que l'on a bien une classe valide.
            if (!class_exists($className)) {
                throw new \RuntimeException("Unable find model " . $name, 500);
            }

            self::$instance[$name] = new $className;
        }

        return self::$instance[$name];
    }

    /**
     * Récupère une valeur dans l'état du modèle.
     *
     * @param   string $path    Chemin dans le registre
     * @param   mixed  $default Une valeur par défaut optionnelle.
     *
     * @return  mixed   La valeur ou null
     */
    public function get($path, $default = null) {

        return $this->state->get($path, $default);
    }

    /**
     * Définit une valeur par défaut dans l'état du modèle.
     * Si une valeur est déjà présente, on ne la change pas.
     *
     * @param   string $path    Chemin dans le registre
     * @param   mixed  $default Une valeur par défaut optionnelle.
     *
     * @return  mixed   La valeur ou null
     */
    public function def($path, $default = null) {

        return $this->state->def($path, $default);
    }

    /**
     * Définit une valeur dans l'état du modèle.
     *
     * @param   string $path  Chemin dans le registre
     * @param   mixed  $value Une valeur par défaut optionnelle.
     *
     * @return  mixed  La valeur précédente si elle existe.
     */
    public function set($path, $value) {

        return $this->state->set($path, $value);
    }

}
