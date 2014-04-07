<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Controller;

use EtdSolutions\Framework\Application\Web;
use Joomla\Input\Input;
use Joomla\Application\AbstractApplication;
use Joomla\Controller\AbstractController;

defined('_JEXEC') or die;

/**
 * Controller de base
 */
abstract class Controller extends AbstractController {

    protected $defaultTask = 'display';

    protected $defaultView;

    protected $name;

    protected $task;

    protected $doTask;

    protected $tasks;

    /**
     * Instancie le controller.
     *
     * @param   Input               $input The input object.
     * @param   AbstractApplication $app   The application object.
     */
    public function __construct(Input $input = null, AbstractApplication $app = null) {

        if (!isset($app)) {
            $app = Web::getInstance();
        }

        if (!isset($input)) {
            $input = $app->input;
        }

        parent::__construct($input, $app);

        // On charge le fichier de langue pour le controller.
        $lang = $this->getApplication()->getLanguage();
        $lang->load($this->getName());

        // Le nom de la vue par défaut est pris sur celui du controller.
        $this->defaultView = $this->getName();

        // Tâches.
        $this->task  = '';
        $this->tasks = array();

        // Determine the methods to exclude from the base class.
        $xMethods = get_class_methods('EtdSolutions\Framework\Controller\Controller');

        // Get the public methods in this class using reflection.
        $r        = new \ReflectionClass($this);
        $rMethods = $r->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($rMethods as $rMethod) {
            $mName = $rMethod->getName();

            // Add default display method if not explicitly declared.
            if (!in_array($mName, $xMethods) || $mName == 'display') {
                $this->methods[] = strtolower($mName);

                // Auto register the methods as tasks.
                $this->taskMap[strtolower($mName)] = $mName;
            }
        }

        $this->registerDefaultTask($this->defaultTask);

    }

    /**
     * Method to execute the controller.
     *
     * @return  string
     *
     * @since   1.0
     * @throws  \LogicException
     * @throws  \RuntimeException
     */
    public function execute() {

        $this->task = $this->getInput()
                           ->get('task', $this->defaultTask);

        $task = strtolower($this->task);
        if (isset($this->taskMap[$task])) {
            $doTask = $this->taskMap[$task];
        } elseif (isset($this->taskMap['__default'])) {
            $doTask = $this->taskMap['__default'];
        } else {
            throw new \RuntimeException("Task not found !");
        }

        // Record the actual task being fired
        $this->doTask = $doTask;

        return $this->$doTask();

    }

    public function display($view = null) {

        // On définit la liste des espaces de noms dans laquelle la vue peut se trouver.
        $namespaces = array(
            '\\EtdSolutions\\Framework',
            $this->getApplication()->get('app_namespace')
        );

        $className = "";

        // On cherche la vue dans ces espaces de nom.
        foreach($namespaces as $namespace) {

            // On crée le nom de la classe.
            if (isset($view)) {
                $className = $namespace . '\\View\\' . ucfirst($view) . 'View';
            } elseif (isset($this->defaultView)) {
                $className = $namespace . '\\View\\' . ucfirst($this->defaultView) . 'View';
            } else {
                throw new \RuntimeException("Unable to find a view", 500);
            }

            // Si on a trouvé la classe, on arrête.
            if (class_exists($className)) {
                break;
            }

        }

        // On vérifie que l'on a bien une classe valide.
        if (!class_exists($className)) {
            throw new \RuntimeException("Unable to find a view", 500);
        }

        // On instancie la vue.
        $view = new $className();

        try {
            $result = $view->render();
        } catch (\Exception $e) {
            $this->getApplication()
                 ->raiseError($e->getMessage(), 404, $e);
        }

        // On retourne le rendu.
        return $result;

    }

    /**
     * Register the default task to perform if a mapping is not found.
     *
     * @param   string $method The name of the method in the derived class to perform if a named task is not found.
     *
     * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
     *
     * @since   12.2
     */
    public function registerDefaultTask($method) {

        $this->registerTask('__default', $method);

        return $this;
    }

    /**
     * Register (map) a task to a method in the class.
     *
     * @param   string $task   The task.
     * @param   string $method The name of the method in the derived class to perform for this task.
     *
     * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
     *
     * @since   12.2
     */
    public function registerTask($task, $method) {

        if (in_array(strtolower($method), $this->methods)) {
            $this->taskMap[strtolower($task)] = $method;
        }

        return $this;
    }

    /**
     * Unregister (unmap) a task in the class.
     *
     * @param   string $task The task.
     *
     * @return  JControllerLegacy  This object to support chaining.
     *
     * @since   12.2
     */
    public function unregisterTask($task) {

        unset($this->taskMap[strtolower($task)]);

        return $this;
    }

    /**
     * Redirects the browser or returns false if no redirect is set.
     */
    public function redirect($url, $msg = null, $type = null) {

        $this->getApplication()
             ->redirect($url, $msg, $type);

        return true;
    }

    /**
     * @return Web
     */
    public function getApplication() {

        return parent::getApplication();
    }

    /**
     * Méthode pour récupérer le nom du controller.
     *
     * @return  string  Le nom du controller.
     *
     * @throws  \RuntimeException
     */
    public function getName() {

        if (empty($this->name)) {
            $r = null;
            $classname = join('', array_slice(explode('\\', get_class($this)), -1));
            if (!preg_match('/(.*)Controller/i', $classname, $r)) {
                throw new \RuntimeException('Unable to detect controller name', 500);
            }
            $this->name = strtolower($r[1]);
        }

        return $this->name;
    }

}
