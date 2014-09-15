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

use EtdSolutions\Framework\Table\Table;
use Joomla\Language\Text;

defined('_JEXEC') or die;

/**
 * Modèle pour des éléments organisés en arbre.
 */
abstract class NestedItemModel extends ItemModel {

    /**
     * @var array Les Conditions de sélection et de tri des lignes imbriquées.
     */
    protected $reorderConditions = null;

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

        // On prépare le table avant de lier les données.
        $this->beforeTableBinding($table, $data, $isNew);

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
        if (!$table->store(false, $this->getReorderConditions($table))) {
            $this->setError($table->getError());

            return false;
        }

        // On nettoie le cache.
        $this->cleanCache();

        // On met à jour l'état du modèle.
        $this->__state_set = true;

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

        // On récupère les conditions pour être dans le bon arbre.
        $conds = $this->getReorderConditions($table);

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
                if (!$table->store(false, $conds)) {
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

    public function publish(&$pks, $value = 0) {

        // On s'assure d'avoir un tableau.
        $pks = (array)$pks;

        // On récupère le table.
        $table = $this->getTable();

        // On récupère les conditions pour être dans le bon arbre.
        $conds = $this->getReorderConditions($table);

        // On parcourt tous les éléments.
        foreach ($pks as $i => $pk) {

            // On teste si l'utilisateur peut modifier cet enregistrement.
            if ($this->allowEdit($pk)) {

                // On tente de charger la ligne.
                if ($table->load($pk) === false) {
                    $this->setError($table->getError());

                    return false;
                }

                // On tente de changer l'état de l'enregistrement.
                if (!$table->publish($pks, $value, $conds)) {
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

        // On récupère les conditions pour être dans le bon arbre.
        $conds = $this->getReorderConditions($table);

        // On supprime tous les éléments.
        foreach ($pks as $i => $pk) {

            // On teste si l'utilisateur peut supprimer cet enregistrement.
            if ($this->allowDelete($pk)) {
                if (!$table->delete($pk, true, $conds)) {
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
     * Méthode pour enregistrer la réorganisation d'un bout d'un arbre.
     * D'abord on enregistre les nouvelles valeurs pour l'ordre dans les valeurs "lft".
     * Ensuite, on appel la reconstruction du tableau pour implémenter le nouvel ordre.
     *
     * @param   array $pks Un tableau de clés primaires des ligns à réordonner.
     * @param   array $lft Les valeurs "lft" des lignes à réordonner.
     *
     * @return  void
     *
     * @throws  \RuntimeException en cas d'erreur db.
     */
    public function saveorder($pks = null, $lft = null) {

        // On récupère le table.
        $table = $this->getTable();

        // On récupère les conditions de réordonnencement.
        $conds = $this->getReorderConditions($table);

        // On enregistre le nouvel ordre.
        $table->saveorder($pks, $lft, $conds);

        // On nettoie le cache.
        $this->cleanCache();
    }

    /**
     * On change le positonnement de l'élément dans l'arbre avant de la sauvegarder.
     *
     * @param Table $table Le tableau à modifier.
     * @param array $data  Les données de l'élément.
     * @param bool  $isNew True si c'est un nouvel élément, false sinon.
     */
    protected function beforeTableBinding(Table &$table, &$data, $isNew = false) {

        // On définit le nouveau parent et on le met en dernière position si besoin.
        if ($isNew || (!$isNew && $table->parent_id != $data['parent_id'])) {
            $table->setLocation($data['parent_id'], 'last-child');
        }

    }

    /**
     * Définit la WHERE pour réordonner les lignes.
     *
     * @param array $conditions Un tableau de conditions à ajouter pour effectuer l'ordre.
     * @param Table $table      Une instance Table.
     */
    public function setReorderConditions($conditions = null, $table = null) {

        if (!isset($conditions)) {
            $conditions = array();
        }

        $this->reorderConditions = $conditions;
    }

    /**
     * Donne la clause WHERE pour réordonner les lignes.
     * Cela permet de s'assurer que la ligne sera déplacer relativement à une ligne qui correspondra à cette clause.
     *
     * @param   Table $table Une instance Table.
     *
     * @return  array  Un tableau de conditions à ajouter pour effectuer l'ordre.
     */
    protected function getReorderConditions($table) {

        if (!isset($this->reorderConditions)) {
            $this->setReorderConditions(null, $table);
        }

        return $this->reorderConditions;
    }

}
