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
            taskInput: 'input[name="task"]',
            orderingBtn: 'th .list-ordering',
            orderingInput: 'input[name="list_ordering"]',
            directionInput: 'input[name="list_direction"]',
            sortableContainer: 'table',
            sortableHandle: '.sortable-handle.active'
        }
    },

    ordering: {
        activeOrdering: '',
        activeDirection: ''
    },

    init: function(form, options) {
        this.$form = $(form);
        $.extend(true, this.options, options);

        // On initialise les infos de tri.
        this.ordering.activeOrdering = this.$form.find(this.options.selectors.orderingInput).val();
        this.ordering.activeDirection = this.$form.find(this.options.selectors.directionInput).val();

        this.bind().makeSortable();

        return this;
    },

    bind: function() {

        this.$form.find(this.options.selectors.itemButtons).on('click', $.proxy(this.onListBtnClick, this));
        $(this.options.selectors.limitInput).on('change', $.proxy(this.onLimitChange, this));
        this.$form.find(this.options.selectors.checkAll).on('change', $.proxy(this.onCheckAllChange, this));
        this.$form.find(this.options.selectors.orderingBtn).on('click', $.proxy(this.onOrderingColumnClick, this));

        return this;
    },

    makeSortable: function() {

        if (this.$form.hasClass('form-sortable')) {
            this.$form.find(this.options.selectors.sortableContainer).tableDnD({
                onDragClass: 'dragging',
                dragHandle: this.options.selectors.sortableHandle,
                onDragStart: $.proxy(this.onSortableDragStart, this),
                onDrop: $.proxy(this.onSortableDrop, this)
            });
        }

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
            if (self.$form.find(self.options.selectors.checkboxes + ':checked').length > 0) {
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
                //@TODO: raise error
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

    onCheckAllChange: function() {
        this.$form.find(this.options.selectors.checkboxes).prop('checked', $(this.options.selectors.checkAll).prop('checked'));
        return this;
    },

    onOrderingColumnClick: function(e) {
        e.preventDefault();

        // Tri à définir
        var newOrdering = $(e.delegateTarget).data('order'), newDirection = $(e.delegateTarget).data('direction');

        if (this.ordering.activeDirection != newDirection || this.ordering.activeOrdering != newOrdering) {
            this.ordering.activeOrdering = newOrdering;
            this.ordering.activeDirection = newDirection;
            this.$form.find(this.options.selectors.orderingInput).val(newOrdering);
            this.$form.find(this.options.selectors.directionInput).val(newDirection);
            this.$form.attr('action', '/' + this.options.listView);
            this.$form.submit();
        }

        return this;
    },

    onSortUpdate: function() {

        this.$form.find(this.options.selectors.taskInput).val('saveOrder');
        /*f.find('input[name="order[]"]').each(function(index) {
         $(this).val(index);
         });
         var data = f.serializeArray();
         f.find('input[name="cid[]"]').each(function() {
         data.push({
         name: 'cid[]',
         value: $(this).val()
         });
         });*/
        //console.log(data);
        //$.post('/projets/saveOrderAjax', data);
    },

    onSortableDragStart: function(table, handle) {

        var $tr = $(handle).parents('tr');

        // On désactive les autres groupes.
        $(table).find('tbody > tr[data-sortable-group-id != ' + $tr.data('sortable-group-id') + ']').addClass('nodrop');

    },

    onSortableDrop: function(table, row, changed) {

        // On déplace les enfants de l'élément.
        var $tr = $(row);
        var prevItemChildrenNodes = $(table).find('tbody > tr[data-parents*=" ' + $tr.data('item-id') + '"]');
        if (prevItemChildrenNodes.length) {
            $tr.after(prevItemChildrenNodes);
        }

        // On réactive les groupes.
        $(table).find('tbody > tr').removeClass('nodrop');

        // On définit le groupe à ordonner.
        var sortableRange = $('tr[data-sortable-group-id = ' + $tr.data('sortable-group-id') + ']'), count = sortableRange.length;

        if (count > 1) {

            var ids = [];
            var order = [];

            sortableRange.find(this.options.selectors.checkboxes).each(function(i) {
                ids.push($(this).val());
                order.push(i);
            });

            // On construit le tableau de données à envoyer au serveur.
            var data = {
                task: 'saveOrder',
                ids: ids,
                order: order
            };

            // On ajoute le jeton.
            data[this.options.token] = '1';

            $.post(this.options.ajaxURI, data).done(function(data) {
                console.log('done', data);
            }).fail(function(data) {
                console.log('fail', data);
            }).always(function(data) {
                console.log('always', data);
            });

        }

    }

};