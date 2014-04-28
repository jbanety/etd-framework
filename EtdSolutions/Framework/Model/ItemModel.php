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
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Modèle pour gérer un élément.
 */
abstract class ItemModel extends Model {

    /**
     * Contexte dans lequel le modèle est instancié.
     *
     * @var    string
     */
    protected $context = null;

    /**
     * Cache interne des données.
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Instancie le modèle.
     *
     * @param Registry $state          L'état du modèle.
     * @param bool     $ignore_request Utilisé pour ignorer la mise à jour de l'état depuis la requête.
     */
    public function __construct(Registry $state = null, $ignore_request = false) {

        parent::__construct($state, $ignore_request);

        // On devine le contexte suivant le nom du modèle.
        if (empty($this->context)) {
            $this->context = strtolower($this->getName());
        }
    }

    /**
     * Renvoi les données d'un élément à charger en BDD.
     *
     * @param mixed $id Si null, l'id est chargé dans l'état.
     */
    public function getItem($id = null) {

        if (empty($id)) {
            $id = $this->get($this->context . '.id');
        }

        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $item = $this->loadItem($id);

        // On ajoute l'élement au cache.
        $this->cache[$id] = $item;

        return $this->cache[$id];

    }

    /**
     * Méthode pour charger un élément.
     * Elle doit être implémentée dans chaque modèle.
     *
     * @param int $id
     *
     * @return mixed
     */
    abstract protected function loadItem($id);

    /**
     * Méthode pour définir automatiquement l'état du modèle.
     */
    protected function populateState() {

        $app = Web::getInstance();

        // Load the object state.
        $id = $app->input->get('id', 0, 'int');
        $this->setState($this->context . '.id', $id);
    }

}