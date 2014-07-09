<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Controller;

use EtdSolutions\Framework\Application\Web;
use EtdSolutions\Framework\Model\ItemModel;
use EtdSolutions\Framework\Model\Model;
use EtdSolutions\Framework\User\User;
use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;
use Joomla\Language\Text;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

/**
 * Controller pour un élément.
 */
class ItemController extends Controller {

    /**
     * @var string La route pour la vue de listing des éléments.
     */
    protected $listRoute = null;

    /**
     * @var string La route pour la vue de visualisation et de modification d'un élément.
     */
    protected $itemRoute = null;

    /**
     * Instancie le controller.
     *
     * @param   Input               $input The input object.
     * @param   AbstractApplication $app   The application object.
     */
    public function __construct(Input $input = null, AbstractApplication $app = null) {

        // On devine la route de l'élément suivant le nom du controller.
        if (empty($this->itemRoute)) {
            $this->itemRoute = strtolower($this->getName());
        }

        // On devine la route de listing comme le pluriel de la route pour un élément.
        if (empty($this->listRoute)) {

            // Pluralisation simple basée sur un snippet de Paul Osman.
            // http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
            //
            // Pour des types plus complexes, il suffit de définir manuellement la variable dans la classe.
            $plural = array(
                '/(x|ch|ss|sh)$/i'      => "$1es",
                '/([^aeiouy]|qu)y$/i'   => "$1ies",
                '/([^aeiouy]|qu)ies$/i' => "$1y",
                '/(bu)s$/i'             => "$1ses",
                '/s$/i'                 => "s",
                '/$/'                   => "s"
            );

            // On trouve le bon match en utlisant les expressions régulières.
            foreach ($plural as $k => $v) {
                if (preg_match($k, $this->itemRoute)) {
                    $this->listRoute = preg_replace($k, $v, $this->itemRoute);
                    break;
                }
            }
        }

        return parent::__construct($input, $app);
    }

    /**
     * Ajoute un élément.
     *
     * @return mixed
     */
    public function add() {

        // On contrôle les droits.
        if (!$this->allowAdd()) {
            $this->redirect("/" . $this->listRoute, Text::_('APP_ERROR_UNAUTHORIZED_ACTION'), 'error');
        }

        // On passe en layout de création (form).
        $this->getInput()
             ->set('layout', 'form');

        // On affiche la vue.
        return $this->display();

    }

    /**
     * Modifie un élément.
     *
     * @return mixed
     */
    public function edit() {

        // On récupère l'identifiant.
        $id = $this->getInput()
                   ->get('id', null, 'array');

        // Si on a aucun élément, on redirige vers la liste avec une erreur.
        if (!is_array($id) || count($id) < 1) {
            $this->redirect("/" . $this->listRoute, Text::_('CTRL_' . strtoupper($this->getName()) . '_NO_ITEM_SELECTED'), 'warning');

            return false;
        }

        // On ne prend que le premier des ids.
        $id = (int)$id[0];

        // On modifie l'input pour mettre l'id.
        $this->getInput()
             ->set('id', $id);

        // On contrôle les droits.
        if (!$this->allowEdit($id)) {
            $this->redirect("/" . $this->listRoute, Text::_('APP_ERROR_UNAUTHORIZED_ACTION'), 'error');
        }

        // On passe en layout de création (form).
        $this->getInput()
             ->set('layout', 'form');

        // On affiche la vue.
        return $this->display();

    }

    /**
     * Supprime un élément.
     *
     * @return bool
     */
    public function delete() {

        // App
        $app = Web::getInstance();

        // On contrôle le jeton de la requête.
        if (!$app->checkToken()) {
            $app->raiseError(Text::_('APP_ERROR_INVALID_TOKEN', 403));
        }

        // On récupère les identifiants
        $id = $this->getInput()
                   ->get('id', null, 'array');

        // Si on a aucun élément, on redirige vers la liste avec une erreur.
        if (!is_array($id) || count($id) < 1) {
            $this->redirect("/" . $this->listRoute, Text::_('CTRL_' . strtoupper($this->getName()) . '_NO_ITEM_SELECTED'), 'warning');

            return false;
        }

        // On récupềre le model
        $model = $this->getModel();

        // On s'assure que ce sont bien des integers.
        $id = ArrayHelper::toInteger($id);

        // On effectue la suppression.
        if ($model->delete($id)) {

            // La suppresion s'est faite avec succès.
            $this->redirect("/" . $this->listRoute, Text::plural('CTRL_' . strtoupper($this->getName()) . '_N_ITEMS_DELETED', count($id)), 'success');

        } else {

            // Une erreur s'est produite.
            $this->redirect("/" . $this->listRoute, $model->getError(), 'error');
        }

        return true;

    }

