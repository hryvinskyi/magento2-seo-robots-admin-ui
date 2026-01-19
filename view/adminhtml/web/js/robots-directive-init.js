/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

require([
    'jquery',
    'robotsTagSelector'
], function ($) {
    'use strict';

    // Helper to parse legacy strings like "google:noindex"
    function parseLegacyString(str) {
        var result = { value: '', bot: '', modification: '' };
        var parts = str.split(':');
        var advancedDirectives = ['max-snippet', 'max-image-preview', 'max-video-preview', 'unavailable_after'];

        if (parts.length === 1) {
            result.value = parts[0];
        } else if (parts.length === 2) {
            var firstLower = parts[0].toLowerCase();
            if (advancedDirectives.indexOf(firstLower) !== -1) {
                result.value = parts[0];
                result.modification = parts[1];
            } else {
                result.bot = parts[0];
                result.value = parts[1];
            }
        } else if (parts.length >= 3) {
            result.bot = parts[0];
            result.value = parts[1];
            result.modification = parts.slice(2).join(':');
        }
        return result;
    }

    function initRobotSelector(element) {
        var $textarea = $(element);

        if ($textarea.data('initialized')) {
            return;
        }

        // Skip initialization for template rows (containing underscore template tags)
        var name = $textarea.attr('name');
        if (name && name.indexOf('<%- _id %>') !== -1) {
            return;
        }

        // Create wrapper dynamically
        var $wrapper = $('<div class="robots-tag-selector-wrapper"></div>');
        $textarea.after($wrapper);

        // Settings
        var directives = $textarea.data('directives') || {};
        var enableBotNames = $textarea.data('enable-bot-names') || false;

        // Parse initial value from textarea
        var initialValue = [];
        try {
            var val = $textarea.val();
            if (val) {
                initialValue = JSON.parse(val);
            }
        } catch (e) {
            console.log(initialValue);
            console.warn('Invalid JSON in robots directives', e, val);
        }

        // Normalize legacy format if needed
        if (!Array.isArray(initialValue)) {
            initialValue = [];
        } else {
            initialValue = initialValue.map(function (item) {
                if (typeof item === 'string') {
                    return parseLegacyString(item);
                }
                // Handle array format like ["index"] -> {value: "index", bot: "", modification: ""}
                if (Array.isArray(item)) {
                    return { value: item[0] || '', bot: item[1] || '', modification: item[2] || '' };
                }
                // Handle object format - ensure all keys exist
                if (typeof item === 'object' && item !== null) {
                    return { value: item.value || '', bot: item.bot || '', modification: item.modification || '' };
                }
                return item;
            }).filter(function (item) {
                return item && item.value && item.value !== '';
            });
        }

        // Initialize plugin
        var options = {
            placeholder: 'Add directives...',
            enableBotNames: enableBotNames,
            onChange: function (values) {
                // Update textarea with JSON string
                $textarea.val(JSON.stringify(values));
                // Trigger change for any listeners
                $textarea.trigger('change');
            }
        };

        if (directives && Object.keys(directives).length > 0) {
            options.directives = directives;
        }

        $wrapper.robotsTagSelector(options);

        // Set initial values
        if (initialValue.length > 0) {
            setTimeout(function () {
                $wrapper.robotsTagSelector('setValue', initialValue);
            }, 50);
        }

        // Check if the field is disabled (scope inheritance)
        if ($textarea.prop('disabled') || $textarea.hasClass('disabled')) {
            $wrapper.robotsTagSelector('disable');
        }

        $textarea.data('initialized', true);
    }

    // Global initializer function
    window.initRobotsTagSelectors = function () {
        $('.directive-multiselect-json').each(function () {
            initRobotSelector(this);
        });
    };

    function applyDisabledState(textarea, isInherited) {
        const $textarea = $(textarea);
        $textarea.closest('.directive-multiselect-container')
            .find('.robots-tag-selector-wrapper')
            .each(function () {
                const $wrapper = $(this);
                if (isInherited) {
                    $wrapper.robotsTagSelector('disable');
                } else {
                    $wrapper.robotsTagSelector('enable');
                }
            });
    }

    function setupInheritCheckboxes() {
        $('.directive-multiselect-json').each(function () {
            const targetNode = this;

            const observer = new MutationObserver((mutationsList) => {
                for (const mutation of mutationsList) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                        const isDisabled = $(targetNode).prop('disabled');
                        applyDisabledState(targetNode, isDisabled);
                    }
                }
            });

            observer.observe(targetNode, {
                attributes: true,
                attributeFilter: ['disabled']
            });

            applyDisabledState(targetNode, $(targetNode).prop('disabled'));
        });
    }

    // Initialize all directive multiselects on page
    window.initRobotsTagSelectors();

    // Setup inherit checkbox handlers
    setupInheritCheckboxes();

    // Handle dynamic rows addition
    $(document).on('contentUpdated', function () {
        window.initRobotsTagSelectors();
        setupInheritCheckboxes();
    });

    // Use MutationObserver as a backup for dynamic rows
    var observer = new MutationObserver(function (mutations) {
        var shouldCheck = false;
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length) {
                shouldCheck = true;
            }
        });

        if (shouldCheck) {
            window.initRobotsTagSelectors();
            setupInheritCheckboxes();
        }
    });

    var configForm = document.querySelector('#config-edit-form');
    if (configForm) {
        observer.observe(configForm, { childList: true, subtree: true });
    }
});
