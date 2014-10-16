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
 * Serveur du processus long.
 */
class Server extends Base {

    public function __construct($max = 100) {

        parent::__construct();

        // On définit quelques propriétés pour le process.
        $this->set('max', $max);
        $this->set('progress', 0);
        $this->set('status', self::IDLE);
    }
}