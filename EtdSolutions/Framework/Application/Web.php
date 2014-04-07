<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Application;

use EtdSolutions\Framework\Controller\ErrorController;
use Joomla\Application\AbstractWebApplication;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseDriver;
use Joomla\Language\Language;
use Joomla\Language\Text;
use Joomla\Router\RestRouter;
use Joomla\Registry\Registry;
use Joomla\Input\Input;
use Joomla\Application\Web\WebClient;
use EtdSolutions\Framework\Session\Session;
use Joomla\Router\Router;
use Joomla\Uri\Uri;
use Joomla\String\String;

defined('_JEXEC') or die;

final class Web extends AbstractWebApplication {

    /**
     * @var RestRouter  Le router de l'application.
     */
    public $router;

    /**
     * @var Language La langue de l'application.
     */
    protected $language;

    /**
     * @var array Liste des messages devant être affichés à l'utilisateur.
     */
    protected $_messageQueue = array();

    /**
     * @var DatabaseDriver Le gestionnaire de la base de données.
     */
    protected $db;

    /**
     * @var array Dernière erreur.
     */
    protected $error;

    /**
     * @var  Web  L'instance de l'application.
     */
    private static $instance;

    public function __construct(Input $input = null, Registry $config = null, WebClient $client = null) {

        // On charge la configuration.
        $config = new Registry(new \JConfig());

        parent::__construct($input, $config, $client);

        // On initialise la langue.
        $this->getLanguage();
    }

    /**
     * Retourne une référence à l'objet global Web, en le créant seulement si besoin.
     *
     * @return  Web
     */
    public static function getInstance() {

        if (empty(self::$instance)) {
            self::$instance = new Web;
        }

        return self::$instance;
    }

    /**
     * Renvoi le gestionnaire de base de données, en le créant s'il n'est pas initialisé.
     *
     * @return DatabaseDriver Le gestionnaire de base de données.
     */
    public function getDb() {

        if (!isset($this->db)) {
            // On initialise la base de données.
            $dbFactory = new DatabaseFactory();

            $this->db = $dbFactory->getDriver($this->get('database.driver'), array(
                'host'     => $this->get('database.host'),
                'user'     => $this->get('database.user'),
                'password' => $this->get('database.password'),
                'port'     => $this->get('database.port'),
                'socket'   => $this->get('database.socket'),
                'database' => $this->get('database.name'),
                'prefix'   => $this->get('database.prefix'),
            ));

            // Debug ?
            if (JDEBUG) {
                $this->db->setDebug(true);
            }

            //$this->db->connect();
        }

        return $this->db;

    }

    /**
     * Renvoi l'objet de langue.
     *
     * @return  Language
     *
     * @note    JPATH_ROOT doit être définit.
     */
    public function getLanguage() {

        if (is_null($this->language)) {

            // On récupère l'objet Language avec le tag de langue.
            // On charge aussi le fichier de langue /xx-XX/xx-XX.ini et les fonctions de localisation /xx-XX/xx-XX.localise.php si dispo.
            $language = Language::getInstance($this->get('language'), $this->get('debug_language', false));

            // On configure Text pour utiliser notre instance de Language.
            Text::setLanguage($language);

            $this->language = $language;
        }

        return $this->language;
    }

