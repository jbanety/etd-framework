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

/**
 * TableDnD plug-in for JQuery, allows you to drag and drop table rows
 * You can set up various options to control how the system will work
 * Copyright (c) Denis Howlett <denish@isocra.com>
 * Licensed like jQuery, see http://docs.jquery.com/License.
 *
 * Configuration options:
 *
 * onDragStyle
 *     This is the style that is assigned to the row during drag. There are limitations to the styles that can be
 *     associated with a row (such as you can't assign a border--well you can, but it won't be
 *     displayed). (So instead consider using onDragClass.) The CSS style to apply is specified as
 *     a map (as used in the jQuery css(...) function).
 * onDropStyle
 *     This is the style that is assigned to the row when it is dropped. As for onDragStyle, there are limitations
 *     to what you can do. Also this replaces the original style, so again consider using onDragClass which
 *     is simply added and then removed on drop.
 * onDragClass
 *     This class is added for the duration of the drag and then removed when the row is dropped. It is more
 *     flexible than using onDragStyle since it can be inherited by the row cells and other content. The default
 *     is class is tDnD_whileDrag. So to use the default, simply customise this CSS class in your
 *     stylesheet.
 * onDrop
 *     Pass a function that will be called when the row is dropped. The function takes 2 parameters: the table
 *     and the row that was dropped. You can work out the new order of the rows by using
 *     table.rows.
 * onDragStart
 *     Pass a function that will be called when the user starts dragging. The function takes 2 parameters: the
 *     table and the row which the user has started to drag.
 * onAllowDrop
 *     Pass a function that will be called as a row is over another row. If the function returns true, allow
 *     dropping on that row, otherwise not. The function takes 2 parameters: the dragged row and the row under
 *     the cursor. It returns a boolean: true allows the drop, false doesn't allow it.
 * scrollAmount
 *     This is the number of pixels to scroll if the user moves the mouse cursor to the top or bottom of the
 *     window. The page should automatically scroll up or down as appropriate (tested in IE6, IE7, Safari, FF2,
 *     FF3 beta
 * dragHandle
 *     This is a jQuery mach string for one or more cells in each row that is draggable. If you
 *     specify this, then you are responsible for setting cursor: move in the CSS and only these cells
 *     will have the drag behaviour. If you do not specify a dragHandle, then you get the old behaviour where
 *     the whole row is draggable.
 *
 * Other ways to control behaviour:
 *
 * Add class="nodrop" to any rows for which you don't want to allow dropping, and class="nodrag" to any rows
 * that you don't want to be draggable.
 *
 * Inside the onDrop method you can also call $.tableDnD.serialize() this returns a string of the form
 * <tableID>[]=<rowID1>&<tableID>[]=<rowID2> so that you can send this back to the server. The table must have
 * an ID as must all the rows.
 *
 * Other methods:
 *
 * $("...").tableDnDUpdate()
 * Will update all the matching tables, that is it will reapply the mousedown method to the rows (or handle cells).
 * This is useful if you have updated the table rows using Ajax and you want to make the table draggable again.
 * The table maintains the original configuration (so you don't have to specify it again).
 *
 * $("...").tableDnDSerialize()
 * Will serialize and return the serialized string as above, but for each of the matching tables--so it can be
 * called from anywhere and isn't dependent on the currentTable being set up correctly before calling
 *
 * Known problems:
 * - Auto-scoll has some problems with IE7  (it scrolls even when it shouldn't), work-around: set scrollAmount to 0
 *
 * Version 0.2: 2008-02-20 First public version
 * Version 0.3: 2008-02-07 Added onDragStart option
 *                         Made the scroll amount configurable (default is 5 as before)
 * Version 0.4: 2008-03-15 Changed the noDrag/noDrop attributes to nodrag/nodrop classes
 *                         Added onAllowDrop to control dropping
 *                         Fixed a bug which meant that you couldn't set the scroll amount in both directions
 *                         Added serialize method
 * Version 0.5: 2008-05-16 Changed so that if you specify a dragHandle class it doesn't make the whole row
 *                         draggable
 *                         Improved the serialize method to use a default (and settable) regular expression.
 *                         Added tableDnDupate() and tableDnDSerialize() to be called when you are outside the table
 * Version 0.6: 2011-12-02 Added support for touch devices
 * Version 0.7  2012-04-09 Now works with jQuery 1.7 and supports touch, tidied up tabs and spaces
 */
