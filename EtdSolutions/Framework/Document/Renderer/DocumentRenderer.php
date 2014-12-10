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
use Joomla\Filesystem\Path;

defined('_JEXEC') or die;

class DocumentRenderer {

    /**
     * Reference to the Document object that instantiated the renderer
     *
     * @var    Document
     */
    protected $_doc = null;

    protected $paths = null;

    /**
     * The renderer layout.
     *
     * @var    string
     */
    protected $layout = '';

    /**
     * @var $name string Le nom du renderer.
     */
    protected $name;

    /**
     * Class constructor
     *
     * @param   Document $doc A reference to the Document object that instantiated the renderer
     */
    public function __construct(Document $doc) {

        // Document
        $this->_doc = $doc;

        // Le layout est fixé au nom du renderer.
        $this->layout = strtolower($this->getName());

        // Chemins de recherche du layout.
        $paths = new \SplPriorityQueue;
        $paths->insert(JPATH_THEME . '/html/renderers', 1);
        $this->paths = $paths;

    }

    /**
     * Renders a script and returns the results as a string
     *
     * @return  string  The output of the script
     */
    public function render() {

        // Get the layout path.
        $path = $this->getPath($this->getLayout());

        // Check if the layout path was found.
        if (!$path) {
            throw new \RuntimeException('Renderer Layout Path Not Found : ' . $this->getLayout(), 404);
        }

        // Start an output buffer.
        ob_start();

        // Load the layout.
        include $path;

        // Get the layout contents.
        $output = ob_get_clean();

        return $output;

    }

    /**
     * Méthode pour récupérer le nom du renderer.
     *
     * @return  string  Le nom du renderer.
     *
     * @throws  \RuntimeException
     */
    public function getName() {

        if (empty($this->name)) {
            $r = null;
            $classname = join('', array_slice(explode('\\', get_class($this)), -1));
            if (!preg_match('/(.*)Renderer/i', $classname, $r)) {
                throw new \RuntimeException('Unable to detect renderer name', 500);
            }
            $this->name = $r[1];
        }

        return $this->name;
    }

    /**
     * Method to get the layout path.
     *
     * @param   string $layout The base name of the layout file (excluding extension).
     * @param   string $ext The extension of the layout file (default: "php").
     *
     * @return  mixed  The layout file name if found, false otherwise.
     */
    public function getPath($layout, $ext = 'php') {

        // Get the layout file name.
        $file = Path::clean($layout . '.' . $ext);

        // Find the layout file path.
        $path = Path::find(clone($this->paths), $file);

        return $path;
    }

    /**
     * Method to get the renderer layout.
     *
     * @return  string  The layout name.
     */
    public function getLayout() {
        return $this->layout;
    }
}