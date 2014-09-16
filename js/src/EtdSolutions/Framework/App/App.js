/*
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

EtdSolutions.Framework.App = {

    clearMessages: function() {
        $('#message-container').empty();
    },

    renderMessages: function(messages) {

        var $container = $('#message-container');
        this.clearMessages();

        var $list = $('<ul class="alerts-list"></ul>');

        $.each(messages, function(type, msgs) {

            var html = '<li><div class="alert alert-' + type + ' alert-dismissable" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">' + EtdSolutions.Framework.Language.Text._('APP_GLOBAL_CLOSE') + '</span></button>';

            $.each(msgs, function(index, msg) {
                if (index > 0) {
                    html += '<br>';
                }
                html += msg;
            });

            html += '</div></li>';

            $list.append($(html));
        });

        $container.append($list);

    },

    renderMessage: function(type, message) {
        var messages = {};
        messages[type] = [message];
        this.renderMessages(messages);
    },

    raiseError: function(error) {
        this.renderMessage('error', error);
    },

    raiseWarning: function(warning) {
        this.renderMessage('warning', warning);
    },

    raiseSuccess: function(success) {
        this.renderMessage('success', success);
    },

    raiseInfo: function(info) {
        this.renderMessage('info', info);
    }

};