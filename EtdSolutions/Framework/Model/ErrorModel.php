<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etdglobalone.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Model;

use EtdSolutions\Framework\Application\Web;
use Joomla\Model\AbstractModel;

defined('_JEXEC') or die;

class ErrorModel extends AbstractModel {

    protected $error;

    /**
     * @return array Un tableau correspondant à l'erreur.
     */
    public function getError() {

        if (!isset($this->error)) {
            $this->error = Web::getInstance()->getError();
        }

        return $this->error;
    }

}