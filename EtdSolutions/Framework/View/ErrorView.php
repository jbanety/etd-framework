<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etdglobalone.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\View;

defined('_JEXEC') or die;

class ErrorView extends HtmlView {

    protected $error;

    public function beforeRender() {

        $this->error = $this->model->getError();
    }

}
