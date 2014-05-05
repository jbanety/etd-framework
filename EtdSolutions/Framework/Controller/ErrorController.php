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

defined('_JEXEC') or die;

class ErrorController extends Controller {

    public function canDo($action = null, $section = null) {

        // On ne contrôle rien pour le controller des erreurs.
        return true;
    }

}