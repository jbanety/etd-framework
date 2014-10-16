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

/**
 * Base commune.
 */
class Base {

    /**
     * Constantes d'état du processus.
     */
    const ABORTED = -1;
    const IDLE = 0;
    const RUNNING = 1;
    const FINISHED = 2;

    /**
     * @var Datastore Le gestionnaire des données.
     */
    protected $datastore;

    /**
     * Constructeur.
     */
    public function __construct() {

        // Gestionnaire de données pour le processus.
        $this->datastore = new Datastore();
    }

    public function start() {

        $this->set('status', self::RUNNING);
        return $this;
    }

    public function finish() {

        $this->set('status', self::FINISHED);
        return $this;
    }

    public function abort() {

        $this->set('status', self::ABORTED);
    }

    public function isAborted() {

        return ($this->get('status') == self::ABORTED);
    }

    public function isFinished() {

        return ($this->get('status') == self::FINISHED);
    }

    public function isRunning() {

        return ($this->get('status') == self::RUNNING);
    }

    public function getData() {

        return $this->datastore->dump();
    }

    public function deleteFile() {

        return $this->datastore->deleteFile();
    }

    /**
     * Méthode pour définir une propriété du processus.
     *
     * @param string $name Le nom de la propriété
     * @param mixed $value La valeur.
     *
     * @return Base $this
     */
    public function set($name, $value) {

        $this->datastore->$name = $value;
        return $this;
    }

    /**
     * Méthode pour récupérer une valeur.
     *
     * @param string $name Le nom de la propriété.
     *
     * @return mixed
     */
    public function get($name) {

        return $this->datastore->$name;
    }
}