<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Progression;

use EtdSolutions\Framework\Application\Web;
use Joomla\Data\DataObject;
use Joomla\Filesystem\Path;
use Joomla\Language\Text;
use Joomla\Registry\Registry;

/**
 * Le but de cette classe est de fournir du feedback au client.
 * Elle permet de communiquer sur le déroulement d'un process long.
 *
 * @property int status Le statut du processus.
 */
final class Datastore extends DataObject {

    /**
     * @var string Identifiant de la session.
     */
    protected $session_id;

    /**
     * @var string Le chemin vers le fichier d'échange.
     */
    protected $filepath;

    /**
     * @var int Le timestamp de la dernière modification sur le fichier d'échange.
     */
    protected $lastModification = 0;

    /**
     * @var bool Flag pour empêcher la lecture du fichier.
     */
    protected $blockRead = false;

    public function __construct() {

        // Identifiant de la session.
        $this->session_id = Web::getInstance()->getSession()->getId();

        // Fichier d'échange.
        $this->filepath = Path::clean(JPATH_TMP . "/" . $this->session_id . ".json");

        // On charge les données du fichier.
        $this->read();
    }

    /**
     * @return string Le chemin vers le fichier.
     */
    public function getFilePath() {

        return $this->filepath;
    }

    /**
     * Méthode pour supprimer le fichier d'échange.
     *
     * @return bool
     */
    public function deleteFile() {

        // On vérifie que le fichier existe et qu'il est accessible en lecture.
        if (file_exists($this->filepath) && is_writable($this->filepath)) {
            if (unlink($this->filepath)) {
                $this->blockRead = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Donne une représentation JSON des propriétés.
     *
     * @return string Les propriétés de l'instance au format JSON.
     */
    public function toJSON() {

        // On bloque la lecture.
        $this->blockRead = true;

        // On dump en JSON.
        $json = json_encode($this->jsonSerialize());

        // On débloque la lecture.
        $this->blockRead = false;

        return $json;
    }

    /**
     * Donne la valeur d'un propriété.
     *
     * @param string $property Le nom de la propriété.
     *
     * @return mixed La valeur
     */
    protected function getProperty($property) {

        // On lie les changements dans le fichier.
        $this->read();

        return parent::getProperty($property);
    }

    protected function setProperty($property, $value) {

        // On définit la valeur.
        $ret = parent::setProperty($property, $value);

        // On écrit les changements dans le fichier d'échange.
        $this->write();

        return $ret;
    }

    /**
     * Méthode pour lire le fichier d'échange.
     */
    protected function read() {

        // On vérifie que le fichier existe et qu'il est accessible en lecture.
        if (!$this->blockRead && file_exists($this->filepath) && is_readable($this->filepath)) {

            // On nettoie le cache de stat.
            clearstatcache(true, $this->filepath);

            // On récupère la dernière date de modification.
            $lastModification = filemtime($this->filepath);

            // Si le contenu a été modifié, on lit le fichier.
            if ($lastModification > $this->lastModification) {

                $this->lastModification = $lastModification;

                // On lie les données.
                $content = file_get_contents($this->filepath);
                $reg = new Registry($content);
                $this->bind($reg->toArray());
            }
        }
        return $this;
    }

    /**
     * Méthode pour écrire le fichier d'échange.
     *
     * @return $this
     *
     * @throws \RuntimeException En cas d'erreur avec le fichier.
     */
    protected function write() {

        // On écrit dans le fichier avec un verrou exclusif.
        if (!file_put_contents($this->filepath, $this->toJSON(), LOCK_EX)) {
            throw new \RuntimeException(Text::_('Unable to write to file at ' . $this->filepath));
        }
        return $this;
    }
}