    /**
     * Méthode pour sauver un enregistrement.
     */
    public function save() {

        // App
        $app = Web::getInstance();

        // On contrôle le jeton de la requête.
        if (!$app->checkToken()) {
            $app->raiseError(Text::_('APP_ERROR_INVALID_TOKEN', 403));
        }

        /**
         * @var ItemModel $model
         */
        $model    = $this->getModel();
        $input    = $this->getInput();
        $data     = $input->get('etdform', array(), 'array');
        $recordId = (int)$data['id'];

        // Contrôle d'accès.
        if (!$this->allowSave($recordId)) {
            $this->redirect("/" . $this->listRoute, Text::_('APP_ERROR_UNAUTHORIZED_ACTION'), 'error');

            return false;
        }

        $this->beforeSave($model, $data);

        // On filtre les données
        $data = $model->filter($data);

        // On valide les données.
        $valid = $model->validate($data);

        if ($valid === false) {

            // On récupère les messages de validation.
            $errors = $model->getErrors();

            // On affiche jusqu'à 3 messages de validation à l'utilisateur.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // On sauvegarde les données dans la session.
            $app->setUserState($this->getName() . '.edit.data', $data);

            // On renvoi vers le formulaire.
            $this->redirect("/" . $this->itemRoute . $this->getRedirectToItemAppend($recordId));

            return false;
        }

        // On enregistre.
        if (!$model->save($data)) {

            // On sauvegarde les données VALIDÉES dans la session.
            $app->setUserState($this->getName() . '.edit.data', $data);

            // On renvoi vers le formulaire.
            $this->redirect("/" . $this->itemRoute . $this->getRedirectToItemAppend($recordId), Text::sprintf('APP_ERROR_CTRL_SAVE_FAILED', $model->getError()), 'error');

            return false;

        }

        // On invoque la méthode afterSave pour permettre aux contrôleurs enfants d'accéder au modèle.
        $this->afterSave($model, $data);

        // On nettoie les informations d'édition de l'enregistrement dans la session.
        $app->setUserState($this->getName() . '.edit.data', null);

        // On redirige vers la page de listing.
        $this->redirect("/" . $this->listRoute, Text::_('CTRL_' . strtoupper($this->getName()) . '_SAVE_SUCCESS'), 'success');

        return true;

    }

    /**
     * Méthode pour annuler une édition.
     */
    public function cancel() {

        // On nettoie les informations d'édition de l'enregistrement dans la session.
        Web::getInstance()
           ->setUserState($this->getName() . '.edit.data', null);

        // On redirige vers la liste.
        $this->redirect("/" . $this->listRoute);

    }

    /**
     * Méthode pour afficher la page d'information sur un élément.
     *
     * @return string|object
     */
    public function view() {

        // On contrôle les droits.
        if (!$this->allowView()) {
            $this->redirect("/" . $this->listRoute, Text::_('APP_ERROR_UNAUTHORIZED_ACTION'), 'error');
        }

        // On nettoie les informations d'édition de l'enregistrement dans la session.
        Web::getInstance()
           ->setUserState($this->getName() . '.edit.data', null);

        //On affiche la vue
        return $this->display();

    }

    /**
     * Méthode pour dupliquer un enregistrement.
     *
     * @return bool
     */
    public function duplicate() {

        // App
        $app = $this->getApplication();

        // On contrôle le jeton de la requête.
        if (!$app->checkToken()) {
            $app->raiseError(Text::_('APP_ERROR_INVALID_TOKEN', 403));
        }

        $model = $this->getModel();
        $id    = $this->getInput()
                      ->get('id', 0, 'array');

        // Si on a aucun élément, on redirige vers la liste avec une erreur.
        if (!is_array($id) || count($id) < 1) {
            $this->redirect("/" . $this->listRoute, Text::_('CTRL_' . strtoupper($this->getName()) . '_NO_ITEM_SELECTED'), 'warning');

            return false;
        }

        // On s'assure que ce sont bien des integers.
        $id = ArrayHelper::toInteger($id);

        // On duplique.
        if ($model->duplicate($id)) {

            // La suppresion s'est faite avec succès.
            $this->redirect("/" . $this->listRoute, Text::plural('CTRL_' . strtoupper($this->getName()) . '_N_ITEMS_DUPLICATED', count($id)), 'success');

        } else {

            // Une erreur s'est produite.
            $this->redirect("/" . $this->listRoute, $model->getError(), 'error');
        }

        return true;

    }

    /**
     * Méthode pour contrôler si l'utilisateur peut créer un nouvel enregistrement.
     *
     * @return  boolean
     */
    protected function allowAdd() {

        return User::getInstance()
                   ->authorise('add', $this->getName());
    }

    /**
     * Méthode pour contrôler si l'utilisateur peut modifier un enregistrement.
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
     * Méthode pour contrôler si l'utilisateur peut afficher un enregistrement.
     *
     * @param   array|int $id L'identifiant de l'enregistrement.
     *
     * @return  boolean
     */
    protected function allowView($id = null) {

        return User::getInstance()
                   ->authorise('view', $this->getName());
    }

    /**
     * Méthode pour contrôler si l'utilisateur peut enregistrer un enregistrement.
     *
     * @param   array|int $id L'identifiant de ou des enregistrements.
     *
     * @return  boolean
     */
    protected function allowSave($id = null) {

        if ($id) {
            return $this->allowEdit($id);
        } else {
            return $this->allowAdd();
        }
    }

    /**
     * Donne les segments à ajouter à la route pour la redirection vers la vue de l'élément.
     *
     * @param   integer $recordId La clé primaire de l'élément.
     *
     * @return  string  Les segments à ajouter à l'URL.
     */
    protected function getRedirectToItemAppend($recordId = null) {

        $append = "";

        if (is_int($recordId)) {
            if ($recordId == 0) {
                $append = "/add";
            } else if ($recordId > 0) {
                $append = "/edit/" . $recordId;
            }
        }

        return $append;
    }

    /**
     * Méthode qui permet aux controllers enfants d'accéder
     * aux données et au modèle avant la sauvegarde.
     *
     * @param   Model $model Le modèle.
     * @param   array $data  Les données.
     *
     * @return  void
     */
    protected function beforeSave(Model &$model, &$data) {

    }

    /**
     * Méthode qui permet aux controllers enfants d'accéder
     * aux données et au modèle après la sauvegarde.
     *
     * @param   Model $model Le modèle.
     * @param   array $data  Les données.
     *
     * @return  void
     */
    protected function afterSave(Model &$model, $data = array()) {

    }

}