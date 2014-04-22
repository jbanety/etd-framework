<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Document\Renderer;

use EtdSolutions\Framework\Document\Document;

defined('_JEXEC') or die;

class DocumentRenderer {

    /**
     * Reference to the Document object that instantiated the renderer
     *
     * @var    Document
     */
    protected $_doc = null;

    /**
     * Class constructor
     *
     * @param   Document $doc A reference to the Document object that instantiated the renderer
     */
    public function __construct(Document $doc) {

        $this->_doc = $doc;
    }

    /**
     * Renders a script and returns the results as a string
     *
     * @return  string  The output of the script
     */
    public function render() {

    }
}