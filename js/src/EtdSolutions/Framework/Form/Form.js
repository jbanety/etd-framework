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
    $filtersContainer: null,
    $filtersInputs: null,
    $modal: null,

    options: {
        ajaxURI: null,
        token: null,
        data: {},
        itemView: null,
        listView: null,
        modalTemplate: '<div class="modal fade" id="form-modal"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">' + EtdSolutions.Framework.Language.Text._('APP_GLOBAL_CLOSE') + '</span></button><h4 class="modal-title"></h4></div><div class="modal-body"></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">' + EtdSolutions.Framework.Language.Text._('APP_GLOBAL_CLOSE') + '</button><button type="button" class="btn btn-primary">' + EtdSolutions.Framework.Language.Text._('APP_GLOBAL_OK') + '</button></div></div></div></div>',
        modal: {
            backdrop: true,
            keyboard: true,
            show: false,
            remote: false
        },
        selectors: {
            checkboxes: '.list-col-cb input',
            itemButtons: '.btn-list:not(.btn-list-custom)',
            limitInput: '#limit',
            checkAll: 'input[name="checkAll"]',
            taskInput: 'input[name="task"]',
            orderingBtn: 'th .list-ordering',
            orderingInput: 'input[name="list_ordering"]',
            directionInput: 'input[name="list_direction"]',
            sortableContainer: 'table',
            sortableHandle: '.sortable-handle.active',
            filterInputs: 'select, input[type="checkbox"], input[type="radio"]'
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

    addToolbarFilters: function(filtersContainer) {

        this.$filtersContainer = $(filtersContainer);
        this.$filtersInputs = this.$filtersContainer.find(this.options.selectors.filterInputs);
        this.$filtersInputs.on('change', $.proxy(this.onFilterChange, this));
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

    addHiddenField: function(name, value) {

        var field = this.$form.find('input[name="' + name + '"]');

        if (field.length) {
            field.attr('type', 'hidden');
        } else {
            field = $('<input type="hidden" name="' + name + '" value="">');
            this.$form.append(field);
        }

        field.val(value);

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

    selectCheckboxes: function(cbs) {

        var self = this;

        // On s'assure d'avoir un tableau.
        if ($.type(cbs) != "array") {
            cbs = [cbs];
        }

        // On décoche toutes les checkboxes.
        this.$form.find(this.options.selectors.checkboxes).prop('checked', false);

        // On coche celles qui nous intéressent.
        $.each(cbs, function() {
            self.$form.find(self.options.selectors.checkboxes + '[value="' + this + '"]').prop('checked', true);
        });

        return this;

    },

    validate: function() {
        return true;
    },

    prepareModal: function(title, body, reset, options) {

        reset = reset || false;
        options = $.extend({}, this.options.modal, options);

        // On supprime la modal existante si nécessaire.
        if (reset && this.$modal && this.$modal.length) {
            this.$modal.remove();
        }

        // On crée la nouvelle modal si nécessaire.
        if (!this.$modal) {
            this.$modal = $(this.options.modalTemplate);
            $(document.body).append(this.$modal);
        }

        this.$modal.find('.modal-title').html(title);
        this.$modal.find('.modal-body').html(body);

        this.$modal.modal(options);

        return this;

    },

    doModal: function(method) {
        if (this.$modal) {
            this.$modal.modal(method);
        }
        return this;
    },

    showModal: function() {
        return this.doModal('show');
    },

    hideModal: function() {
        return this.doModal('hide');
    },

    onListBtnClick: function(e) {
        e.preventDefault();
        var target = $(e.delegateTarget), data = target.attr('href').split('/').splice(1);

        if (target.hasClass('btn-list-modal')) {

            var remoteURI = this.options.ajaxURI + '/' + data[1] + '/' + data[2];
            if (data.length == 4) {
                remoteURI += '/' + data[3];
            }

            this.prepareModal('', '', true, {
               remote: remoteURI
            });

            this.showModal();

            return this;

        }

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

    },

    onFilterChange: function(event) {

        // On supprime tous les champs "filters" déjà présents dans le formulaire.
        this.$form.find('[name^="filter"]').remove();

        // On ajoute les champs au formulaire.
        this.$filtersInputs.each($.proxy(function(index, element) {
            var $element = $(element);
            var $input = $('<input type="hidden" name="' + $element.attr('name') + '" value="' + $element.val() + '">');
            this.$form.append($input);
        }, this));

        this.$form.attr('action', '/' + this.options.listView);
        this.$form.submit();

    }

};