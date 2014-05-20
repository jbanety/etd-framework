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
use Joomla\Filesystem\Path;
use Joomla\Form\Form;
use Joomla\Form\FormHelper;
use Joomla\Language\Text;
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
     * @param null $name
     * @param array $options
     * @return Form
     * @throws \RuntimeException
     */
    public function getForm($name=null, array $options = array()) {

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
        $app = Web::getInstance();
        $path = Path::clean(JPATH_LIBRARIES . $app->get('app_namespace') . "/Form");
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
     * Méthode pour modifier le formulaire avant la liaison avec les données.
     *
     * @param Form $form
     * @return Form
     */
    protected function preprocessForm(Form $form) {
        return $form;
    }

    public function validate($data) {

        $form =$this->getForm();
        $ret = $form->validate($data);

        // Si le form n'est pas valide, on stocke les erreurs dans le modèle.
        if ($ret === false) {
            $this->setErrors($form->getErrors());
        }

        return $ret;
    }

    public function filter($data) {

        $form = $this->getForm();
        $data = $form->filter($data);

        return $data;

    }

    protected function loadFormData($options=array()) {

        $app = Web::getInstance();

        // Je tente les charger les données depuis la session.
        $data = $app->getUserStateFromRequest($this->context.'.edit.data', 'etdform', array(), 'array');

        // Si on a pas de données, on charge celle de l'élément si on a est en édition.
        if (empty($data) && $this->get($this->context . '.id')) {
            $data = $this->getItem();
        }

        return $data;

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
        $this->set($this->context . '.id', $id);
    }


}