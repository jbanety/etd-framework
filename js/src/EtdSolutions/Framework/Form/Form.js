/*
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

EtdSolutions.Framework.Form = {

    $form: null,

    options: {
        ajaxURI: null,
        token: null,
        data: {},
        itemView: null,
        listView: null,
        selectors: {
            checkboxes: '.list-col-cb input',
            itemButtons: '.btn-list',
            limitInput: '#limit',
            checkAll: 'input[name="checkAll"]',
            taskInput: 'input[name="task"]'
        }
    },

    init: function(form, options) {
        this.$form = $(form);
        $.extend(true, this.options, options);

        // On lie les événements
        this.$form.find(this.options.selectors.itemButtons).on('click', $.proxy(this.onListBtnClick, this));
        $(this.options.selectors.limitInput).on('change', $.proxy(this.onLimitChange, this));
        this.$form.find(this.options.selectors.checkAll).on('change', $.proxy(this.onCheckAllChange, this));

        return this;
    },

    prepareEditRow: function(row) {

        row = $(row);

        var self = this, input = row.find('.inline-edit-input');

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
                'task': 'updateField',
                'name': input.attr('name'),
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

        return this;

    },

    postAjax: function(data, sucCallback, errCallback) {

        data = $.extend(this.options.data, data);
        data[this.options.token] = '1';

        $.post(this.options.ajaxURI, data, function(data) {
            if (data.error && errCallback) {
                errCallback(data);
            } else if (sucCallback) {
                sucCallback(data);
            }
        });

        return this;
    },

    setAjaxURI: function(uri) {
        this.options.ajaxURI = uri;
        return this;
    },

    setAdditionnalData: function(data) {
        this.options.data = data;
        return this;
    },

    addSelectTask: function(btnId, task) {
        var self = this;
        $('#' + btnId).on('click', function(e) {
            e.preventDefault();
            if (self.$form.find(this.options.selectors.checkboxes + ':checked').length > 0) {
                self.submitTask(task, self.options.itemView);
            } else {
                alert(EtdSolutions.Framework.Language.Text._('APP_ERROR_NO_ITEM_SELECTED'));
            }
        });
        return this;
    },

    addSubmitTask: function(btnId, task, action) {
        var self = this;
        $('#' + btnId).on('click', function(e) {
            e.preventDefault();
            self.submitTask(task, action);
        });
        return this;
    },

    addValidationTask: function(btnId, task, action) {
        var self = this;
        $('#' + btnId).on('click', function(e) {
            e.preventDefault();
            if (self.validate()) {
                self.submitTask(task, action);
            } else {
                // raise error
            }
        });
        return this;
    },

    submitTask: function(task, action) {
        if (action) {
            this.$form.attr('action', '/' + action);
        }
        this.$form.find(this.options.selectors.taskInput).val(task);
        this.$form.submit();
        return this;
    },

    validate: function() {
        return true;
    },

    onListBtnClick: function(e) {
        e.preventDefault();
        var target = $(e.delegateTarget), data = target.attr('href').split('/').splice(1);
        this.$form.find(this.options.selectors.checkboxes).prop('checked', false);
        this.$form.find(this.options.selectors.checkboxes + '[value="' + data.pop() + '"]').prop('checked', true);
        this.$form.find(this.options.selectors.taskInput).val(data.pop());
        this.$form.attr('action', '/' + data.join("/"));
        this.$form.submit();
        return this;
    },

    onLimitChange: function(e) {
        e.preventDefault();
        this.$form.attr('action', '/' + this.options.listView);
        this.$form.submit();
        return this;
    },

    onCheckAllChange: function(e) {
        this.$form.find(this.options.selectors.checkboxes).prop('checked', $(this.options.selectors.checkAll).prop('checked'));
        return this;
    }

};