!function ($, window, document, undefined) {
// Determine if this is a touch device
    var hasTouch   = 'ontouchstart' in document.documentElement,
        startEvent = hasTouch ? 'touchstart' : 'mousedown',
        moveEvent  = hasTouch ? 'touchmove'  : 'mousemove',
        endEvent   = hasTouch ? 'touchend'   : 'mouseup';

// If we're on a touch device, then wire up the events
// see http://stackoverflow.com/a/8456194/1316086
    hasTouch
    && $.each("touchstart touchmove touchend".split(" "), function(i, name) {
        $.event.fixHooks[name] = $.event.mouseHooks;
    });


    $(document).ready(function () {
        function parseStyle(css) {
            var objMap = {},
                parts = css.match(/([^;:]+)/g) || [];
            while (parts.length)
                objMap[parts.shift()] = parts.shift().trim();

            return objMap;
        }
        $('table').each(function () {
            if ($(this).data('table') == 'dnd') {

                $(this).tableDnD({
                    onDragStyle: $(this).data('ondragstyle') && parseStyle($(this).data('ondragstyle')) || null,
                    onDropStyle: $(this).data('ondropstyle') && parseStyle($(this).data('ondropstyle')) || null,
                    onDragClass: $(this).data('ondragclass') == undefined && "tDnD_whileDrag" || $(this).data('ondragclass'),
                    onDrop: $(this).data('ondrop') && new Function('table', 'row', $(this).data('ondrop')), // 'return eval("'+$(this).data('ondrop')+'");') || null,
                    onDragStart: $(this).data('ondragstart') && new Function('table', 'row' ,$(this).data('ondragstart')), // 'return eval("'+$(this).data('ondragstart')+'");') || null,
                    scrollAmount: $(this).data('scrollamount') || 5,
                    sensitivity: $(this).data('sensitivity') || 10,
                    hierarchyLevel: $(this).data('hierarchylevel') || 0,
                    indentArtifact: $(this).data('indentartifact') || '<div class="indent">&nbsp;</div>',
                    autoWidthAdjust: $(this).data('autowidthadjust') || true,
                    autoCleanRelations: $(this).data('autocleanrelations') || true,
                    jsonPretifySeparator: $(this).data('jsonpretifyseparator') || '\t',
                    serializeRegexp: $(this).data('serializeregexp') && new RegExp($(this).data('serializeregexp')) || /[^\-]*$/,
                    serializeParamName: $(this).data('serializeparamname') || false,
                    dragHandle: $(this).data('draghandle') || null
                });
            }


        });
    });

    window.jQuery.tableDnD = {
        /** Keep hold of the current table being dragged */
        currentTable: null,
        /** Keep hold of the current drag object if any */
        dragObject: null,
        /** The current mouse offset */
        mouseOffset: null,
        /** Remember the old value of X and Y so that we don't do too much processing */
        oldX: 0,
        oldY: 0,

        /** Actually build the structure */
        build: function(options) {
            // Set up the defaults if any

            this.each(function() {
                // This is bound to each matching table, set up the defaults and override with user options
                this.tableDnDConfig = $.extend({
                    onDragStyle: null,
                    onDropStyle: null,
                    // Add in the default class for whileDragging
                    onDragClass: "tDnD_whileDrag",
                    onDrop: null,
                    onDragStart: null,
                    scrollAmount: 5,
                    /** Sensitivity setting will throttle the trigger rate for movement detection */
                    sensitivity: 10,
                    /** Hierarchy level to support parent child. 0 switches this functionality off */
                    hierarchyLevel: 0,
                    /** The html artifact to prepend the first cell with as indentation */
                    indentArtifact: '<div class="indent">&nbsp;</div>',
                    /** Automatically adjust width of first cell */
                    autoWidthAdjust: true,
                    /** Automatic clean-up to ensure relationship integrity */
                    autoCleanRelations: true,
                    /** Specify a number (4) as number of spaces or any indent string for JSON.stringify */
                    jsonPretifySeparator: '\t',
                    /** The regular expression to use to trim row IDs */
                    serializeRegexp: /[^\-]*$/,
                    /** If you want to specify another parameter name instead of the table ID */
                    serializeParamName: false,
                    /** If you give the name of a class here, then only Cells with this class will be draggable */
                    dragHandle: null
                }, options || {});

                // Now make the rows draggable
                $.tableDnD.makeDraggable(this);
                // Prepare hierarchy support
                this.tableDnDConfig.hierarchyLevel
                && $.tableDnD.makeIndented(this);
            });

            // Don't break the chain
            return this;
        },
        makeIndented: function (table) {
            var config = table.tableDnDConfig,
                rows = table.rows,
                firstCell = $(rows).first().find('td:first')[0],
                indentLevel = 0,
                cellWidth = 0,
                longestCell,
                tableStyle;

            if ($(table).hasClass('indtd'))
                return null;

            tableStyle = $(table).addClass('indtd').attr('style');
            $(table).css({whiteSpace: "nowrap"});

            for (var w = 0; w < rows.length; w++) {
                if (cellWidth < $(rows[w]).find('td:first').text().length) {
                    cellWidth = $(rows[w]).find('td:first').text().length;
                    longestCell = w;
                }
            }
            $(firstCell).css({width: 'auto'});
            for (w = 0; w < config.hierarchyLevel; w++)
                $(rows[longestCell]).find('td:first').prepend(config.indentArtifact);
            firstCell && $(firstCell).css({width: firstCell.offsetWidth});
            tableStyle && $(table).css(tableStyle);

            for (w = 0; w < config.hierarchyLevel; w++)
                $(rows[longestCell]).find('td:first').children(':first').remove();

            config.hierarchyLevel
            && $(rows).each(function () {
                indentLevel = $(this).data('level') || 0;
                indentLevel <= config.hierarchyLevel
                    && $(this).data('level', indentLevel)
                || $(this).data('level', 0);
                for (var i = 0; i < $(this).data('level'); i++)
                    $(this).find('td:first').prepend(config.indentArtifact);
            });

            return this;
        },
        /** This function makes all the rows on the table draggable apart from those marked as "NoDrag" */
        makeDraggable: function(table) {
            var config = table.tableDnDConfig;

            config.dragHandle
                // We only need to add the event to the specified cells
                && $(config.dragHandle, table).each(function() {
                // The cell is bound to "this"
                $(this).bind(startEvent, function(e) {
                    $.tableDnD.initialiseDrag($(this).parents('tr')[0], table, this, e, config);
                    return false;
                });
            })
                // For backwards compatibility, we add the event to the whole row
                // get all the rows as a wrapped set
            || $(table.rows).each(function() {
                // Iterate through each row, the row is bound to "this"
                if (! $(this).hasClass("nodrag")) {
                    $(this).bind(startEvent, function(e) {
                        if (e.target.tagName == "TD") {
                            $.tableDnD.initialiseDrag(this, table, this, e, config);
                            return false;
                        }
                    }).css("cursor", "move"); // Store the tableDnD object
                }
            });
        },
        currentOrder: function() {
            var rows = this.currentTable.rows;
            return $.map(rows, function (val) {
                return ($(val).data('level') + val.id).replace(/\s/g, '');
            }).join('');
        },
        initialiseDrag: function(dragObject, table, target, e, config) {
            this.dragObject    = dragObject;
            this.currentTable  = table;
            this.mouseOffset   = this.getMouseOffset(target, e);
            this.originalOrder = this.currentOrder();

            // Now we need to capture the mouse up and mouse move event
            // We can use bind so that we don't interfere with other event handlers
            $(document)
                .bind(moveEvent, this.mousemove)
                .bind(endEvent, this.mouseup);

            // Call the onDragStart method if there is one
            config.onDragStart
            && config.onDragStart(table, target);
        },
        updateTables: function() {
            this.each(function() {
                // this is now bound to each matching table
                if (this.tableDnDConfig)
                    $.tableDnD.makeDraggable(this);
            });
        },
        /** Get the mouse coordinates from the event (allowing for browser differences) */
        mouseCoords: function(e) {
            if(e.pageX || e.pageY)
                return {
                    x: e.pageX,
                    y: e.pageY
                };

            return {
                x: e.clientX + document.body.scrollLeft - document.body.clientLeft,
                y: e.clientY + document.body.scrollTop  - document.body.clientTop
            };
        },
        /** Given a target element and a mouse eent, get the mouse offset from that element.
         To do this we need the element's position and the mouse position */
        getMouseOffset: function(target, e) {
            var mousePos,
                docPos;

            e = e || window.event;

            docPos    = this.getPosition(target);
            mousePos  = this.mouseCoords(e);

            return {
                x: mousePos.x - docPos.x,
                y: mousePos.y - docPos.y
            };
        },
        /** Get the position of an element by going up the DOM tree and adding up all the offsets */
        getPosition: function(element) {
            var left = 0,
                top  = 0;

            // Safari fix -- thanks to Luis Chato for this!
            // Safari 2 doesn't correctly grab the offsetTop of a table row
            // this is detailed here:
            // http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
            // the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
            // note that firefox will return a text node as a first child, so designing a more thorough
            // solution may need to take that into account, for now this seems to work in firefox, safari, ie
            if (element.offsetHeight == 0)
                element = element.firstChild; // a table cell

            while (element.offsetParent) {
                left   += element.offsetLeft;
                top    += element.offsetTop;
                element = element.offsetParent;
            }

            left += element.offsetLeft;
            top  += element.offsetTop;

            return {
                x: left,
                y: top
            };
        },
        autoScroll: function (mousePos) {
            var config       = this.currentTable.tableDnDConfig,
                yOffset      = window.pageYOffset,
                windowHeight = window.innerHeight
                    ? window.innerHeight
                    : document.documentElement.clientHeight
                    ? document.documentElement.clientHeight
                    : document.body.clientHeight;

            // Windows version
            // yOffset=document.body.scrollTop;
            if (document.all)
                if (typeof document.compatMode != 'undefined'
                    && document.compatMode != 'BackCompat')
                    yOffset = document.documentElement.scrollTop;
                else if (typeof document.body != 'undefined')
                    yOffset = document.body.scrollTop;

            mousePos.y - yOffset < config.scrollAmount
                && window.scrollBy(0, - config.scrollAmount)
            || windowHeight - (mousePos.y - yOffset) < config.scrollAmount
                && window.scrollBy(0, config.scrollAmount);

        },
        moveVerticle: function (moving, currentRow) {

            if (0 != moving.vertical
                // If we're over a row then move the dragged row to there so that the user sees the
                // effect dynamically
                && currentRow
                && this.dragObject != currentRow
                && this.dragObject.parentNode == currentRow.parentNode)
                0 > moving.vertical
                    && this.dragObject.parentNode.insertBefore(this.dragObject, currentRow.nextSibling)
                || 0 < moving.vertical
                    && this.dragObject.parentNode.insertBefore(this.dragObject, currentRow);

        },
        moveHorizontal: function (moving, currentRow) {
            var config       = this.currentTable.tableDnDConfig,
                currentLevel;

            if (!config.hierarchyLevel
                || 0 == moving.horizontal
                // We only care if moving left or right on the current row
                || !currentRow
                || this.dragObject != currentRow)
                return null;

            currentLevel = $(currentRow).data('level');

            0 < moving.horizontal
                && currentLevel > 0
                && $(currentRow).find('td:first').children(':first').remove()
            && $(currentRow).data('level', --currentLevel);

            0 > moving.horizontal
                && currentLevel < config.hierarchyLevel
                && $(currentRow).prev().data('level') >= currentLevel
                && $(currentRow).children(':first').prepend(config.indentArtifact)
            && $(currentRow).data('level', ++currentLevel);

        },
        mousemove: function(e) {
            var dragObj      = $($.tableDnD.dragObject),
                config       = $.tableDnD.currentTable.tableDnDConfig,
                currentRow,
                mousePos,
                moving,
                x,
                y;

            e && e.preventDefault();

            if (!$.tableDnD.dragObject)
                return false;

            // prevent touch device screen scrolling
            e.type == 'touchmove'
            && event.preventDefault(); // TODO verify this is event and not really e

            // update the style to show we're dragging
            config.onDragClass
                && dragObj.addClass(config.onDragClass)
            || dragObj.css(config.onDragStyle);

            mousePos = $.tableDnD.mouseCoords(e);
            x = mousePos.x - $.tableDnD.mouseOffset.x;
            y = mousePos.y - $.tableDnD.mouseOffset.y;

            // auto scroll the window
            $.tableDnD.autoScroll(mousePos);

            currentRow = $.tableDnD.findDropTargetRow(dragObj, y);
            moving = $.tableDnD.findDragDirection(x, y);

            $.tableDnD.moveVerticle(moving, currentRow);
            $.tableDnD.moveHorizontal(moving, currentRow);

            return false;
        },
        findDragDirection: function (x,y) {
            var sensitivity = this.currentTable.tableDnDConfig.sensitivity,
                oldX        = this.oldX,
                oldY        = this.oldY,
                xMin        = oldX - sensitivity,
                xMax        = oldX + sensitivity,
                yMin        = oldY - sensitivity,
                yMax        = oldY + sensitivity,
                moving      = {
                    horizontal: x >= xMin && x <= xMax ? 0 : x > oldX ? -1 : 1,
                    vertical  : y >= yMin && y <= yMax ? 0 : y > oldY ? -1 : 1
                };

            // update the old value
            if (moving.horizontal != 0)
                this.oldX    = x;
            if (moving.vertical   != 0)
                this.oldY    = y;

            return moving;
        },
        /** We're only worried about the y position really, because we can only move rows up and down */
        findDropTargetRow: function(draggedRow, y) {
            var rowHeight = 0,
                rows      = this.currentTable.rows,
                config    = this.currentTable.tableDnDConfig,
                rowY      = 0,
                row       = null;

            for (var i = 0; i < rows.length; i++) {
                row       = rows[i];
                rowY      = this.getPosition(row).y;
                rowHeight = parseInt(row.offsetHeight) / 2;
                if (row.offsetHeight == 0) {
                    rowY      = this.getPosition(row.firstChild).y;
                    rowHeight = parseInt(row.firstChild.offsetHeight) / 2;
                }
                // Because we always have to insert before, we need to offset the height a bit
                if (y > (rowY - rowHeight) && y < (rowY + rowHeight))
                // that's the row we're over
                // If it's the same as the current row, ignore it
                    if (draggedRow.is(row)
                        || (config.onAllowDrop
                        && !config.onAllowDrop(draggedRow, row))
                        // If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
                        || $(row).hasClass("nodrop"))
                        return null;
                    else
                        return row;
            }
            return null;
        },
        processMouseup: function() {
            var config      = this.currentTable.tableDnDConfig,
                droppedRow  = this.dragObject,
                parentLevel = 0,
                myLevel     = 0;

            if (!this.currentTable || !droppedRow)
                return null;

            // Unbind the event handlers
            $(document)
                .unbind(moveEvent, this.mousemove)
                .unbind(endEvent,  this.mouseup);

            config.hierarchyLevel
                && config.autoCleanRelations
                && $(this.currentTable.rows).first().find('td:first').children().each(function () {
                myLevel = $(this).parents('tr:first').data('level');
                myLevel
                    && $(this).parents('tr:first').data('level', --myLevel)
                && $(this).remove();
            })
                && config.hierarchyLevel > 1
            && $(this.currentTable.rows).each(function () {
                myLevel = $(this).data('level');
                if (myLevel > 1) {
                    parentLevel = $(this).prev().data('level');
                    while (myLevel > parentLevel + 1) {
                        $(this).find('td:first').children(':first').remove();
                        $(this).data('level', --myLevel);
                    }
                }
            });

            // If we have a dragObject, then we need to release it,
            // The row will already have been moved to the right place so we just reset stuff
            config.onDragClass
                && $(droppedRow).removeClass(config.onDragClass)
            || $(droppedRow).css(config.onDropStyle);

            this.dragObject = null;
            // Call the onDrop method if there is one
            config.onDrop
               /* && this.originalOrder != this.currentOrder()
                && $(droppedRow).hide().fadeIn('fast')*/
            && config.onDrop(this.currentTable, droppedRow, this.originalOrder != this.currentOrder());

            this.currentTable = null; // let go of the table too
        },
        mouseup: function(e) {
            e && e.preventDefault();
            $.tableDnD.processMouseup();
            return false;
        },
        jsonize: function(pretify) {
            var table = this.currentTable;
            if (pretify)
                return JSON.stringify(
                    this.tableData(table),
                    null,
                    table.tableDnDConfig.jsonPretifySeparator
                );
            return JSON.stringify(this.tableData(table));
        },
        serialize: function() {
            return $.param(this.tableData(this.currentTable));
        },
        serializeTable: function(table) {
            var result = "";
            var paramName = table.tableDnDConfig.serializeParamName || table.id;
            var rows = table.rows;
            for (var i=0; i<rows.length; i++) {
                if (result.length > 0) result += "&";
                var rowId = rows[i].id;
                if (rowId && table.tableDnDConfig && table.tableDnDConfig.serializeRegexp) {
                    rowId = rowId.match(table.tableDnDConfig.serializeRegexp)[0];
                    result += paramName + '[]=' + rowId;
                }
            }
            return result;
        },
        serializeTables: function() {
            var result = [];
            $('table').each(function() {
                this.id && result.push($.param(this.tableData(this)));
            });
            return result.join('&');
        },
        tableData: function (table) {
            var config = table.tableDnDConfig,
                previousIDs  = [],
                currentLevel = 0,
                indentLevel  = 0,
                rowID        = null,
                data         = {},
                getSerializeRegexp,
                paramName,
                currentID,
                rows;

            if (!table)
                table = this.currentTable;
            if (!table || !table.id || !table.rows || !table.rows.length)
                return {error: { code: 500, message: "Not a valid table, no serializable unique id provided."}};

            rows      = config.autoCleanRelations
                && table.rows
                || $.makeArray(table.rows);
            paramName = config.serializeParamName || table.id;
            currentID = paramName;

            getSerializeRegexp = function (rowId) {
                if (rowId && config && config.serializeRegexp)
                    return rowId.match(config.serializeRegexp)[0];
                return rowId;
            };

            data[currentID] = [];
            !config.autoCleanRelations
                && $(rows[0]).data('level')
            && rows.unshift({id: 'undefined'});



            for (var i=0; i < rows.length; i++) {
                if (config.hierarchyLevel) {
                    indentLevel = $(rows[i]).data('level') || 0;
                    if (indentLevel == 0) {
                        currentID   = paramName;
                        previousIDs = [];
                    }
                    else if (indentLevel > currentLevel) {
                        previousIDs.push([currentID, currentLevel]);
                        currentID = getSerializeRegexp(rows[i-1].id);
                    }
                    else if (indentLevel < currentLevel) {
                        for (var h = 0; h < previousIDs.length; h++) {
                            if (previousIDs[h][1] == indentLevel)
                                currentID         = previousIDs[h][0];
                            if (previousIDs[h][1] >= currentLevel)
                                previousIDs[h][1] = 0;
                        }
                    }
                    currentLevel = indentLevel;

                    if (!$.isArray(data[currentID]))
                        data[currentID] = [];
                    rowID = getSerializeRegexp(rows[i].id);
                    rowID && data[currentID].push(rowID);
                }
                else {
                    rowID = getSerializeRegexp(rows[i].id);
                    rowID && data[currentID].push(rowID);
                }
            }
            return data;
        }
    };

    window.jQuery.fn.extend(
        {
            tableDnD             : $.tableDnD.build,
            tableDnDUpdate       : $.tableDnD.updateTables,
            tableDnDSerialize    : $.proxy($.tableDnD.serialize, $.tableDnD),
            tableDnDSerializeAll : $.tableDnD.serializeTables,
            tableDnDData         : $.proxy($.tableDnD.tableData, $.tableDnD)
        }
    );

}(window.jQuery, window, window.document);

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
    $filtersContainer: null,
    $filtersInputs: null,

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