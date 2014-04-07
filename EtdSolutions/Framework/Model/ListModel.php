<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etdglobalone.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Model;

use Joomla\Database\DatabaseQuery;

defined('_JEXEC') or die;

/**
 * Modèle de base
 */
abstract class ListModel extends Model {

    /**
     * @var array Cache interne des données.
     */
    protected $cache = array();

    /**
     * @var DatabaseQuery Un cache interne pour la dernière requête utilisée.
     */
    protected $query;

    /**
     * Méthode pour obtenir un tableau des éléments.
     *
     * @return mixed Un tableau des éléments en cas de succès, false sinon.
     */
    public function getItems() {

        // On récupère la clé de stockage.
        $store = $this->getStoreId();

        // On essaye de charger les données depuis le cache si possible.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        // On charge la liste des éléments.
        $query = $this->_getListQuery();

        $this->db->setQuery($query, $this->getStart(), $this->getLimit());
        $items = $this->db->loadObjectList();

        // Add the items to the internal cache.
        $this->cache[$store] = $items;

        return $this->cache[$store];

    }

    /**
     * Récupère le numéro de départ des éléments dans la collection.
     *
     * @return  integer  le numéro de départ des éléments dans la collection.
     */
    public function getStart() {

        $store = $this->getStoreId('getStart');

        // On essaye de charger les données depuis le cache si possible.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $start = $this->get('list.start');
        $end = $this->get('list.end');
        $total = $this->getTotal();

        if ($start > $end) {
            $start = $end;
        }

        if ($start > $total) {
            $start = max(1, $total - $end);
        }

        $this->cache[$store] = $start;

        return $this->cache[$store];
    }

    public function getLimit() {

        $store = $this->getStoreId('getLimit');

        // On essaye de charger les données depuis le cache si possible.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $start = $this->getStart();
        $end = $this->get('list.end');
        $total = $this->getTotal();

        $limit = $end - $start + 1;

        if ($limit > $total) {
            $limit = 0;
        }

        $this->cache[$store] = $limit;

        return $this->cache[$store];

    }

    /**
     * Récupère le total des éléments dans la collection.
     *
     * @return  integer  le total des éléments dans la collection.
     */
    public function getTotal() {

        $store = $this->getStoreId('getTotal');

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $query = $this->_getListQuery();

        // On utilise le rapide COUNT(*) si there no GROUP BY or HAVING clause:
        if ($query instanceof DatabaseQuery && $query->type == 'select' && $query->group === null && $query->having === null) {
            $query = clone $query;
            $query->clear('select')->clear('order')->select('COUNT(*)');

            $this->db->setQuery($query);
            $total = (int)$this->db->loadResult();

        } else {

            // Sinon on retombe sur une façon inefficace pour compter les éléments.
            $this->db->setQuery($query);
            $this->db->execute();

            $total = (int)$this->db->getNumRows();

        }

        // On ajoute le total au cache.
        $this->cache[$store] = $total;

        return $this->cache[$store];
    }

    public function parseRange($http_range=null) {

        if (!isset($http_range)) {
            $http_range = $_SERVER['HTTP_RANGE'];
        }

        $range = explode("-", str_replace("items=", "", $http_range));
        $this->set('list.start', $range[0]);
        $this->set('list.end', $range[1]);

        return $this;
    }

    /**
     * Méthode pour récupérer un objet contenant l'interval de sélection des données.
     *
     * @return  Object  Un objet représentant l'interval.
     */
    public function getContentRange() {

        // Clé de stockage.
        $store = $this->getStoreId('getContentRange');

        // Depuis le cache ?
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $start = (int) $this->getStart();
        $limit = (int) $this->get('list.limit');
        $total = (int) $this->getTotal();
        $end   = $start + $limit;

        if ($limit > $total) {
            $start = 0;
        }

        if (!$limit) {
            $limit = $total;
            $start = 0;
        }

        if ($start > $total - $limit) {
            $start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
        }

        if ($end > $total) {
            $end = $total;
        }

        $this->cache[$store] = $start . '-' . $end . '/' . $total;

        return $this->cache[$store];
    }

    /**
     * Méthode pour récupérer un objet DatabaseQuery pour récupérer les données dans la base.
     *
     * @return  DatabaseQuery   Un objet DatabaseQuery.
     */
    protected function getListQuery() {
        return $this->db->getQuery(true);
    }

    /**
     * Méthode pour obtenir un identifiant de stockage basé sur l'état du modèle.
     *
     * @param   string $id Un identifiant de base.
     *
     * @return  string  Un identifiant de stockage.
     */
    protected function  getStoreId($id = '') {

        $id .= ':' . $this->get('list.start');
        $id .= ':' . $this->get('list.limit');
        $id .= ':' . $this->get('list.ordering');
        $id .= ':' . $this->get('list.direction');

        return md5($id);
    }

    /**
     * Methode pour mettre en cache la dernière requête construite.
     *
     * @return  DatabaseQuery  Un objet DatabaseQuery
     */
    protected function _getListQuery() {

        // Capture la dernière clé de stockage utilisée.
        static $lastStoreId;

        // On récupère la clé de stockage actuelle.
        $currentStoreId = $this->getStoreId();

        // Si la dernière clé est différente de l'actuelle, on actualise la requête.
        if ($lastStoreId != $currentStoreId || empty($this->query)) {
            $lastStoreId = $currentStoreId;
            $this->query = $this->getListQuery();
        }

        return $this->query;
    }

}
