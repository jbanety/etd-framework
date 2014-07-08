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

use EtdSolutions\Framework\Application\Web;
use Joomla\Language\Text;

defined('_JEXEC') or die;

class MessageRenderer extends DocumentRenderer {

    public function render() {

        // Initialise variables.
        $buffer = '';
        $lists  = array();

        // Get the message queue
        $messages = Web::getInstance()
                       ->getMessageQueue();

        // Build the sorted message list
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $msg) {
                if (isset($msg['type']) && isset($msg['message'])) {
                    $lists[$msg['type']][] = $msg;
                }
            }
        }

        if (!empty($lists)) {
            $buffer .= '<ul class="alerts-list">';
            foreach ($lists as $type => $messages) {
                $buffer .= '<li>';
                $buffer .= '<div class="alert alert-' . $type . ' alert-dismissable" role="alert">';
                $buffer .= '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">' . Text::_('APP_GLOBAL_CLOSE') . '</span></button>';
                foreach ($messages as $i => $message) {
                    if ($i > 0) {
                        $buffer .= "<br>";
                    }
                    $buffer .= $message['message'];
                }
                $buffer .= '</div>';
                $buffer .= '</li>';
            }
            $buffer .= '</ul>';
        }

        return $buffer;
    }

}