    /**
     * Contrôle un jeton de formulaire dans la requête.
     *
     * A utiliser avec getFormToken.
     *
     * @param   string $method La méthode de la requête dans la laquelle on doit trouver le jeton.
     *
     * @return  boolean  True si trouvé et valide, false sinon.
     */
    public function checkToken($method = 'post') {

        $token = $this->getFormToken();

        if (!$this->input->$method->get($token, '', 'alnum')) {
            if ($this->getSession()
                     ->isNew()
            ) {
                // On redirige vers la page de login.
                echo '{"message":"Votre session est expirée."}';
                $this->close(401);
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Redirige le navigateur vers une nouvelle adresse.
     *
     * @param string $url     La nouvelle URL
     * @param string $msg     Message a afficher à l'utilisateur
     * @param string $msgType Type du message
     * @param bool   $moved   Redirection 301 pour indiquer une page qui a changé d'emplacement (SEF)
     */
    public function redirect($url, $msg = '', $msgType = 'message', $moved = false) {

        // Check for relative internal links.
        if (preg_match('#^index\.php#', $url)) {
            $url = $this->get('uri.base.full') . $url;
        }

        // Perform a basic sanity check to make sure we don't have any CRLF garbage.
        $url = preg_split("/[\r\n]/", $url);
        $url = $url[0];

        /*
         * Here we need to check and see if the URL is relative or absolute.  Essentially, do we need to
         * prepend the URL with our base URL for a proper redirect.  The rudimentary way we are looking
         * at this is to simply check whether or not the URL string has a valid scheme or not.
         */
        if (!preg_match('#^[a-z]+\://#i', $url)) {
            // Get a JURI instance for the requested URI.
            $uri = new Uri($this->get('uri.request'));

            // Get a base URL to prepend from the requested URI.
            $prefix = $uri->toString(array(
                                         'scheme',
                                         'user',
                                         'pass',
                                         'host',
                                         'port'
                                     ));

            // We just need the prefix since we have a path relative to the root.
            if ($url[0] == '/') {
                $url = $prefix . $url;
            } else // It's relative to where we are now, so lets add that.
            {
                $parts = explode('/', $uri->toString(array('path')));
                array_pop($parts);
                $path = implode('/', $parts) . '/';
                $url  = $prefix . $path . $url;
            }
        }

        // If the message exists, enqueue it.
        if (trim($msg)) {
            $this->enqueueMessage($msg, $msgType);
        }

        // Persist messages if they exist.
        if (count($this->_messageQueue)) {
            $session = $this->getSession();
            $session->set('application.queue', $this->_messageQueue);
        }

        // If the headers have already been sent we need to send the redirect statement via JavaScript.
        if ($this->checkHeadersSent()) {
            echo "<script>document.location.href='$url';</script>\n";
        } else {
            // We have to use a JavaScript redirect here because MSIE doesn't play nice with utf-8 URLs.
            if (($this->client->engine == WebClient::TRIDENT) && !String::is_ascii($url)) {
                $html = '<html><head>';
                $html .= '<meta http-equiv="content-type" content="text/html; charset=' . $this->charSet . '" />';
                $html .= '<script>document.location.href=\'' . $url . '\';</script>';
                $html .= '</head><body></body></html>';

                echo $html;
            } else {
                // All other cases use the more efficient HTTP header for redirection.
                $this->header($moved ? 'HTTP/1.1 301 Moved Permanently' : 'HTTP/1.1 303 See other');
                $this->header('Location: ' . $url);
                $this->header('Content-Type: text/html; charset=' . $this->charSet);
            }
        }

        // Close the application after the redirect.
        $this->close();
    }

    public function enqueueMessage($msg, $type = 'info') {

        if (!count($this->_messageQueue)) {
            $session      = $this->getSession();
            $sessionQueue = $session->get('application.queue');

            if (count($sessionQueue)) {
                $this->_messageQueue = $sessionQueue;
                $session->set('application.queue', null);
            }
        }

        // Enqueue the message.
        $type = strtolower($type);
        switch ($type) {
            case 'warning':
                $icon = 'exclamation-circle';
                break;
            case 'danger':
            case 'error':
                $type = 'danger';
                $icon = 'times-circle';
                break;
            case 'success':
                $icon = 'check-circle';
                break;
            case 'info':
            default:
                $type = 'info';
                $icon = 'info-circle';
                break;
        }
        $this->_messageQueue[] = array(
            'message' => $msg,
            'type'    => $type,
            'icon'    => $icon
        );

        return $this;
    }

    /**
     * Get the system message queue.
     *
     * @return  array  The system message queue.
     */
    public function getMessageQueue() {

        // For empty queue, if messages exists in the session, enqueue them.
        if (!count($this->_messageQueue)) {
            $session      = $this->getSession();
            $sessionQueue = $session->get('application.queue');

            if (count($sessionQueue)) {
                $this->_messageQueue = $sessionQueue;
                $session->set('application.queue', null);
            }
        }

        return $this->_messageQueue;
    }

    /**
     * Méthode pour définit une erreur dans l'application.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $exception
     */
    public function setError($message, $code, $exception = null) {

        $trace = null;
        $extra = "";

        if (JDEBUG) {
            if (isset($exception)) {
                $trace = $exception->getTrace();
                $extra = str_replace(JPATH_ROOT, "", $exception->getFile()) . ":" . $exception->getLine();
            } else {
                $trace = array_slice(debug_backtrace(), 2);
            }
        }

        $this->error = array(
            'message'   => $message,
            'code'      => $code,
            'backtrace' => $trace,
            'extra'     => $extra
        );
    }

    /**
     * Retourne la dernière erreur enregistrée.
     *
     * @return array    L'erreur
     */
    public function getError() {

        return $this->error;
    }

    /**
     * Méthode pour déclencher une erreur.
     * On arrête le flux et on affiche la page d'erreur.
     *
     * @param string     $message   Message d'erreur à afficher.
     * @param int        $code      Code de l'erreur.
     * @param \Exception $exception L'exception déclenchée si disponible.
     */
    public function raiseError($message, $code = 500, $exception = null) {

        $this->clearHeaders();
        switch ($code) {
            case 401:
                $status = '401 Unauthorized';
                break;
            case 403:
                $status = '403 Forbidden';
                break;
            case 404:
                $status = '404 Not found';
                break;
            case 500:
                $status = '500 Internal Server Error';
                break;
            default:
                $status = '200 OK';
                break;
        }
        $this->setHeader('status', $status);
        $this->setError($message, $code, $exception);
        $controller = new ErrorController();
        $this->setBody($controller->execute());
        $this->respond();
        $this->close();
    }

    /**
     * Initialise l'application.
     *
     * C'est ici qu'on instancie le routeur de l'application les routes correspondantes vers les controllers.
     */
    protected function initialise() {

        // On instancie la session.
        $this->setSession(Session::getInstance('Database', array(
            'db' => $this->getDb()
        )));

        // On initialise la session.
        $this->getSession()
             ->initialise($this->input);
        $this->getSession()
             ->start();

        // On crée la session dans la base de données.
        $this->createDbSession();

        // On instancie le routeur.
        $this->router = new Router();
        $this->router->setDefaultController($this->get('default_controller'));

        // On définit les routes.
        $this->router->addMaps($this->get('routes', array()));

    }

    /**
     * Effectue la logique de l'application.
     */
    protected function doExecute() {

        // On récupère le controller.
        $controller = $this->route();

        // On exécute la logique du controller.
        try {
            $result = $controller->execute();
        } catch (\Exception $e) {
            $this->raiseError($e->getMessage(), $e->getCode(), $e);
        }

        $this->render($result);

    }

    /**
     * Route l'application.
     *
     * Le routage est le processus pendant lequel on examine la requête pour déterminer
     * quel controller doit recevoir la requête.
     *
     * @param  string $route La route a analyser. (Optionnel, REQUEST_URI par défaut)
     *
     * @return \EtdSolutions\Framework\Controller\Controller Le controller
     */
    protected function route($route = null) {

        if (!isset($route)) {
            $route = $_SERVER['REQUEST_URI'];
        }

        try {
            // On détermine le controller grâce au router.
            $controller = $this->router->getController($route);
        } catch (\Exception $e) {
            $this->raiseError($e->getMessage(), $e->getCode(), $e);
        }

        return $controller;

    }

    /**
     * Effectue le rendu de l'application.
     *
     * Le rendu est le résultat du traitement du résultat du contrôleur.
     * Si c'est une chaine de caractère on assume que c'est de l'HTML et donc on renvoi
     * du text/html. Dans le cas contraire, on transforme le résultat en chaine de
     * caractère au format JSON.
     *
     * On modifie aussi ici les headers de la réponse pour l'adapter au résultat.
     *
     */
    protected function render($result = null) {

        // C'est un string => HTML
        if (is_string($result)) {

            // On modifie le type MIME de la réponse.
            $this->mimeType = 'text/html';

        } else {

            // Si c'est un tableau et que l'on a un code de statut HTTP.
            if (is_array($result) && array_key_exists('status', $result)) {
                switch ($result['status']) {
                    case 401:
                        $status = '401 Unauthorized';
                        break;
                    case 403:
                        $status = '403 Forbidden';
                        break;
                    case 404:
                        $status = '404 Not found';
                        break;
                    case 500:
                        $status = '500 Internal Server Error';
                        break;
                    default:
                        $status = '200 OK';
                        break;
                }
                $this->setHeader('status', $status);
                unset($result['status']);
            }

            $result = $result['html'];

        }

        // On affecte le résultat au corps de la réponse.
        $this->setBody($result);

    }

    /**
     * Checks the user session.
     *
     * If the session record doesn't exist, initialise it.
     * If session is new, create session variables
     *
     * @return  void
     *
     * @since   11.1
     */
    protected function createDbSession() {

        $db      = $this->db;
        $session = $this->getSession();

        try {

            $query = $db->getQuery(true)
                        ->select($db->quoteName('session_id'))
                        ->from($db->quoteName('#__session'))
                        ->where($db->quoteName('session_id') . ' = ' . $db->quote($session->getId()));

            $db->setQuery($query, 0, 1);
            $exists = $db->loadResult();

            // If the session record doesn't exist initialise it.
            if (!$exists) {
                $query->clear();
                if ($session->isNew()) {
                    $query->insert($db->quoteName('#__session'))
                          ->columns($db->quoteName('session_id') . ', ' . $db->quoteName('time'))
                          ->values($db->quote($session->getId()) . ', ' . $db->quote((int)time()));
                    $db->setQuery($query);
                } else {
                    $query->insert($db->quoteName('#__session'))
                          ->columns($db->quoteName('session_id') . ', ' . $db->quoteName('time'))
                          ->values($db->quote($session->getId()) . ', ' . $db->quote((int)$session->get('session.timer.start')));
                    $db->setQuery($query);

                    $db->setQuery($query);
                }

                // If the insert failed, exit the application.
                $db->execute();
            }

        } catch (\RuntimeException $e) {
            exit($e->getMessage());
        }
    }

}