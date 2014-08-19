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

use Joomla\Application\AbstractDaemonApplication;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;
use Joomla\Input\Cli;
use Joomla\Language\Language;
use Joomla\Language\Text;
use Joomla\Registry\Registry;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

defined('_JEXEC') or die;

abstract class Daemon extends AbstractDaemonApplication {

    /**
     * @var  Daemon  L'instance de l'application.
     */
    private static $instance;

    /**
     * @var DatabaseDriver Le gestionnaire de la base de données.
     */
    protected $db;

    /**
     * @var \Memcached Gestionnaire memcached.
     */
    protected $memcached;

    /**
     * @var Language La langue de l'application.
     */
    protected $language;

    /**
     * Constructeur
     *
     * @param Cli $input
     * @param Registry $config
     */
    public function __construct(Cli $input = null, Registry $config = null) {

        // On charge la configuration.
        $config = new Registry(new \JConfig());

        parent::__construct($input, $config);

    }

    /**
     * Initialise l'application.
     */
    protected function initialise() {

        // On définit le fuseau horaire.
        @date_default_timezone_set($this->get('timezone', 'Europe/Paris'));

        // On initialise la langue.
        $this->getLanguage();

        // On instancie le logger.
        $logger = new Logger($this->get('application_name'));
        $logger->pushHandler(new StreamHandler(JPATH_LOGS, Logger::INFO));
        $this->setLogger($logger);

        // PID
        $this->set('application_pid_file', JPATH_TMP . "/" . $this->get('application_pid_file'));

        // On instancie le gestionnaire memcached.
        $this->memcached = new \Memcached($this->get('memcached.persistent_id'));

        // On ajoute le serveur.
        $this->memcached->addServer($this->get('memcached.host'), $this->get('memcached.port'));

    }

    /**
     * Méthode pour récupérer une instance d'une application, la créant si besoin.
     *
     * @param   string $name            Le nom de l'application
     *
     * @return  Daemon  L'instance.
     *
     * @throws   \RuntimeException
     */
    public static function getInstance($name) {

        $name = ucfirst($name);
        $store = md5($name);

        if (empty(self::$instance[$store])) {

            // On définit la liste des espaces de noms dans laquelle le modèle peut se trouver.
            $namespaces = array(
                self::get('app_namespace'),
                '\\EtdSolutions\\Framework'
            );

            $className = "";

            // On cherche l'application dans ces espaces de nom.
            foreach ($namespaces as $namespace) {

                $className = $namespace . '\\Application\\' . $name . 'Application';

                // Si on a trouvé la classe, on arrête.
                if (class_exists($className)) {
                    break;
                }

            }
            // On vérifie que l'on a bien une classe valide.
            if (!class_exists($className)) {
                throw new \RuntimeException("Unable find application " . $name, 500);
            }

            self::$instance[$store] = new $className;
        }


        return self::$instance[$store];
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
     * Change l'identité du processus.
     *
     * @return  boolean  True si l'identité a été changée avec succès.
     *
     * @see     posix_setuid()
     */
    protected function changeIdentity() {
        // Get the group and user ids to set for the daemon.
        $uid = (int)$this->config->get('application_uid', 0);
        $gid = (int)$this->config->get('application_gid', 0);

        // Get the application process id file path.
        $file = $this->config->get('application_pid_file');

        // Change the user id for the process id file if necessary.
        if ($uid && (fileowner($file) != $uid) && (!@ chown($file, $uid))) {
            $this->getLogger()->error(Text::_('APP_DAEMON_ERROR_ID_FILE_USER_OWNERSHIP'));

            return false;
        }

        // Change the group id for the process id file if necessary.
        if ($gid && (filegroup($file) != $gid) && (!@ chgrp($file, $gid))) {
            $this->getLogger()->error(Text::_('APP_DAEMON_ERROR_ID_FILE_GROUP_OWNERSHIP'));

            return false;
        }

        // Set the correct home directory for the process.
        if ($uid && ($info = posix_getpwuid($uid)) && is_dir($info['dir'])) {
            system('export HOME="' . $info['dir'] . '"');
        }

        // Change the group id for the process necessary.
        if ($gid && (posix_getgid() != $gid) && (!@ posix_setgid($gid))) {
            $this->getLogger()->error(Text::_('APP_DAEMON_ERROR_ID_PROCESS_GROUP_OWNERSHIP'));
            return false;
        }

        // Change the user id for the process necessary.
        if ($uid && (posix_getuid() != $uid) && (!@ posix_setuid($uid))) {
            $this->getLogger()->error(Text::_('APP_DAEMON_ERROR_ID_PROCESS_USER_OWNERSHIP'));
            return false;
        }


        // Get the user and group information based on uid and gid.
        $user = posix_getpwuid($uid);
        $group = posix_getgrgid($gid);

        $this->getLogger()->info(Text::sprintf('APP_DAEMON_ID_SUCCESS', $user['name'], $group['name']));

        return true;
    }

    /**
     * Méthode pour éteindre le daemon et optionnellement le redémarrer.
     *
     * @param   boolean  $restart  True pour redémarrer le daemon en sortie.
     *
     * @return  void
     */
    protected function shutdown($restart = false) {

        if (isset($this->db)) {
            $this->getDb()->disconnect();
        }

        if (isset($this->memcached)) {
            $this->memcached->quit();
        }

        parent::shutdown($restart);

    }


}