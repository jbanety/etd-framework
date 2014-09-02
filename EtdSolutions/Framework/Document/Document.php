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

use EtdSolutions\Framework\Application\Web;
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

    /**
     * @var array Styles en ligne.
     */
    public $styles = array();

    /**
     * @var array Scripts JS en ligne.
     */
    public $js = array();

    /**
     * @var array Scripts JS en ligne exécutés dans le contexte jQuery.
     */
    public $domReadyJs = array();

    /**
     * @var string String qui contient le template
     */
    protected $template = '';

    /**
     * @var array Tableau des buffers du rendu des positions.
     */
    protected $positions = array();

    /**
     * @var Document L'instance du document.
     */
    private static $instance;

    /**
     * Constructeur.
     */
    function __construct() {

        $this->scripts['head'] = array();
        $this->scripts['foot'] = array();

        $this->js['head'] = array();
        $this->js['foot'] = array();

        $this->styles['head'] = array();
        $this->styles['foot'] = array();

        $this->stylesheets['head'] = array();
        $this->stylesheets['foot'] = array();

    }

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
     * Définit le titre du document.
     *
     * @param   string $title Le titre
     *
     * @return  Document Cette instance $this pour le chaining.
     */
    public function setTitle($title) {

        $this->title = $title;

        return $this;
    }

    /**
     * Retourne le titre du document.
     *
     * @return  string
     */
    public function getTitle() {

        return $this->title;
    }

    /**
     * Définit la description du document
     *
     * @param   string $description La description
     *
     * @return  Document Cette instance $this pour le chaining.
     */
    public function setDescription($description) {

        $this->description = $description;

        return $this;
    }

    /**
     * Retourne la description du document.
     *
     * @return  string
     */
    public function getDescription() {

        return $this->description;
    }

    /**
     * Ajoute un fichier JavaScript à charger.
     *
     * @param string $url      L'URI du fichier à charger.
     * @param string $position La position de la ligne dans le document. (head = avant la balise </head>; foot = avant la balise </body>)
     * @param bool   $onTop    Place la ligne en haut de la pile.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function addScript($url, $position = "foot", $onTop = false) {

        if (!in_array($url, $this->scripts[$position])) {
            if ($onTop) {
                array_unshift($this->scripts[$position], $url);
            } else {
                array_push($this->scripts[$position], $url);
            }
        }

        return $this;
    }

    /**
     * Ajoute du script JavaScript en ligne.
     *
     * @param string $script   Le script JS à ajouter.
     * @param string $position La position du script dans le document. (head = avant la balise </head>; foot = avant la balise </body>)
     * @param bool   $onTop    Place le script en haut de la pile.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function addJS($script, $position = "foot", $onTop = false) {

        if (!in_array($script, $this->js[$position])) {
            if ($onTop) {
                array_unshift($this->js[$position], $script);
            } else {
                array_push($this->js[$position], $script);
            }
        }

        return $this;
    }

    /**
     * Ajoute du script JavaScript en ligne exécuté dans le contexte jQuery.
     * Il sera exécuté après que le DOM du document soit prêt.
     *
     * @param string $script Le script JS à ajouter.
     * @param bool   $onTop  Place le script en haut de la pile.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function addDomReadyJS($script, $onTop = false) {

        if (!in_array($script, $this->domReadyJs)) {
            if ($onTop) {
                array_unshift($this->domReadyJs, $script);
            } else {
                array_push($this->domReadyJs, $script);
            }
        }

        return $this;
    }

    /**
     * Ajoute une feuille de styles à charger.
     *
     * @param string $url      L'URI du fichier à charger.
     * @param string $position La position de la ligne dans le document. (head = avant la balise </head>; foot = avant la balise </body>)
     * @param bool   $onTop    Place la ligne en haut de la pile.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function addStylesheet($url, $position = "head", $onTop = false) {

        if (!in_array($url, $this->stylesheets[$position])) {
            if ($onTop) {
                array_unshift($this->stylesheets[$position], $url);
            } else {
                array_push($this->stylesheets[$position], $url);
            }
        }

        return $this;
    }

    /**
     * Ajoute du CSS en ligne.
     *
     * @param string $css      Le CSS à ajouter.
     * @param string $position La position du CSS dans le document. (head = avant la balise </head>; foot = avant la balise </body>)
     * @param bool   $onTop    Place le script en haut de la pile.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function addCSS($css, $position = "head", $onTop = false) {

        if (!in_array($css, $this->styles[$position])) {
            if ($onTop) {
                array_unshift($this->styles, $css);
            } else {
                array_push($this->styles, $css);
            }
        }

        return $this;
    }

    /**
     * Méthode pour récupérer le contenu rendu pour une position.
     *
     * Cette méthode effectue le rendu de la position si celui-ci
     * n'a pas déjà été fait.
     *
     * @param string $position Le nom de la position.
     *
     * @return array|string Le contenu de la position ou le tableau des positions si $position = null.
     */
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

    /**
     * Méthode pour définir le contenu d'une position.
     *
     * @param string $position Le nom de la position.
     * @param string $content  Le contenu.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function setPositionContent($position, $content) {

        self::$content[$position] = $content;

        return $this;
    }

    /**
     * Méthode pour récupérer et parser le template.
     *
     * @return Document Cette instance $this pour le chaining.
     */
    public function parse() {

        return $this->fetchTemplate()
                    ->parseTemplate();
    }

    /**
     * Méthode pour effectuer le rendu du template.
     *
     * @return string Le rendu du template.
     */
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
     * Méthode pour charger le renderer correspondant à la position.
     *
     * @param $position string Le nom de la position
     *
     * @return DocumentRenderer
     *
     * @throws  \RuntimeException
     */
    protected function getRenderer($position) {

        // On définit la liste des espaces de noms dans laquelle le renderer peut se trouver.
        $namespaces = array(
            '\\EtdSolutions\\Framework',
            Web::getInstance()
               ->get('app_namespace')
        );

        $className = "";

        // On cherche la vue dans ces espaces de nom.
        foreach ($namespaces as $namespace) {

            // On crée le nom de la classe.
            $className = $namespace . '\\Document\\Renderer\\' . ucfirst($position) . 'Renderer';

            // Si on a trouvé la classe, on arrête.
            if (class_exists($className)) {
                break;
            }

        }

        // On vérifie que l'on a bien une classe valide.
        if (!class_exists($className)) {
            throw new \RuntimeException('Unable to load renderer class ' . ucfirst($position) . 'Renderer', 500);
        }

        // On instancie le renderer.
        $instance = new $className($this);

        return $instance;
    }

    /**
     * Méthode pour charger le template HTML depuis le thème de l'application.
     *
     * @return Document Cette instance $this pour le chaining.
     * @throws \RuntimeException si le fichier est introuvable.
     */
    protected function fetchTemplate() {

        $contents = '';

        $file = JPATH_THEME . '/template.php';

        // Check to see if we have a valid template file
        if (file_exists($file)) {

            // Get the file content
            ob_start();
            require $file;
            $contents = ob_get_contents();
            ob_end_clean();

        } else {
            throw new \RuntimeException('Unable to find template', 500);
        }

        $this->template = $contents;

        return $this;
    }

    /**
     * Méthode pour analyser le fichier HTML et détecter les positions.
     * Les positions sont sous forme de commentaires HTML (e.g. <!--[etd:navigation]-->)
     *
     * @return Document Cette instance $this pour le chaining.
     */
    protected function parseTemplate() {

        $matches = array();

        if (preg_match_all('#<!--\[etd:([^\]]+)\]-->#iU', $this->template, $matches)) {
            $positions = array();

            // On parcourt les positions dans l'ordre inverse.
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $positions[$matches[1][$i]] = $matches[0][$i];
            }

            // On prend la position "foot" si elle existe et on la met en dernier.
            if (array_key_exists('foot', $positions)) {
                $foot = $positions['foot'];
                unset($positions['foot']);
                $positions['foot'] = $foot;
            }

            $this->positions = $positions;
        }

        return $this;
    }

    /**
     * Méthode pour effectuer le rendu du template.
     * Tout d'abord, on récupère pour chaque position son contenu,
     * ensuite on remplace leur balise respective par celui-ci.
     *
     * @return string Le rendu du template.
     */
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