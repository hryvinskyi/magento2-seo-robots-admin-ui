/**
 * Copyright (c) 2026. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'robotsTagSelector'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Hryvinskyi_SeoRobotsProductAdminUi/form/element/robots-textarea',
            enableBotNames: false,
            directives: {}
        },

        _isUpdatingFromWidget: false,
        _isUpdatingFromTextarea: false,
        _widgetInitialized: false,

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();

            // Ensure input name doesn't have [] suffix
            if (typeof this.inputName === 'string' && this.inputName.slice(-2) === '[]') {
                this.inputName = this.inputName.slice(0, -2);
            }

            this.normalizeInitialValue();
            return this;
        },

        /**
         * Normalize initial value - store as JSON string for form submission
         */
        normalizeInitialValue: function () {
            var currentValue = this.value();
            var arrayValue;

            if (!currentValue) {
                this.value('[]');
                return;
            }

            // Handle JSON string
            if (typeof currentValue === 'string') {
                try {
                    arrayValue = JSON.parse(currentValue);
                } catch (e) {
                    this.value('[]');
                    return;
                }
            } else {
                arrayValue = currentValue;
            }

            if (!Array.isArray(arrayValue)) {
                this.value('[]');
                return;
            }

            // Normalize each item
            var normalized = arrayValue.map(function (item) {
                if (typeof item === 'object' && item !== null) {
                    return {
                        value: item.value || '',
                        bot: item.bot || '',
                        modification: item.modification || ''
                    };
                }
                return { value: String(item), bot: '', modification: '' };
            }).filter(function (item) {
                return item.value !== '';
            });

            // Store as JSON string for form submission
            this.value(JSON.stringify(normalized));
        },

        /**
         * Get array value from JSON string
         */
        getArrayValue: function () {
            var val = this.value();
            if (!val || typeof val !== 'string') {
                return [];
            }
            try {
                var parsed = JSON.parse(val);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        },

        /**
         * Set value from array
         */
        setArrayValue: function (arr) {
            this.value(JSON.stringify(arr || []));
        },

        /**
         * Get JSON string for textarea display
         */
        getJsonValue: function () {
            var arr = this.getArrayValue();
            if (arr.length === 0) {
                return '';
            }
            return JSON.stringify(arr, null, 2);
        },

        /**
         * Initialize after render
         */
        initRobotsSelector: function (element) {
            var self = this;
            var $element = $(element);
            var $wrapper = $element.find('.robots-tag-selector-wrapper');
            var $textarea = $element.find('.robots-json-textarea');
            var $hiddenInput = $element.find('.robots-hidden-value');

            if (!$wrapper.length || this._widgetInitialized) {
                return;
            }
            this._widgetInitialized = true;

            var options = {
                placeholder: 'Select directives...',
                enableBotNames: self.enableBotNames,
                onChange: function (values) {
                    if (self._isUpdatingFromTextarea) {
                        return;
                    }
                    self._isUpdatingFromWidget = true;
                    self.setArrayValue(values);

                    var jsonStr = values.length > 0 ? JSON.stringify(values, null, 2) : '';
                    $textarea.val(jsonStr);
                    $hiddenInput.val(JSON.stringify(values));

                    setTimeout(function () {
                        self._isUpdatingFromWidget = false;
                    }, 50);
                }
            };

            if (self.directives && Object.keys(self.directives).length > 0) {
                options.directives = self.directives;
            }

            $wrapper.robotsTagSelector(options);

            // Set initial value
            var initialValue = self.getArrayValue();
            if (initialValue && initialValue.length > 0) {
                setTimeout(function () {
                    $wrapper.robotsTagSelector('setValue', initialValue);
                    $textarea.val(JSON.stringify(initialValue, null, 2));
                    $hiddenInput.val(JSON.stringify(initialValue));
                }, 100);
            }

            // Handle textarea changes
            $textarea.on('blur', function () {
                self.handleTextareaChange($textarea, $wrapper, $hiddenInput);
            });

            // Subscribe to external value changes
            self.value.subscribe(function (newValue) {
                if (self._isUpdatingFromWidget || self._isUpdatingFromTextarea) {
                    return;
                }

                var arr = self.getArrayValue();
                var jsonStr = arr.length > 0 ? JSON.stringify(arr, null, 2) : '';
                $textarea.val(jsonStr);
                $hiddenInput.val(newValue || '[]');

                if ($wrapper.data('robotsTagSelector')) {
                    $wrapper.robotsTagSelector('setValue', arr);
                }
            });
        },

        /**
         * Handle textarea change - parse JSON and update widget
         */
        handleTextareaChange: function ($textarea, $wrapper, $hiddenInput) {
            var self = this;
            var jsonStr = $textarea.val().trim();

            if (!jsonStr) {
                self._isUpdatingFromTextarea = true;
                self.setArrayValue([]);
                $hiddenInput.val('[]');
                if ($wrapper.data('robotsTagSelector')) {
                    $wrapper.robotsTagSelector('setValue', []);
                }
                setTimeout(function () {
                    self._isUpdatingFromTextarea = false;
                }, 50);
                return;
            }

            try {
                var parsed = JSON.parse(jsonStr);
                if (!Array.isArray(parsed)) {
                    self.showTextareaError($textarea, 'Value must be a JSON array');
                    return;
                }

                // Normalize
                var normalized = parsed.map(function (item) {
                    if (typeof item === 'object' && item !== null) {
                        return {
                            value: item.value || '',
                            bot: item.bot || '',
                            modification: item.modification || ''
                        };
                    }
                    return { value: String(item), bot: '', modification: '' };
                }).filter(function (item) {
                    return item.value !== '';
                });

                self._isUpdatingFromTextarea = true;
                self.setArrayValue(normalized);
                $hiddenInput.val(JSON.stringify(normalized));

                // Update widget
                if ($wrapper.data('robotsTagSelector')) {
                    $wrapper.robotsTagSelector('setValue', normalized);
                }

                // Format textarea
                $textarea.val(JSON.stringify(normalized, null, 2));
                self.clearTextareaError($textarea);

                setTimeout(function () {
                    self._isUpdatingFromTextarea = false;
                }, 50);

            } catch (e) {
                self.showTextareaError($textarea, 'Invalid JSON: ' + e.message);
            }
        },

        /**
         * Show error on textarea
         */
        showTextareaError: function ($textarea, message) {
            $textarea.addClass('_error');
            var $container = $textarea.closest('.robots-textarea-container');
            $container.find('.robots-json-error').remove();
            $container.append('<div class="robots-json-error" style="color: #e22626; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        },

        /**
         * Clear textarea error
         */
        clearTextareaError: function ($textarea) {
            $textarea.removeClass('_error');
            $textarea.closest('.robots-textarea-container').find('.robots-json-error').remove();
        },

        /**
         * Check if value has changed
         */
        hasChanged: function () {
            var value = this.value() || '[]';
            var initial = this.initialValue || '[]';
            return value !== initial;
        }
    });
});
