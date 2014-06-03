<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Table;

use EtdSolutions\Framework\Application\Web;
use Joomla\Data\DataObject;
use Joomla\Language\Text;

defined('_JEXEC') or die;

/**
 * Représentation d'une table dans la base de données.
 */
abstract class Table extends DataObject {

    /**
     * @var string Nom du table.
     */
    protected $name;

    /**
     * @var string Nom de la table dans la BDD.
     */
    protected $table = '';

    /**
     * @var string Nom de la clé primaire dans la BDD.
     */
    protected $pk = '';

    /**
     * @var array Les erreurs survenues dans le table.
     */
    protected $errors = array();

    /**
     * @var  Table  Les instances des tables.
     */
    private static $instances;

    /**
     * Constructeur pour définir le nom de la table et la clé primaire.
     *
     * @param   string $table Nom de la table à modéliser.
     * @param   mixed  $pk    Nom de la clé primaire.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($table, $pk = 'id') {

        if(empty($table)) {
            throw new \InvalidArgumentException("Table name is empty");
        }

        // Nom de la table.
        $this->table = $table;

        // Nom de la clé primaire.
        $this->pk = $pk;

    }

    /**
     * Méthode pour récupérer une instance d'un table, la créant si besoin.
     *
     * @param   string $name      Le nom de la classe
     *
     * @return  Table  L'instance.
     *
     * @throws   \RuntimeException
     */
    public static function getInstance($name) {

        $name  = ucfirst($name);
        $store = md5($name);

        if (empty(self::$instances[$store])) {

            // On définit la liste des espaces de noms dans laquelle le modèle peut se trouver.
            $namespaces = array(
                Web::getInstance()
                   ->get('app_namespace'),
                '\\EtdSolutions\\Framework'
            );

            $className = "";

            // On cherche le modèle dans ces espaces de nom.
            foreach ($namespaces as $namespace) {

                $className = $namespace . '\\Table\\' . $name . 'Table';

                // Si on a trouvé la classe, on arrête.
                if (class_exists($className)) {
                    break;
                }

            }
            // On vérifie que l'on a bien une classe valide.
            if (!class_exists($className)) {
                throw new \RuntimeException("Unable find table " . $name, 500);
            }

            self::$instances[$store] = new $className();
        }

        return self::$instances[$store];
    }

    /**
     * Renvoi les colonnes de la table dans la base de données.
     * Doit être définit manuellement dans chaque instance.
     *
     * @return  array Un tableau des champs disponibles dans la table.
     */
    abstract public function getFields();

    /**
     * @return string Le nom du table dans la base de données.
     */
    public function getTable() {

        return $this->table;
    }

    /**
     * @return string Le nom de la clé primaire dans la base de données.
     */
    public function getPk() {

        return $this->pk;
    }

    /**
     * Méthode pour charger une ligne dans la base de données à partir de la clé primaire et la relier
     * aux propriétés de l'instance Table.
     *
     * @param   mixed $pk La clé primaire avec laquelle charger la ligne, ou un tableau de champs à comparer avec la base de données.
     *
     * @return  boolean  True si succès, false si la ligne n'a pas été trouvée.
     *
     * @throws  \InvalidArgumentException
     */
    public function load($pk = null) {

        // Si aucune clé primaire n'est donné, on prend celle de l'instance.
        if (is_null($pk)) {
            $pk = $this->getProperty($this->pk);
        }

        // Si la clé primaire est vide, on ne charge rien.
        if (empty($pk)) {
            return false;
        }

        // On récupère la base de données.
        $db = Web::getInstance()
                 ->getDb();

        // On initialise la requête.
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($this->table);

        if (is_array($pk)) {
            foreach ($pk as $k => $v) {
                $query->where($db->quoteName($k) . " = " . $db->quote($v));
            }
        } else {
            $query->where($db->quoteName($this->pk) . " = " . $db->quote($pk));
        }

        $db->setQuery($query);

        // On charge la ligne.
        $row = $db->loadAssoc();

        // On contrôle que l'on a bien un résultat.
        if (empty($row)) {
            $this->addError(Text::_('APP_ERROR_TABLE_EMPTY_ROW'));

            return false;
        }

        // On relie la ligne avec le table.
        $this->bind($row);

        return true;
    }

