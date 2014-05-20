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

class FootRenderer extends DocumentRenderer {

    public function render() {

        return $this->fetchFoot($this->_doc);

    }

    /**
     * Generates the head HTML and return the results as a string
     *
     * @param   Document $document The document for which the head will be created
     *
     * @return  string  The head hTML
     */
    public function fetchFoot($document) {

        $buffer = '';

        // Generate stylesheet links
        if (count($document->stylesheets['foot'])) {
            foreach ($document->stylesheets['foot'] as $src) {
                $buffer .= '<link rel="stylesheet" href="' . $src . '">' . "\n";
            }
        }

        // Generate stylesheet declarations
        if (count($document->styles['foot'])) {
            $buffer .= '<style>' . "\n";
            foreach ($document->styles['foot'] as $content) {
                $buffer .= $content . "\n";
            }
            $buffer .= '</style>' . "\n";
        }

        // Generate scripts
        if (count($document->scripts['foot'])) {
            foreach ($document->scripts['foot'] as $src) {
                $buffer .= '<script src="' . $src . '"></script>' . "\n";
            }
        }

        // Generate script declarations
        if (count($document->js['foot']) || count($document->domReadyJs)) {
            $buffer .= '<script>' . "\n";
            if (count($document->js['foot'])) {
                foreach ($document->js['foot'] as $content) {
                    $buffer .= $content . "\n";
                }
            }
            if (count($document->domReadyJs)) {
                $buffer .= "jQuery(document).ready(function() {\n";
                foreach ($document->domReadyJs as $content) {
                    $buffer .= $content . "\n";
                }
                $buffer .= "});\n";
            }
            $buffer .= '</script>' . "\n";
        }

        return $buffer;
    }

}