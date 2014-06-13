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
use EtdSolutions\Framework\Table\Table;
use EtdSolutions\Framework\User\User;
use Joomla\Filesystem\Path;
use EtdSolutions\Framework\Form\Form;
use Joomla\Form\FormHelper;
use Joomla\Language\Text;
use Joomla\Registry\Registry;
use Joomla\String\String;

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
     *
     * @return \stdClass Un objet représentant l'élément.
     */
    public function getItem($id = null) {

        $id    = (!empty($id)) ? $id : (int)$this->get($this->context . '.id');
        $table = $this->getTable();

        if ($id > 0) {

            // On tente de charger la ligne.
            $return = $table->load($id);

            // On contrôle les erreurs.
            if ($return === false && $table->getError()) {
                $this->setError($table->getError());

                return false;
            }
        }

        // On récupère les données de l'élément.
        $item = $table->dump(0);

        return $item;

    }

    /**
     * Donne le formulaire associé au modèle.
     *
     * @param null  $name
     * @param array $options
     *
     * @return Form
     * @throws \RuntimeException
     */
    public function getForm($name = null, array $options = array()) {

        if (!isset($name)) {
            $name = $this->getName();
        }

        // On compile un identifiant de cache.
        $store = md5("getForm:" . $name . ":" . serialize($options));

        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        if (!isset($options['control'])) {
            $options['control'] = 'etdform';
        }

        // On instancie le formulaire.
        $form = new Form($name, $options);

        // On ajoute le chemin vers les fichiers XML.
        FormHelper::addFormPath(JPATH_FORMS);

        // On ajoute le chemin vers les types de champs.
        $app  = Web::getInstance();
        $path = Path::clean(JPATH_LIBRARIES . $app->get('app_namespace') . "/Form/Field");
        FormHelper::addFieldPath($path);

        // On charge les champs depuis le XML.
        if (!$form->loadFile($name)) {
            throw new \RuntimeException(Text::_('APP_ERROR_FORM_NOT_LOADED'), 500);
        }

        // On modifie le formulaire si besoin.
        $form = $this->preprocessForm($form);

        // On charge les données si nécessaire.
        $data = $this->loadFormData($options);

        // On les lie au formulaire.
        if (!empty($data)) {
            $form->bind($data);
        }

        // On ajoute l'élement au cache.
        $this->cache[$store] = $form;

        return $this->cache[$store];

    }

    /**
     * Méthode pour valider les données en entrée suivant les règles du formulaire associé au modèle.
     *
     * @param $data array Les données à valider.
     *
     * @return boolean True si valide, false sinon.
     */
    public function validate($data) {

        $form = $this->getForm();
        $ret  = $form->validate($data);

        // Si le form n'est pas valide, on stocke les erreurs dans le modèle.
        if ($ret === false) {
            $this->setErrors($form->getErrors());
        }

        return $ret;
    }

    /**
     * Méthode pour ne valider qu'un seul champ suivant les règles du formulaire associé au modèle.
     *
     * @param $name  string Le nom du champ.
     * @param $data mixed Les données à tester.
     *
     * @return boolean True si valide, false sinon.
     */
    public function validateField($name, $data) {

        $form  = $this->getForm();
        $ret   = $form->validate($data, null, $name);

        // Si le champ n'est pas valide, on stocke les erreurs dans le modèle.
        if ($ret === false) {
            $this->setErrors($form->getErrors());
        }

        return $ret;

    }

    /**
     * Méthode pour filtrer les données en entrée suivant le formulaire associé au modèle.
     *
     * @param $data array Données à filtrer.
     *
     * @return array Les données filtrées.
     */
    public function filter($data) {

        $form = $this->getForm();
        $data = $form->filter($data);

        return $data;

    }

    /**
     * Méthode pour supprimer des enregistrements.
     *
     * @param $pks array|int Un tableau de clés primaires ou une clé primaire.
     *
     * @return bool True si
     */
    public function delete(&$pks) {

        // On s'assure d'avoir un tableau.
        $pks = (array)$pks;

        // On récupère le table.
        $table = $this->getTable();

        // On supprime tous les éléments.
        foreach ($pks as $i => $pk) {

            // On teste si l'utilisateur peut supprimer cet enregistrement.
            if ($this->allowDelete($pk)) {
                if (!$table->delete($pk)) {
                    $this->setError(Text::_('APP_ERROR_MODEL_UNABLE_TO_DELETE_ITEM'));

                    return false;
                }
            } else {

                // On retire la clé primaire fautive.
                unset($pks[$i]);

                // On retourne une erreur.
                $this->setError(Text::_('APP_ERROR_MODEL_DELETE_NOT_PERMITTED'));

                return false;
            }
        }

        // On nettoie le cache.
        $this->cleanCache();

        return true;

    }

    /**
     * Méthode pour enregistrer les données du formulaire.
     *
     * @param   array $data Les données du formulaire.
     *
     * @return  boolean  True en cas de succès, false sinon.
     */
    public function save($data) {

        // On récupère le table et le nom de la clé primaire.
        $table = $this->getTable();
        $key   = $table->getPk();

        // On récupère la clé primaire.
        $pk = (!empty($data[$key])) ? $data[$key] : (int)$this->get($this->context . '.id');

        // Par défaut, on crée un nouvel enregistrement.
        $isNew = true;

        // On charge la ligne si c'est un enregistrement existant.
        if ($pk > 0) {
            $table->load($pk);
            $isNew = false;
        }

        // On relie les données
        if (!$table->bind($data)) {
            $this->setError($table->getError());

            return false;
        }

        // On prépare la ligne avant de la sauvegarder.
        $table = $this->preprocessTable($table);

        // On contrôle les données.
        if (!$table->check()) {
            $this->setError($table->getError());

            return false;
        }

        // On stocke les données.
        if (!$table->store()) {
            $this->setError($table->getError());

            return false;
        }

        // On nettoie le cache.
        $this->cleanCache();

        // On met à jour la session.
        $pkName = $table->getPk();
        if (isset($table->$pkName)) {
            $this->set($this->context . '.id', $table->$pkName);
        }
        $this->set($this->context . '.isNew', $isNew);

        return true;

    }

    /**
     * Méthode pour dupliquer un enregistrement.
     *
     * @param $pks array Un tableau des clés primaires représentantes des enregistrements à dupliquer.
     *
     * @return bool
     */
    public function duplicate($pks) {

        // On s'assure d'avoir un tableau.
        $pks = (array)$pks;

        // On récupère le table.
        $table = $this->getTable();

        // On supprime tous les éléments.
        foreach ($pks as $i => $pk) {

            // On teste si l'utilisateur peut modifier cet enregistrement et en ajouter un autre.
            if ($this->allowEdit($pk) && $this->allowAdd()) {

                // On tente de charger la ligne.
                if ($table->load($pk) === false) {
                    $this->setError($table->getError());

                    return false;
                }

                // On retire la clé primaire pour créer une nouvelle ligne.
                $table->{$table->getPk()} = null;

                // On change les champs.
                $this->prepareDuplicatedTable($table);

                // On contrôle les données.
                if (!$table->check()) {
                    $this->setError($table->getError());

                    return false;
                }

                // On stocke les données.
                if (!$table->store()) {
                    $this->setError($table->getError());

                    return false;
                }

            } else {

                // On retire la clé primaire fautive.
                unset($pks[$i]);

                // On retourne une erreur.
                $this->setError(Text::_('CTRL_LIST_ERROR_DUPLICATE_NOT_PERMITTED'));

                return false;
            }
        }

        // On nettoie le cache.
        $this->cleanCache();

        return true;

    }

    /**
     * Méthode pour nettoyer le cache.
     *
     * @param null $id Un identifiant de cache optionnel.
     */
    public function cleanCache($id = null) {
        //@TODO: implémenter le mécanisme de cache.
    }

    /**
     * Méthode pour contrôler si l'utilisateur peut supprimer un enregistrement.
     *
     * @param   array|int $id L'identifiant de l'enregistrement.
     *
     * @return  boolean
     */
    protected function allowDelete($id = null) {

        return User::getInstance()
                   ->authorise('delete', $this->getName());
    }

    /**
     * Méthode pour contrôler si l'utilisateur peut ajouter un enregistrement.
     *
     * @param   array|int $id L'identifiant de l'enregistrement.
     *
     * @return  boolean
     */
    protected function allowAdd() {

        return User::getInstance()
                   ->authorise('create', $this->getName());
    }

    /**
     * Méthode pour contrôler si l'utilisateur peut éditer un enregistrement.
     *
     * @param   array|int $id L'identifiant de l'enregistrement.
     *
     * @return  boolean
     */
    protected function allowEdit($id = null) {

        return User::getInstance()
                   ->authorise('edit', $this->getName());
    }

    /**
     * Méthode pour modifier le formulaire avant la liaison avec les données.
     *
     * @param Form $form
     *
     * @return Form
     */
    protected function preprocessForm(Form $form) {

        return $form;
    }

    /**
     * Prépare et nettoie les données du Table avant son enregistrement.
     *
     * @param   Table $table Une référence à un objet Table.
     *
     * @return  Table
     */
    protected function preprocessTable(Table $table) {

        // Les classes dérivées pourront l'implémenter si besoin.

        return $table;
    }

    /**
     * Prépare le Table avant sa duplication.
     * On l'utilise pour changer certain champs avant son insertion en BDD.
     *
     * @param Table $table Une référence à un objet Table.
     */
    protected function prepareDuplicatedTable(Table &$table) {

        // Les classes dérivées pourront l'implémenter si besoin.

    }

    protected function loadFormData($options = array()) {

        $app = Web::getInstance();

        // Je tente les charger les données depuis la session.
        $data = $app->getUserStateFromRequest($this->context . '.edit.data', 'etdform', array(), 'array');

        // Si on a pas de données, on charge celle de l'élément si on a est en édition.
        if (empty($data) && $this->get($this->context . '.id')) {
            $data = $this->getItem();
        }

        return $data;

    }

    /**
     * Méthode pour définir automatiquement l'état du modèle.
     */
    protected function populateState() {

        $app = Web::getInstance();

        // Load the object state.
        $id = $app->input->get('id', 0, 'int');
        $this->set($this->context . '.id', $id);
    }

}