    /**
     * Méthode pour faire des contrôle de sécurité sur les propriétés de l'instance Table
     * pour s'assurer qu'elles sont sûres avant leur stockage dans la base de données.
     *
     * @return  boolean  True si l'instance est saine et bonne à être stockée en base.
     */
    public function check() {

        return true;
    }

    /**
     * Méthod pour stocker une ligne dans la base de données avec les propriétés du Table.
     * Si la clé primaire est définit, la ligne avec cette clé primaire sera mise à jour.
     * S'il n'y a pas de clé primaire, une nouvelle ligne sera insérée et la clé primaire
     * du Table sera mise à jour.
     *
     * @param   boolean $updateNulls True pour mettre à jour les champs même s'ils sont null.
     *
     * @return  boolean  True en cas de succès.
     */
    public function store($updateNulls = false) {

        $db = Web::getInstance()
                 ->getDb();

        // Si une clé primaire existe on met à jour l'objet, sinon on l'insert.
        if ($this->hasPrimaryKey()) {
            $result = $db->updateObject($this->table, $this, $this->pk, $updateNulls);
        } else {
            $result = $db->insertObject($this->table, $this, $this->pk);
        }

        return $result;
    }

    /**
     * Méthode pour mettre à disposition un raccourci pour relier, contrôler et stocker une
     * instance Table dans le table de la base de données.
     *
     * @param   mixed $data Un tableau associatif ou un objet à relier à l'instance Table.
     *
     * @return  boolean  True en cas de succès.
     */
    public function save($data) {

        // On essaye de relier la source à l'instance.
        if (!$this->bind($data)) {
            return false;
        }

        // On lance les contrôles de securité sur l'instance et on vérifie que tout est bon avant le stockage en base.
        if (!$this->check()) {
            return false;
        }

        // On essaye de stocker les propriétés en base.
        if (!$this->store()) {
            return false;
        }

        // On nettoie les erreurs.
        $this->clearErrors();

        return true;
    }

    /**
     * Méthode pour supprimer une ligne de la base de données grâce à une clé primaire.
     *
     * @param   mixed $pk Une clé primaire à supprimer. Optionnelle : si non définit la valeur de l'instance sera utilisée.
     *
     * @return  boolean  True en cas de succès.
     *
     * @throws  \UnexpectedValueException
     */
    public function delete($pk = null) {

        if (is_null($pk)) {
            $pk = $this->getProperty($this->pk);
        }

        $db = Web::getInstance()
                 ->getDb();

        // On supprime la ligne.
        $query = $db->getQuery(true)
                    ->delete($this->table);
        $query->where($db->quoteName($this->pk) . ' = ' . $db->quote($pk));

        $db->setQuery($query);

        $db->execute();

        return true;
    }

    /**
     * Contrôle que la clé primaire a été définit.
     *
     * @return  boolean  True si la clé primaire est définit.
     */
    public function hasPrimaryKey() {

        $pk = $this->getProperty($this->pk);

        return !empty($pk);
    }

    /**
     * Méthode pour stocker une erreur.
     *
     * @param $error
     *
     * @return $this
     */
    public function addError($error) {

        array_push($this->errors, $error);

        return $this;
    }

    /**
     * Donne les erreurs survenues dans le table.
     *
     * @return array
     */
    public function getErrors() {

        return $this->errors;
    }

    /**
     * @return string La première erreur.
     */
    public function getError() {

        return $this->errors[0];
    }

    /**
     * Supprime toutes les erreurs.
     */
    public function clearErrors() {

        $this->errors = array();
    }

    /**
     * Méthode pour savoir si le table a des erreurs.
     *
     * @return bool True s'il y a des erreurs.
     */
    public function hasError() {

        return (count($this->errors) > 0);
    }

}