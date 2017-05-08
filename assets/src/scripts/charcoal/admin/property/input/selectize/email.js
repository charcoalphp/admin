/**
 * Selectize Picker
 * List version.
 *
 * Require
 * - selectize.js
 */

;(function () {
    var Email = function (opts) {
        this.input_type = 'charcoal/admin/property/input/selectize';

        // Property_Input_Selectize properties
        this.input_id = null;
        this.obj_type = null;
        this.copy_items = false;
        this.title = null;
        this.translations = null;

        // Pattern refers to the form property that matches the text inputted through selectize.
        this.pattern = null;
        this.multiple = false;
        this.separator = ',';

        this.selectize = null;
        this.selectize_selector = null;
        this.form_ident = null;
        this.selectize_options = {};

        this.clipboard = null;
        this.allow_update = false;

        this.set_properties(opts).init();
    };
    Email.prototype = Object.create(Charcoal.Admin.Property_Input_Selectize.prototype);
    Email.constructor = Charcoal.Admin.Property_Input_Selectize;
    Email.parent = Charcoal.Admin.Property_Input_Selectize.prototype;

    Email.prototype.set_properties = function (opts) {
        this.input_id = opts.id || this.input_id;
        this.obj_type = opts.data.obj_type || this.obj_type;

        // Enables the copy button
        this.copy_items = opts.data.copy_items || this.copy_items;
        this.allow_update = opts.data.allow_update || this.allow_update;
        this.title = opts.data.title || this.title;
        this.translations = opts.data.translations || this.translations;
        this.pattern = opts.data.pattern || this.pattern;
        this.multiple = opts.data.multiple || this.multiple;
        this.separator = opts.data.multiple_separator || this.multiple_separator || ',';
        this.form_ident = opts.data.form_ident || this.form_ident;

        this.selectize_selector = opts.data.selectize_selector || this.selectize_selector;

        this.selectize_options = opts.data.selectize_options || this.selectize_options;

        this.$input = $(this.selectize_selector || '#' + this.input_id);

        var plugins;
        if (this.multiple) {
            plugins = {
                // 'restore_on_backspace',
                drag_drop: {},
                charcoal_item: {}
            };

        } else {
            plugins = {
                charcoal_item: {}
            };
        }

        var objType = this.obj_type;
        var default_opts = {
            plugins: plugins,
            formData: {},
            delimiter: this.separator,
            persist: true,
            preload: 'focus',
            openOnFocus: true,
            searchField: ['value', 'text', 'email'],
            dropdownParent: this.$input.closest('.form-field'),

            createFilter: function (input) {
                for (var item in this.options) {
                    item = this.options[item];
                    if (item.text === input) {
                        return false;
                    }
                }
                return true;
            },
            onInitialize: function () {
                var self = this;
                self.sifter.iterator(this.items, function (value) {
                    var option = self.options[value];
                    var $item = self.getItem(value);

                    if (option.color) {
                        $item.css('background-color', option.color/*[options.colorField]*/);
                    }
                });
            },
            render: {
                item: function (item, escape) {
                    return '<div class="item">' +
                        (item.text ? '<span class="name">' + escape(item.text) + '</span>' : '') +
                        (item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') +
                            '</div>';
                },
                option: function (item, escape) {
                    return '<div class="option">' +
                        (item.text ? '<span class="name">' + escape(item.text) + '</span>' : '') +
                        (item.email ? '<span class="caption">' + escape(item.email) + '</span>' : '') +
                        '</div>';
                }
            }
        };

        if (objType) {
            default_opts.create = this.create_item.bind(this);
            default_opts.load = this.load_items.bind(this);
        } else {
            default_opts.plugins.create_on_enter = {};
            default_opts.create = function (input) {
                return {
                    value: input,
                    text: input
                };
            };
        }

        if (this.selectize_options.splitOn) {
            var splitOn = this.selectize_options.splitOn;
            if ($.type(splitOn) === 'array') {
                for (var i = splitOn.length - 1; i >= 0; i--) {
                    switch (splitOn[i]) {
                        case 'comma':
                            splitOn[i] = '\\s*,\\s*';
                            break;

                        case 'tab':
                            splitOn[i] = '\\t+';
                            break;

                        default:
                            splitOn[i] = splitOn[i].replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                    }
                }

                splitOn = splitOn.join('|');
            }

            this.selectize_options.splitOn = new RegExp(splitOn);
        }

        this.selectize_options = $.extend(true,{}, default_opts, this.selectize_options);

        return this;
    };

    Email.parent.create_item = function (input, callback, opts) {
        var form_data = {};
        opts = opts || {};
        var pattern = this.pattern;
        var self = this;
        var type = this.obj_type;
        var title = this.title;
        var translations = this.translations;
        var settings = this.selectize_options;
        var step = opts.step || 0;
        var form_ident = this.form_ident;
        var submit_label = null;
        var id = opts.id || null;

        // Get the form ident
        if (form_ident && typeof form_ident === 'object') {
            if (!id && form_ident.create) {
                // The object must be created using 2 pop-up
                form_ident = form_ident.create;
                title += ' - ' + translations.statusTemplate.replaceMap({
                        '[[ current ]]': 1,
                        '[[ total ]]': 2
                    });
                step = 1;
                submit_label = 'Next';
            } else if (id && form_ident.update) {
                form_ident = form_ident.update;

                if (step === 2) {
                    title += ' - ' + translations.statusTemplate.replaceMap({
                            '[[ current ]]': 2,
                            '[[ total ]]': 2
                        });
                    submit_label = 'Finish';
                }
            } else {
                form_ident = null;
            }
        }

        if ($.isEmptyObject(settings.formData)) {
            if (pattern) {
                if (input) {
                    form_data[pattern] = input;
                }
                form_data.form_ident = form_ident;
                form_data.submit_label = submit_label;
            } else {
                if (input) {
                    form_data = {
                        name: input
                    };
                }
            }
        } else if (input) {
            form_data = $.extend({}, settings.formData);
            $.each(form_data, function (key, value) {
                if (value === ':input') {
                    form_data[key] = input;
                }
            });
        }

        var data = {
            title: title,
            size: BootstrapDialog.SIZE_WIDE,
            cssClass: '-quick-form',
            dialog_options: {
                onhide: function () {
                    callback({
                        return: false
                    });
                }
            },
            widget_type: 'charcoal/admin/widget/quickForm',
            widget_options: {
                obj_type: type,
                obj_id: id,
                form_data: form_data
            }
        };

        if (step > 0) {
            data.type = BootstrapDialog.TYPE_PRIMARY;
        }

        var dialog = this.dialog(data, function (response) {
            if (response.success) {
                // Call the quickForm widget js.
                // Really not a good place to do that.
                if (!response.widget_id) {
                    return false;
                }

                Charcoal.Admin.manager().add_widget({
                    id: response.widget_id,
                    type: 'charcoal/admin/widget/quick-form',
                    data: {
                        obj_type: type
                    },
                    obj_id: id,
                    suppress_feedback: (step === 1),
                    save_callback: function (response) {

                        var label = response.obj.id;
                        if (pattern in response.obj && response.obj[pattern]) {
                            label = response.obj[pattern][Charcoal.Admin.lang()] || response.obj[pattern];
                        } else if ('name' in response.obj && response.obj.name) {
                            label = response.obj.name[Charcoal.Admin.lang()] || response.obj.name;
                        }

                        callback({
                            value: response.obj.id,
                            text: label,
                            email: response.obj.email,
                            color: response.obj.color,
                            class: 'new'
                        });
                        dialog.close();
                        if (step === 1) {
                            self.create_item(input, callback, {
                                id: response.obj.id,
                                step: 2
                            });
                        }
                    }
                });

                // Re render.
                // This is not good.
                Charcoal.Admin.manager().render();
            }
        });
    };

    Email.parent.load_items = function (query, callback) {
        var type = this.obj_type;
        var pattern = this.pattern;

        $.ajax({
            url: Charcoal.Admin.admin_url() + 'object/load',
            data: {
                obj_type: type
            },
            type: 'GET',
            error: function () {
                callback();
            },
            success: function (res) {
                var items = [];

                for (var item in res.collection) {
                    item = res.collection[item];
                    var label = item.id;

                    if (pattern && pattern in item && item[pattern]) {
                        label = item[pattern][Charcoal.Admin.lang()] || item[pattern];
                    } else if ('name' in item && item.name) {
                        label = item.name[Charcoal.Admin.lang()] || item.name;
                    }

                    items.push({
                        value: item.id,
                        text: label,
                        email: item.email,
                        color: item.color
                    });
                }
                callback(items);
            }
        });
    };

    Charcoal.Admin.Property_Input_Selectize_Email = Email;

}(jQuery, document));