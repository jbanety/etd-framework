<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Session;

use Joomla\Filter\InputFilter;

defined('_JEXEC') or die;

/**
 * Custom session storage handler for PHP
 *
 * @see    http://www.php.net/manual/en/function.session-set-save-handler.php
 * @todo   When dropping compatibility with PHP 5.3 use the SessionHandlerInterface and the SessionHandler class
 * @since  1.0
 */
abstract class Storage extends \Joomla\Session\Storage {

    /**
     * @var    array  JSessionStorage instances container.
     * @since  1.0
     */
    protected static $instances = array();

    /**
     * Returns a session storage handler object, only creating it if it doesn't already exist.
     *
     * @param   string $name    The session store to instantiate
     * @param   array  $options Array of options
     *
     * @return  Storage
     *
     * @since   1.0
     */
    public static function getInstance($name = 'none', $options = array()) {

        $filter = new InputFilter;
        $name   = strtolower($filter->clean($name, 'word'));

        if (empty(self::$instances[$name])) {
            $class = '\\EtdSolutions\Framework\\Session\\Storage\\' . ucfirst($name);

            if (!class_exists($class)) {
                $path = __DIR__ . '/storage/' . $name . '.php';

                if (file_exists($path)) {
                    require_once $path;
                } else {
                    // No attempt to die gracefully here, as it tries to close the non-existing session
                    exit('Unable to load session storage class: ' . $name);
                }
            }

            self::$instances[$name] = new $class($options);
        }

        return self::$instances[$name];
    }
}
