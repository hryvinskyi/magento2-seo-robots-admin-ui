/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Robots Tag Selector jQuery Plugin
 * Custom implementation for X-Robots-Tag directive management
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    // X-Robots-Tag directives database
    // Default directives if none provided
    const DEFAULT_DIRECTIVES = {
        indexing: [
            {
                value: 'all',
                label: 'all',
                description: $t('No restrictions for indexing or serving (Equivalent to index, follow)'),
                conflicts: ['noindex', 'nofollow', 'none']
            },
            {
                value: 'index',
                label: 'index',
                description: $t('Allow different indexing'),
                conflicts: ['noindex', 'none']
            },
            {
                value: 'follow',
                label: 'follow',
                description: $t('Follow links on this page'),
                conflicts: ['nofollow', 'none']
            },
            {
                value: 'noindex',
                label: 'noindex',
                description: $t('Do not show this page in search results'),
                conflicts: ['index', 'all']
            },
            {
                value: 'nofollow',
                label: 'nofollow',
                description: $t('Do not follow links on this page'),
                conflicts: ['follow', 'all']
            },
            {
                value: 'none',
                label: 'none',
                description: $t('Equivalent to noindex, nofollow'),
                conflicts: ['index', 'follow', 'all']
            }
        ],
        snippets: [
            {
                value: 'noarchive',
                label: 'noarchive',
                description: $t('Do not show a cached link in search results')
            },
            {
                value: 'nosnippet',
                label: 'nosnippet',
                description: $t('Do not show a text snippet or video preview')
            },
            {
                value: 'max-snippet',
                label: 'max-snippet',
                description: $t('Maximum text length of snippet'),
                hasInput: true,
                inputType: 'number',
                inputPlaceholder: $t('Enter character count'),
                formatter: 'numericColon'
            },
            {
                value: 'max-image-preview',
                label: 'max-image-preview',
                description: $t('Maximum size of image preview'),
                hasInput: true,
                inputType: 'select',
                inputOptions: ['none', 'standard', 'large'],
                formatter: 'standardColon'
            },
            {
                value: 'max-video-preview',
                label: 'max-video-preview',
                description: $t('Maximum video preview duration in seconds'),
                hasInput: true,
                inputType: 'number',
                inputPlaceholder: $t('Enter seconds (-1 for no limit)'),
                formatter: 'numericColon'
            }
        ],
        images: [
            {
                value: 'noimageindex',
                label: 'noimageindex',
                description: $t('Do not index images on this page')
            }
        ],
        translations: [
            {
                value: 'notranslate',
                label: 'notranslate',
                description: $t('Do not offer translation of this page')
            }
        ],
        crawling: [
            {
                value: 'unavailable_after',
                label: 'unavailable_after',
                description: $t('Do not show after specified date/time'),
                hasInput: true,
                inputType: 'datetime',
                inputPlaceholder: 'YYYY-MM-DD HH:MM:SS UTC',
                formatter: 'standardColon'
            },
            {
                value: 'indexifembedded',
                label: 'indexifembedded',
                description: $t('Allow indexing when embedded via iframe')
            }
        ]
    };

    // Plugin definition
    $.fn.robotsTagSelector = function (options) {
        // Handle method calls
        if (typeof options === 'string') {
            const method = options;
            const args = Array.prototype.slice.call(arguments, 1);
            let returnValue;

            this.each(function () {
                const instance = $.data(this, 'robotsTagSelector');
                if (instance && typeof instance[method] === 'function') {
                    returnValue = instance[method].apply(instance, args);
                }
            });

            return returnValue !== undefined ? returnValue : this;
        }

        // Initialize plugin
        return this.each(function () {
            if (!$.data(this, 'robotsTagSelector')) {
                $.data(this, 'robotsTagSelector', new RobotsTagSelector(this, options));
            }
        });
    };

    // Main class
    class RobotsTagSelector {
        constructor(element, options) {
            this.element = element;
            this.$element = $(element);

            // Default options
            this.options = $.extend({
                placeholder: $t('Add directives...'),
                maxTags: null,
                allowCustom: true,
                directives: DEFAULT_DIRECTIVES,
                onChange: null,
                onAdd: null,
                onRemove: null,
                enableBotNames: false
            }, options);

            this.selectedTags = [];
            this.isOpen = false;
            this.currentInput = '';

            this.formatters = {
                numericColon: (directive, value) => `${directive}:${value}`,
                standardColon: (directive, value) => `${directive}:${value}`
            };

            this.init();
        }

        init() {
            this.buildHTML();
            this.bindEvents();
            this.render();
        }

        buildHTML() {
            this.$container = $('<div class="robots-tag-selector-container"></div>');
            this.$tagsContainer = $('<div class="robots-tag-selector-tags"></div>');
            this.$inputWrapper = $('<div class="robots-tag-selector-input-wrapper"></div>');
            this.$input = $('<input type="text" class="robots-tag-selector-input">');
            this.$dropdown = $('<div class="robots-tag-selector-dropdown"></div>');

            this.$input.attr('placeholder', this.options.placeholder);

            this.$inputWrapper.append(this.$input);
            this.$tagsContainer.append(this.$inputWrapper);
            this.$container.append(this.$tagsContainer);
            this.$element.append(this.$container);
            this.$element.append(this.$dropdown);
        }

        bindEvents() {
            // Container click - focus input
            this.$container.on('click', (e) => {
                if (!$(e.target).hasClass('robots-tag-selector-tag-remove')) {
                    this.$input.focus();
                }
            });

            // Input events
            this.$input.on('focus', () => {
                this.openDropdown();
            });

            this.$input.on('input', () => {
                this.currentInput = this.$input.val();
                this.updateDropdown();
            });

            this.$input.on('keydown', (e) => {
                this.handleKeydown(e);
            });

            // Close dropdown on outside click
            $(document).on('click', (e) => {
                if (!this.$element.is(e.target) && this.$element.has(e.target).length === 0) {
                    this.closeDropdown();
                }
            });

            // Tag removal
            this.$element.on('click', '.robots-tag-selector-tag-remove', (e) => {
                const $tag = $(e.target).closest('.robots-tag-selector-tag');
                const value = $tag.data('value');
                const botName = $tag.data('bot-name') || '';
                this.removeTag(value, botName);
            });

            // Option selection
            this.$element.on('click', '.robots-tag-selector-option:not(.disabled)', (e) => {
                e.stopPropagation();
                const $option = $(e.target).closest('.robots-tag-selector-option');

                // Don't recreate if already showing input form
                if ($option.hasClass('with-input')) {
                    return;
                }

                const value = $option.data('value');
                const directive = this.findDirective(value);

                if (directive && directive.hasInput) {
                    this.showInputForDirective(directive, $option);
                } else {
                    this.addTag(value);
                }
            });

            // Custom tag creation
            this.$element.on('click', '.robots-tag-selector-create-custom', (e) => {
                e.stopPropagation();
                const customValue = this.currentInput.trim();
                if (customValue) {
                    this.addTag(customValue, true);
                }
            });
        }

        handleKeydown(e) {
            const key = e.key;

            if (key === 'Enter') {
                e.preventDefault();
                const $firstOption = this.$dropdown.find('.robots-tag-selector-option:not(.disabled):first');
                if ($firstOption.length) {
                    $firstOption.click();
                } else if (this.options.allowCustom && this.currentInput.trim()) {
                    this.addTag(this.currentInput.trim(), true);
                }
            } else if (key === 'Backspace' && !this.currentInput) {
                e.preventDefault();
                if (this.selectedTags.length > 0) {
                    this.removeTag(this.selectedTags[this.selectedTags.length - 1].value);
                }
            } else if (key === 'Escape') {
                this.closeDropdown();
            }
        }

        openDropdown() {
            this.isOpen = true;
            this.$container.addClass('focused');
            this.updateDropdown();
        }

        closeDropdown() {
            this.isOpen = false;
            this.$container.removeClass('focused');
            this.$dropdown.removeClass('show');
        }

        updateDropdown() {
            if (!this.isOpen) return;

            const searchTerm = this.currentInput.toLowerCase();
            const $content = $('<div></div>');

            // Get all directives
            const allDirectives = this.getAllDirectives();
            const filtered = allDirectives.filter(d => {
                if (this.isTagSelected(d.value)) return false;
                if (!searchTerm) return true;
                return d.label.toLowerCase().includes(searchTerm) ||
                    (d.description && d.description.toLowerCase().includes(searchTerm));
            });

            if (filtered.length > 0) {
                // Group by category
                Object.keys(this.options.directives).forEach(category => {
                    const categoryDirectives = filtered.filter(d =>
                        this.options.directives[category].some(cd => cd.value === d.value)
                    );

                    if (categoryDirectives.length > 0) {
                        const $group = $('<div class="robots-tag-selector-option-group"></div>');
                        const $label = $('<div class="robots-tag-selector-option-group-label"></div>').text(
                            category.charAt(0).toUpperCase() + category.slice(1)
                        );
                        $group.append($label);

                        categoryDirectives.forEach(directive => {
                            $group.append(this.createOptionElement(directive));
                        });

                        $content.append($group);
                    }
                });
            } else if (!searchTerm) {
                $content.append(
                    $('<div class="robots-tag-selector-no-results"></div>').text($t('No available directives'))
                );
            }

            // Add custom tag creation option
            if (this.options.allowCustom && searchTerm && !this.isTagSelected(searchTerm)) {
                const $createCustom = $('<div class="robots-tag-selector-create-custom"></div>');
                $createCustom.html('<span class="robots-tag-selector-create-custom-icon">+</span> Create custom: <strong>' + this.escapeHtml(searchTerm) + '</strong>');
                $content.append($createCustom);
            }

            this.$dropdown.html($content);
            this.$dropdown.addClass('show');
        }

        createOptionElement(directive) {
            const $option = $('<div class="robots-tag-selector-option"></div>');
            $option.data('value', directive.value);

            if (this.isTagSelected(directive.value)) {
                $option.addClass('disabled selected');
            }

            const $main = $('<div class="robots-tag-selector-option-main"></div>').text(directive.label);
            $option.append($main);

            if (directive.description) {
                const $desc = $('<div class="robots-tag-selector-option-description"></div>').text(directive.description);
                $option.append($desc);
            }

            return $option;
        }

        showInputForDirective(directive, $option) {
            $option.addClass('with-input');
            $option.empty();

            const $form = $('<div></div>');
            const $label = $('<div class="robots-tag-selector-option-main"></div>').text(directive.label);
            $form.append($label);

            let $inputField;

            if (directive.inputType === 'select') {
                $inputField = $('<select></select>');
                directive.inputOptions.forEach(opt => {
                    $inputField.append($('<option></option>').val(opt).text(opt));
                });
            } else if (directive.inputType === 'datetime') {
                $inputField = $('<input type="text">');
                $inputField.attr('placeholder', directive.inputPlaceholder || '');
            } else {
                $inputField = $('<input type="' + (directive.inputType || 'text') + '">');
                $inputField.attr('placeholder', directive.inputPlaceholder || '');
                if (directive.inputType === 'number') {
                    $inputField.attr('min', directive.min || 0);
                }
            }

            const $button = $('<button type="button">' + $t('Add') + '</button>');

            $form.append($inputField);
            $form.append($button);
            $option.append($form);

            $inputField.focus();

            const addWithValue = () => {
                const inputValue = $inputField.val().trim();
                if (inputValue) {
                    let formattedValue;
                    if (directive.formatter && this.formatters[directive.formatter]) {
                        formattedValue = this.formatters[directive.formatter](directive.value, inputValue);
                    } else if (typeof directive.format === 'function') {
                        // Legacy support for direct function (if passed correctly somehow)
                        formattedValue = directive.format(inputValue);
                    } else {
                        // Default fallback: value:input
                        formattedValue = `${directive.value}:${inputValue}`;
                    }
                    this.addTag(formattedValue, false, directive.value);
                    $option.remove();
                    this.updateDropdown();
                }
            };

            $button.on('click', addWithValue);
            $inputField.on('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addWithValue();
                }
            });
        }

        addTag(value, isCustom = false, baseDirective = null) {
            // Parse bot name from value if present (format: "botname:directive")
            let botName = '';
            let directiveValue = value;

            if (this.options.enableBotNames && value.indexOf(':') > 0 && !value.match(/^(max-snippet|max-image-preview|max-video-preview|unavailable_after):/)) {
                const parts = value.split(':', 2);
                botName = parts[0].trim();
                directiveValue = parts[1].trim();
            }

            // Check if already exists
            if (this.isTagSelected(directiveValue, botName)) {
                return;
            }

            // Check max tags
            if (this.options.maxTags && this.selectedTags.length >= this.options.maxTags) {
                return;
            }

            // Resolve conflicts
            // Find directive definition
            const directiveDef = this.findDirective(baseDirective || directiveValue);
            if (directiveDef && directiveDef.conflicts) {
                directiveDef.conflicts.forEach(conflict => {
                    // Check if conflicting tag is selected for the SAME bot (or global)
                    if (this.isTagSelected(conflict, botName)) {
                        this.removeTag(conflict, botName);
                    }
                });
            }

            const tag = {
                value: directiveValue,
                botName: botName,
                isCustom: isCustom,
                baseDirective: baseDirective
            };

            this.selectedTags.push(tag);
            this.currentInput = '';
            this.$input.val('');

            this.render();
            this.updateDropdown();
            this.triggerChange();

            if (this.options.onAdd) {
                this.options.onAdd.call(this, tag);
            }
        }

        removeTag(value, botName = '') {
            const index = this.selectedTags.findIndex(tag =>
                tag.value === value && (tag.botName || '') === botName
            );
            if (index > -1) {
                const removedTag = this.selectedTags.splice(index, 1)[0];
                this.render();
                this.updateDropdown();
                this.triggerChange();

                if (this.options.onRemove) {
                    this.options.onRemove.call(this, removedTag);
                }
            }
        }

        isTagSelected(value, botName = '') {
            return this.selectedTags.some(tag =>
                tag.value === value && (tag.botName || '') === botName
            );
        }

        render() {
            // Clear existing tags
            this.$tagsContainer.find('.robots-tag-selector-tag').remove();

            // Render tags
            this.selectedTags.forEach(tag => {
                const $tag = this.createTagElement(tag);
                this.$inputWrapper.before($tag);
            });
        }

        createTagElement(tag) {
            const $tag = $('<div class="robots-tag-selector-tag"></div>');
            $tag.data('value', tag.value);
            $tag.data('bot-name', tag.botName || '');

            const directive = this.findDirective(tag.baseDirective || tag.value);
            if (directive && directive.description) {
                $tag.attr('data-tooltip', directive.description);
            }

            if (tag.isCustom) {
                $tag.addClass('custom');
            } else if (tag.baseDirective) {
                $tag.addClass('with-value');
            }

            // Add bot name section if enabled
            if (this.options.enableBotNames) {
                const $botNameSection = $('<div class="robots-tag-bot-section"></div>');
                const $botNameDisplay = $('<span class="robots-tag-bot-display"></span>');
                const $botNameInput = $('<input type="text" class="robots-tag-bot-input" placeholder="all bots">');

                // Set initial values
                if (tag.botName) {
                    $botNameDisplay.text(tag.botName);
                    $botNameInput.val(tag.botName);
                    $botNameSection.addClass('has-bot');
                } else {
                    $botNameDisplay.text($t('all bots'));
                }

                $botNameSection.append($botNameDisplay);
                $botNameSection.append($botNameInput);

                // Click to edit
                $botNameDisplay.on('click', (e) => {
                    e.stopPropagation();
                    $botNameSection.addClass('editing');
                    $botNameInput.focus();
                    $botNameInput.select();
                });

                // Save on blur or enter
                const saveBotName = () => {
                    const newBotName = $botNameInput.val().trim();
                    tag.botName = newBotName;
                    $tag.data('bot-name', newBotName);

                    if (newBotName) {
                        $botNameDisplay.text(newBotName);
                        $botNameSection.addClass('has-bot');
                    } else {
                        $botNameDisplay.text($t('all bots'));
                        $botNameSection.removeClass('has-bot');
                    }

                    $botNameSection.removeClass('editing');
                    this.triggerChange();
                };

                $botNameInput.on('blur', saveBotName);
                $botNameInput.on('keydown', (e) => {
                    e.stopPropagation();
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveBotName();
                    } else if (e.key === 'Escape') {
                        $botNameInput.val(tag.botName || '');
                        $botNameSection.removeClass('editing');
                    }
                });
                $botNameInput.on('click', (e) => {
                    e.stopPropagation();
                });

                $tag.append($botNameSection);
                $tag.append($('<span class="robots-tag-separator">:</span>'));
            }

            const $text = $('<span class="robots-tag-selector-tag-text"></span>').text(tag.value);
            const $remove = $('<span class="robots-tag-selector-tag-remove">×</span>');

            $tag.append($text);
            $tag.append($remove);

            return $tag;
        }

        getAllDirectives() {
            const all = [];
            Object.values(this.options.directives).forEach(category => {
                all.push(...category);
            });
            return all;
        }

        findDirective(value) {
            return this.getAllDirectives().find(d => d.value === value);
        }

        triggerChange() {
            if (this.options.onChange) {
                this.options.onChange.call(this, this.getValue());
            }
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Public methods
        getValue() {
            return this.selectedTags.map(tag => {
                if (this.options.enableBotNames && tag.botName) {
                    return tag.botName + ':' + tag.value;
                }
                return tag.value;
            });
        }

        setValue(values) {
            this.clear();
            if (Array.isArray(values)) {
                values.forEach(value => {
                    this.addTag(value);
                });
            }
        }

        clear() {
            this.selectedTags = [];
            this.render();
            this.triggerChange();
        }

        disable() {
            this.$container.addClass('disabled');
            this.$input.prop('disabled', true);
        }

        enable() {
            this.$container.removeClass('disabled');
            this.$input.prop('disabled', false);
        }

        destroy() {
            this.$element.empty();
            $.removeData(this.element, 'robotsTagSelector');
        }
    }
});
