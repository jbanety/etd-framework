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

class HeadRenderer extends DocumentRenderer {

    public function render() {

        ob_start();
        echo $this->fetchHead($this->_doc);
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
    public function fetchHead($document) {

        $buffer = '<meta charset="utf-8">'."\n";
        $buffer .= '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'."\n";
        $buffer .= '<title>' . htmlspecialchars($document->getTitle(), ENT_COMPAT, 'UTF-8') . '</title>'."\n";

        // Don't add empty descriptions
        $documentDescription = $document->getDescription();
        if ($documentDescription) {
            $buffer .= '<meta name="description" content="' . htmlspecialchars($documentDescription) . '">'."\n";
        }

        $buffer .= '<meta name="viewport" content="width=device-width, initial-scale=1">'."\n";

        // Generate stylesheet links
        if (count($document->stylesheets['head'])) {
            foreach ($document->stylesheets['head'] as $src) {
                $buffer .= '<link rel="stylesheet" href="' . $src . '">'."\n";
            }
        }

        // Generate stylesheet declarations
        if (count($document->styles['head'])) {
            $buffer .= '<style>'."\n";
            foreach ($document->styles['head'] as $content) {
                $buffer .= $content . "\n";
            }
            $buffer .= '</style>' . "\n";
        }

        // Generate scripts
        if (count($document->scripts['head'])) {
            foreach ($document->scripts['head'] as $src) {
                $buffer .= '<script src="'.$src.'"></script>'."\n";
            }
        }

        // Generate script declarations
        if (count($document->js['head'])) {
            $buffer .= '<script>' . "\n";
            foreach ($document->js['head'] as $content) {
                $buffer .= $content . "\n";
            }
            $buffer .= '</script>' . "\n";
        }

        return $buffer;
    }

}