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

use EtdSolutions\Framework\Session\Storage;

defined('_JEXEC') or die;

/**
 * Class for managing HTTP sessions
 *
 * Provides access to session-state values as well as session-level
 * settings and lifetime management methods.
 * Based on the standard PHP session handling mechanism it provides
 * more advanced features such as expire timeouts.
 *
 * @since  1.0
 */
class Session extends \Joomla\Session\Session {

    /**
     * Session instances container.
     *
     * @var    Session
     * @since  1.0
     */
    protected static $instance;

    /**
     * Constructor
     *
     * @param   string $store   The type of storage for the session.
     * @param   array  $options Optional parameters
     *
     * @since   1.0
     */
    public function __construct($store = 'none', array $options = array()) {

        // Need to destroy any existing sessions started with session.auto_start
        if (session_id()) {
            session_unset();
            session_destroy();
        }

        // Disable transparent sid support
        ini_set('session.use_trans_sid', '0');

        // Only allow the session ID to come from cookies and nothing else.
        ini_set('session.use_only_cookies', '1');

        // Create handler
        $this->store = Storage::getInstance($store, $options);

        $this->storeName = $store;

        // Set options
        $this->_setOptions($options);

        $this->_setCookieParams();

        $this->state = 'inactive';
    }

    /**
     * Returns the global Session object, only creating it
     * if it doesn't already exist.
     *
     * @param   string $handler The type of session handler.
     * @param   array  $options An array of configuration options (for new sessions only).
     *
     * @return  Session  The Session object.
     *
     * @since   1.0
     */
    public static function getInstance($handler, array $options = array()) {

        if (!is_object(self::$instance)) {
            self::$instance = new self($handler, $options);
        }

        return self::$instance;
    }
}
