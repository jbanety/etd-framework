/*!
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

// -- begin dependencies



// -- end dependencies
// -- begin framework







// -- end framework
(function(window) {
    var re = {
        not_string: /[^s]/,
        number: /[def]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[\+\-]/
    }

    function sprintf() {
        var key = arguments[0], cache = sprintf.cache
        if (!(cache[key] && cache.hasOwnProperty(key))) {
            cache[key] = sprintf.parse(key)
        }
        return sprintf.format.call(null, cache[key], arguments)
    }

    sprintf.format = function(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, node_type = "", arg, output = [], i, k, match, pad, pad_character, pad_length, is_positive = true, sign = ""
        for (i = 0; i < tree_length; i++) {
            node_type = get_type(parse_tree[i])
            if (node_type === "string") {
                output[output.length] = parse_tree[i]
            }
            else if (node_type === "array") {
                match = parse_tree[i] // convenience purposes only
                if (match[2]) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < match[2].length; k++) {
                        if (!arg.hasOwnProperty(match[2][k])) {
                            throw new Error(sprintf("[sprintf] property '%s' does not exist", match[2][k]))
                        }
                        arg = arg[match[2][k]]
                    }
                }
                else if (match[1]) { // positional argument (explicit)
                    arg = argv[match[1]]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (get_type(arg) == "function") {
                    arg = arg()
                }

                if (re.not_string.test(match[8]) && (get_type(arg) != "number" && isNaN(arg))) {
                    throw new TypeError(sprintf("[sprintf] expecting number but found %s", get_type(arg)))
                }

                if (re.number.test(match[8])) {
                    is_positive = arg >= 0
                }

                switch (match[8]) {
                    case "b":
                        arg = arg.toString(2)
                    break
                    case "c":
                        arg = String.fromCharCode(arg)
                    break
                    case "d":
                        arg = parseInt(arg, 10)
                    break
                    case "e":
                        arg = match[7] ? arg.toExponential(match[7]) : arg.toExponential()
                    break
                    case "f":
                        arg = match[7] ? parseFloat(arg).toFixed(match[7]) : parseFloat(arg)
                    break
                    case "o":
                        arg = arg.toString(8)
                    break
                    case "s":
                        arg = ((arg = String(arg)) && match[7] ? arg.substring(0, match[7]) : arg)
                    break
                    case "u":
                        arg = arg >>> 0
                    break
                    case "x":
                        arg = arg.toString(16)
                    break
                    case "X":
                        arg = arg.toString(16).toUpperCase()
                    break
                }
                if (!is_positive || (re.number.test(match[8]) && match[3])) {
                    sign = is_positive ? "+" : "-"
                    arg = arg.toString().replace(re.sign, "")
                }
                pad_character = match[4] ? match[4] == "0" ? "0" : match[4].charAt(1) : " "
                pad_length = match[6] - (sign + arg).length
                pad = match[6] ? str_repeat(pad_character, pad_length) : ""
                output[output.length] = match[5] ? sign + arg + pad : (pad_character == 0 ? sign + pad + arg : pad + sign + arg)
            }
        }
        return output.join("")
    }

    sprintf.cache = {}

    sprintf.parse = function(fmt) {
        var _fmt = fmt, match = [], parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree[parse_tree.length] = match[0]
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree[parse_tree.length] = "%"
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list[field_list.length] = field_match[1]
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== "") {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list[field_list.length] = field_match[1]
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list[field_list.length] = field_match[1]
                            }
                            else {
                                throw new SyntaxError("[sprintf] failed to parse named argument key")
                            }
                        }
                    }
                    else {
                        throw new SyntaxError("[sprintf] failed to parse named argument key")
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error("[sprintf] mixing positional and named placeholders is not (yet) supported")
                }
                parse_tree[parse_tree.length] = match
            }
            else {
                throw new SyntaxError("[sprintf] unexpected placeholder")
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return parse_tree
    }

    var vsprintf = function(fmt, argv, _argv) {
        _argv = (argv || []).slice(0)
        _argv.splice(0, 0, fmt)
        return sprintf.apply(null, _argv)
    }

    /**
     * helpers
     */
    function get_type(variable) {
        return Object.prototype.toString.call(variable).slice(8, -1).toLowerCase()
    }

    function str_repeat(input, multiplier) {
        return Array(multiplier + 1).join(input)
    }

    /**
     * export to either browser or node.js
     */
    if (typeof exports !== "undefined") {
        exports.sprintf = sprintf
        exports.vsprintf = vsprintf
    }
    else {
        window.sprintf = sprintf
        window.vsprintf = vsprintf

        if (typeof define === "function" && define.amd) {
            define(function() {
                return {
                    sprintf: sprintf,
                    vsprintf: vsprintf
                }
            })
        }
    }
})(typeof window === "undefined" ? this : window)

if (typeof(EtdSolutions) === 'undefined') {
    var EtdSolutions = {};
}
/*
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

EtdSolutions.Framework = {
    version: '0.0.1'
};
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
        listView: null
    },

    init: function(form, options) {
        this.$form = $(form);
        $.extend(true, this.options, options);

        // On lie les événements
        this.$form.find('.btn-list').on('click', $.proxy(this.onListBtnClick, this));
        $('#limit').on('change', $.proxy(this.onLimitChange, this));

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
            if (self.$form.find('input[type=checkbox]:checked').length > 0) {
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
        this.$form.find('input[name=\"task\"]').val(task);
        this.$form.submit();
        return this;
    },

    validate: function() {
        return true;
    },

    onListBtnClick: function(e) {
        e.preventDefault();
        var target = $(e.delegateTarget), data = target.attr('href').split('/').splice(1);
        this.$form.attr('action', '/' + data[0]);
        this.$form.find('input[name=\"task\"]').val(data[1]);
        this.$form.find('.list-col-cb input').prop('checked', false);
        this.$form.find('input[type=\"checkbox\"][value=\"' + data[2] + '\"]').prop('checked', true);
        this.$form.submit();
    },

    onLimitChange: function(e) {
        e.preventDefault();
        this.$form.attr('action', '/' + this.options.listView);
        this.$form.submit();
    }

};
/*
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

EtdSolutions.Framework.Language = {};
/*
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

/**
 * Traduction JavaScript I18N pour l'application.
 */
EtdSolutions.Framework.Language.Text = {
    strings: {},

    '_': function(key) {
        return this.strings[key.toUpperCase()];
    },

    plural: function(string, n) {

        var key,
            found = false, // On essaye la clé sur les suffixes de pluriel potentiels.
            suffixes = this.getPluralSuffixes(n);

        suffixes.unshift(n);

        $.each(suffixes, function(i, suffix) {
            key = string + '_' + suffix;
            if (EtdSolutions.Framework.Language.Text.strings.hasOwnProperty(key)) {
                found = true;
                return false;
            }
            return true;
        });

        if (!found) {
            // Non trouvé, on revient à l'original.
            key = string;
        }

        key = this._(key);

        return sprintf.call(this, key, n);
    },

    sprintf: function() {
        if (arguments.length > 0) {
            arguments[0] = EtdSolutions.Framework.Language.Text._(arguments[0]);
            return sprintf.apply(this, arguments);
        }
        return '';
    },

    load: function(object) {
        for (var key in object) {
            if (object.hasOwnProperty(key)) {
                this.strings[key.toUpperCase()] = object[key];
            }
        }
        return this;
    },

    getPluralSuffixes: function(count) {

        var ret;

        if (count == 0) {
            ret = ['0'];
        } else if (count == 1) {
            ret = ['1'];
        } else {
            ret = ['MORE'];
        }

        return ret;
    }
};