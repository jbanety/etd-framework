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

        ob_start();
        echo $this->fetchFoot($this->_doc);
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
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
                $buffer .= '<link rel="stylesheet" href="' . $src . '">'."\n";
            }
        }

        // Generate stylesheet declarations
        if (count($document->styles['foot'])) {
            $buffer .= '<style>'."\n";
            foreach ($document->styles['foot'] as $content) {
                $buffer .= $content . "\n";
            }
            $buffer .= '</style>' . "\n";
        }

        // Generate scripts
        if (count($document->scripts['foot'])) {
            foreach ($document->scripts['foot'] as $src) {
                $buffer .= '<script src="'.$src.'"></script>'."\n";
            }
        }

        // Generate script declarations
        if (count($document->js['foot'])) {
            $buffer .= '<script>' . "\n";
            foreach ($document->js['foot'] as $content) {
                $buffer .= $content . "\n";
            }
            $buffer .= '</script>' . "\n";
        }

        return $buffer;
    }

}