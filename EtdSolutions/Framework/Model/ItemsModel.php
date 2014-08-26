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
use EtdSolutions\Framework\Pagination\Pagination;
use Joomla\Database\DatabaseQuery;
use Joomla\Filesystem\Path;
use Joomla\Form\Form;
use Joomla\Form\FormHelper;
use Joomla\Language\Text;

defined('_JEXEC') or die;

/**
 * Modèle de base
 */
abstract class ItemsModel extends Model {

    /**
     * Champs de filtrage ou de tri valides.
     *
     * @var    array
     */
    protected $filter_fields = array();

    /**
     * Cache interne des données.
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Un cache interne pour la dernière requête utilisée.
     *
     * @var DatabaseQuery
     */
    protected $query;

    /**
     * Contexte dans lequel le modèle est instancié.
     *
     * @var    string
     */
    protected $context = null;

    /**
     * Nom de la colonne avec laquelle on indexe le listing.
     *
     * @var string
     *
     * @see ItemsModel::getItems
     */
    protected $indexBy = '';

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

        $this->db->setQuery($query, $this->getStart(), $this->get('list.limit'));
        $items = $this->db->loadObjectList($this->indexBy);

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
        $limit = $this->get('list.limit');
        $total = $this->getTotal();

        if ($start > $total - $limit) {
            $start = max(0, (int)(ceil($total / $limit) - 1) * $limit);
        }

        $this->cache[$store] = $start;

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
            $query->clear('select')
                  ->clear('order')
                  ->select('COUNT(*)');

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

    /**
     * Méthode pour donner un objet Pagination pour les données.
     *
     * @return  Pagination  Un objet Pagination.
     */
    public function getPagination() {

        $store = $this->getStoreId('getPagination');

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        // On crée l'objet pagination.
        $pagination = new Pagination($this->getTotal(), $this->getStart(), $this->get('list.limit'));

        $this->cache[$store] = $pagination;

        return $this->cache[$store];
    }

    public function getFilterForm($name = null) {

        if (!isset($name)) {
            $name = $this->getName();
        }

        $name = "filters_" . strtolower($name);

        // On compile un identifiant de cache.
        $store = md5("getFilterForm:" . $name);

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        // On instancie le formulaire.
        $form = new Form($name);

        // On ajoute le chemin vers les fichiers XML.
        FormHelper::addFormPath(JPATH_FORMS);

        // On ajoute le chemin vers les types de champs.
        $app = Web::getInstance();
        $path = Path::clean(JPATH_LIBRARIES . $app->get('app_namespace') . "/Form/Field");
        FormHelper::addFieldPath($path);

        // On charge les champs depuis le XML.
        if (!$form->loadFile($name)) {
            throw new \RuntimeException(Text::sprintf('APP_ERROR_FORM_NOT_LOADED', $name), 500);
        }

        // On tente de charger les données depuis la session.
        $data = array();
        $data['filter'] = $app->getUserState($this->context.'.filter', array());

        // Si on a pas de données, on prérempli quelques options.
        if (!array_key_exists('list', $data['filter'])) {
            $data['filter']['list'] = array(
                'direction' => $this->get('list.direction'),
                'limit'     => $this->get('list.limit'),
                'ordering'  => $this->get('list.ordering'),
                'start'     => $this->get('list.start')
            );
        }

        // On les lie au formulaire.
        $form->bind($data);

        // On ajoute l'élement au cache.
        $this->cache[$store] = $form;

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

    /**
     * Méthode pour définir automatiquement l'état du modèle.
     *
     * Cette méthode doit être appelée une fois par instanciation et est
     * conçue pour être appelée lors du premier appel de get() sauf si le
     * la configuration du modèle dit de ne pas l'appeler.
     *
     * @param   string $ordering  Un champ de tri optionnel.
     * @param   string $direction Un direction de tri optionnelle (asc|desc).
     *
     * @return  void
     *
     * @note    Appeler get() dans cette méthode résultera en une récursion.
     */
    protected function populateState($ordering = null, $direction = null) {

        $app = Web::getInstance();

        // On reçoit et on définit les filtres.
        if ($filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array')) {
            foreach ($filters as $name => $value) {
                $this->set('filter.' . $name, $value);
            }
        }

        // Limites
        $limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', $app->get('list_limit'), 'uint');
        $this->set('list.limit', $limit);

        // Check if the ordering field is in the white list, otherwise use the incoming value.
        $value = $app->getUserStateFromRequest($this->context . '.ordercol', 'list_ordering', $ordering);

        if (!in_array($value, $this->filter_fields)) {
            $value = $ordering;
            $app->setUserState($this->context . '.ordercol', $value);
        }

        $this->set('list.ordering', $value);

        // Check if the ordering direction is valid, otherwise use the incoming value.
        $value = $app->getUserStateFromRequest($this->context . '.orderdirn', 'list_direction', $direction);

        if (!in_array(strtoupper($value), array(
            'ASC',
            'DESC',
            ''
        ))
        ) {
            $value = $direction;
            $app->setUserState($this->context . '.orderdirn', $value);
        }

        $this->set('list.direction', $value);

        // Start
        $value      = $app->getUserStateFromRequest($this->context . '.start', 'start', 0, 'uint');
        $limitstart = (!empty($limit) ? (floor($value / $limit) * $limit) : 0);
        $this->set('list.start', $limitstart);

    }

}
