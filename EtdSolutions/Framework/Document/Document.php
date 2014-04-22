<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Document;

use EtdSolutions\Framework\Document\Renderer\DocumentRenderer;

defined('_JEXEC') or die;

class Document {

    /**
     * @var string Titre du document
     */
    public $title = '';

    /**
     * @var string Description
     */
    public $description = '';

    /**
     * @var array Tableau contenant le contenu des positions du template.
     */
    public static $content = array();

    /**
     * @var array Scripts
     */
    public $scripts = array();

    /**
     * @var array Feuilles de styles
     */
    public $stylesheets = array();

    public $styles = array();

    public $js = array();

    /**
     * @var string String qui contient le template
     */
    protected $template = '';

    protected $positions = array();

    private static $instance;

    /**
     * Retourne une référence à l'objet global Document, en le créant seulement si besoin.
     *
     * @return  Document
     */
    public static function getInstance() {

        if (empty(self::$instance)) {
            self::$instance = new Document;
        }

        return self::$instance;
    }

    /**
     * Sets the title of the document
     *
     * @param   string $title The title to be set
     *
     * @return  Document instance of $this to allow chaining
     */
    public function setTitle($title) {

        $this->title = $title;

        return $this;
    }

    /**
     * Return the title of the document.
     *
     * @return  string
     */
    public function getTitle() {

        return $this->title;
    }

    /**
     * Sets the description of the document
     *
     * @param   string $description The description to set
     *
     * @return  Document instance of $this to allow chaining
     */
    public function setDescription($description) {

        $this->description = $description;

        return $this;
    }

    /**
     * Return the description of the page.
     *
     * @return  string
     */
    public function getDescription() {

        return $this->description;
    }

    public function addScript($url, $position = "foot") {

        if (!in_array($url, $this->scripts[$position])) {
            $this->scripts[$position]  = $url;
        }

        return $this;
    }

    public function addJS($script, $position = "foot") {

        if (!in_array($script, $this->js[$position])) {
            $this->js[$position]  = $script;
        }

        return $this;
    }

    public function addStylesheet($url, $position = "foot") {

        if (!in_array($url, $this->stylesheets[$position])) {
            $this->stylesheets[$position]  = $url;
        }

        return $this;
    }

    public function addCSS($css, $position = "foot") {

        if (!in_array($css, $this->styles[$position])) {
            $this->styles[$position]  = $css;
        }

        return $this;
    }

    public function getPositionContent($position = null) {

        if ($position === null) {
            return self::$content;
        }

        if (isset(self::$content[$position])) {
            return self::$content[$position];
        }

        // On instancie le renderer.
        $renderer = $this->getRenderer($position);

        $this->setPositionContent($position, $renderer->render());

        return self::$content[$position];

    }

    public function setPositionContent($position, $content) {

        self::$content[$position] = $content;

        return $this;
    }

    public function parse() {

        return $this->fetchTemplate()
                    ->parseTemplate();
    }

    public function render() {

        if (!empty($this->_template)) {
            $data = $this->renderTemplate();
        } else {
            $this->parse();
            $data = $this->renderTemplate();
        }

        return $data;
    }

    /**
     * @return DocumentRenderer
     *
     * @throws  \RuntimeException
     */
    protected function getRenderer($position) {

        $class = '\\EtdSolutions\\Framework\\\Document\\Renderer\\' . ucfirst($position) . 'Renderer';

        if (!class_exists($class)) {
            throw new \RuntimeException('Unable to load renderer class', 500);
        }

        $instance = new $class($this);

        return $instance;
    }

    protected function fetchTemplate() {

        $contents = '';

        $file = JPATH_THEMES . '/template.php';

        // Check to see if we have a valid template file
        if (file_exists($file)) {

            // Get the file content
            ob_start();
            require $file;
            $contents = ob_get_contents();
            ob_end_clean();

        }

        $this->template = $contents;

        return $this;
    }

    protected function parseTemplate() {

        $matches = array();

        if (preg_match_all('#<!--\[([^\]]+)\]-->#iU', $this->template, $matches)) {
            $positions = array();

            // Step through the positions in reverse order.
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $positions[$matches[1][$i]] = $matches[0][$i];
            }

            $this->positions = $positions;
        }

        return $this;
    }

    protected function renderTemplate() {

        $replace = array();
        $with    = array();

        foreach ($this->positions as $position => $tag) {
            $replace[] = $tag;
            $with[]    = $this->getPositionContent($position);
        }

        return str_replace($replace, $with, $this->template);
    }

}