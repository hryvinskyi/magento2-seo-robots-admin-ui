/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'Magento_Ui/js/form/element/multiselect',
    'jquery',
    'robotsTagSelector'
], function (Multiselect, $) {
    'use strict';

    return Multiselect.extend({
        defaults: {
            elementTmpl: 'Hryvinskyi_SeoRobotsAdminUi/form/element/directive-multiselect',
            enableBotNames: false,
            directives: {},
            imports: {
                update: '${ $.provider }:data.validate'
            }
        },

        _isUpdatingFromWidget: false,

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            console.log('[DirectiveMultiselect] Component initialized', this.dataScope, 'Initial value:', this.value());
            return this;
        },

        /**
         * Initialize robots tag selector after render
         */
        initRobotsSelector: function (element) {
            var self = this;
            var $element = $(element);
            var $select = $element.find('select.directive-multiselect-hidden');
            var $wrapper = $element.find('.robots-tag-selector-wrapper');

            console.log('[DirectiveMultiselect] Init selector for', self.dataScope);
            console.log('[DirectiveMultiselect] Found select:', $select.length, 'Found wrapper:', $wrapper.length);

            if (!$select.length || !$wrapper.length) {
                console.warn('[DirectiveMultiselect] Select or wrapper not found', element);
                return;
            }

            // Avoid double initialization
            if ($wrapper.data('robots-selector-initialized')) {
                console.log('[DirectiveMultiselect] Already initialized, skipping');
                return;
            }

            // Hide the original select
            $select.hide();

            var options = {
                placeholder: 'Select directives...',
                enableBotNames: self.enableBotNames,
                onChange: function (values) {
                    console.log('[DirectiveMultiselect] onChange callback triggered with:', values);

                    // Set flag to prevent subscribe callback from updating widget
                    self._isUpdatingFromWidget = true;

                    // Update component value (which will update the select via knockout binding)
                    self.value(values);

                    // Reset flag after a short delay (allow knockout to process)
                    setTimeout(function() {
                        self._isUpdatingFromWidget = false;
                    }, 50);
                }
            };

            if (self.directives && Object.keys(self.directives).length > 0) {
                options.directives = self.directives;
            }

            console.log('[DirectiveMultiselect] Initializing robotsTagSelector with options:', options);

            // Initialize the robotsTagSelector widget
            $wrapper.robotsTagSelector(options);

            // Mark as initialized
            $wrapper.data('robots-selector-initialized', true);

            // Set initial value if exists
            var initialValue = self.value();
            console.log('[DirectiveMultiselect] Initial value:', initialValue, 'Type:', typeof initialValue);

            if (initialValue && initialValue.length > 0) {
                setTimeout(function () {
                    console.log('[DirectiveMultiselect] Setting initial value to widget:', initialValue);
                    $wrapper.robotsTagSelector('setValue', initialValue);
                }, 100);
            }

            // Subscribe to value changes from the component (for external updates)
            self.value.subscribe(function (newValue) {
                console.log('[DirectiveMultiselect] Value changed in component:', newValue, 'Type:', typeof newValue, 'isUpdatingFromWidget:', self._isUpdatingFromWidget);

                // Skip if this change came from the widget itself
                if (self._isUpdatingFromWidget) {
                    console.log('[DirectiveMultiselect] Skipping update - change came from widget');
                    return;
                }

                if ($wrapper.data('robotsTagSelector')) {
                    var currentValue = $wrapper.robotsTagSelector('getValue') || [];
                    console.log('[DirectiveMultiselect] Current widget value:', currentValue);

                    // Normalize newValue - could be array, string (single value or JSON), or empty
                    var normalizedNewValue = [];
                    if (newValue) {
                        if (typeof newValue === 'string') {
                            // Try parsing as JSON first
                            if (newValue.startsWith('[') || newValue.startsWith('{')) {
                                try {
                                    normalizedNewValue = JSON.parse(newValue);
                                    console.log('[DirectiveMultiselect] Parsed JSON to array:', normalizedNewValue);
                                } catch (e) {
                                    console.error('[DirectiveMultiselect] Failed to parse JSON:', e);
                                    normalizedNewValue = [];
                                }
                            } else {
                                // It's a single directive value from knockout binding
                                normalizedNewValue = [newValue];
                                console.log('[DirectiveMultiselect] Converted single string to array:', normalizedNewValue);
                            }
                        } else if (Array.isArray(newValue)) {
                            normalizedNewValue = newValue;
                        }
                    }

                    console.log('[DirectiveMultiselect] Normalized value:', normalizedNewValue);

                    // Only update if values actually changed (avoid circular updates)
                    if (JSON.stringify(currentValue.sort()) !== JSON.stringify(normalizedNewValue.sort())) {
                        console.log('[DirectiveMultiselect] Values differ, updating widget');
                        $wrapper.robotsTagSelector('setValue', normalizedNewValue);
                    } else {
                        console.log('[DirectiveMultiselect] Values are the same, skipping update');
                    }
                }
            });

            console.log('[DirectiveMultiselect] Initialization complete for', self.dataScope);
        }
    });
});
