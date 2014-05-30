/*
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

EtdSolutions.Framework.Form = {

    options: {
        form: null,
        ajaxURI: null,
        token: null,
        data: {}
    },

    init: function(options) {
        $.extend(true, this.options, options);
    },

    prepareEditRow: function(row) {

        row = $(row);

        var self = this,
            input = row.find('.inline-edit-input');

        row.find('.inline-edit-btn').on('click', function(e) {
            e.preventDefault();
            console.log(input.val());
            input.data('prev-value', input.val());
            row.addClass('editing');
        });

        row.find('.inline-cancel-btn').on('click', function(e) {
            e.preventDefault();
            input.val(input.data('prev-value'));
            row.removeClass('editing');
        });

        row.find('.inline-save-btn').on('click', function(e) {
            e.preventDefault();

            var data = {
                'task' : 'updateField',
                'name' : input.attr('name'),
                'value': input.val()
            };

            self.postAjax(data, function(data) {
                row.removeClass('editing');
            }, function(data) {
                //raise error
                input.val(input.data('prev-value'));
                row.removeClass('editing');
            });

        });

    },

    postAjax: function(data, sucCallback, errCallback) {

        data = $.extend(this.options.data, data);
        data[this.options.token] = '1';

        $.post(this.options.ajaxURI, data, function(data) {
            if (data.error && errCallback) {
                errCallback(data);
            } else if(sucCallback) {
                sucCallback(data);
            }
        });
    },

    setAjaxURI: function(uri) {
        this.options.ajaxURI = uri;
    },

    setAdditionnalData: function(data) {
        this.options.data = data;
    }

};