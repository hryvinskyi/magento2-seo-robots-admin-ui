/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Robots Tag Selector jQuery Plugin
 * Manages structured robot directives with {value, bot, modification} format
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    // Default directives configuration
    const DEFAULT_DIRECTIVES = {
        indexing: [
            {
                value: 'all',
                label: 'all',
                description: $t('No restrictions for indexing or serving'),
                conflicts: ['noindex', 'nofollow', 'none']
            },
            {
                value: 'index',
                label: 'index',
                description: $t('Allow indexing'),
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
                hasModification: true,
                modificationType: 'number',
                modificationPlaceholder: $t('Character count')
            },
            {
                value: 'max-image-preview',
                label: 'max-image-preview',
                description: $t('Maximum size of image preview'),
                hasModification: true,
                modificationType: 'select',
                modificationOptions: ['none', 'standard', 'large']
            },
            {
                value: 'max-video-preview',
                label: 'max-video-preview',
                description: $t('Maximum video preview duration in seconds'),
                hasModification: true,
                modificationType: 'number',
                modificationPlaceholder: $t('Seconds (-1 for no limit)')
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
                hasModification: true,
                modificationType: 'datetime',
                modificationPlaceholder: 'YYYY-MM-DD'
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

        return this.each(function () {
            if (!$.data(this, 'robotsTagSelector')) {
                $.data(this, 'robotsTagSelector', new RobotsTagSelector(this, options));
            }
        });
    };

    /**
     * Main RobotsTagSelector class
     */
    class RobotsTagSelector {
        constructor(element, options) {
            this.element = element;
            this.$element = $(element);

            this.options = $.extend({
                placeholder: $t('Select directives...'),
                maxTags: null,
                allowCustom: true,
                directives: DEFAULT_DIRECTIVES,
                onChange: null,
                enableBotNames: false
            }, options);

            // Store structured tags: [{value, bot, modification}, ...]
            this.selectedTags = [];
            this.isOpen = false;
            this.currentInput = '';

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
            this.$container.on('click', (e) => {
                if (this.$container.hasClass('disabled')) {
                    return;
                }
                if (!$(e.target).hasClass('robots-tag-selector-tag-remove')) {
                    this.$input.focus();
                }
            });

            this.$input.on('focus', () => this.openDropdown());
            this.$input.on('input', () => {
                this.currentInput = this.$input.val();
                this.updateDropdown();
            });
            this.$input.on('keydown', (e) => this.handleKeydown(e));

            $(document).on('click', (e) => {
                if (!this.$element.is(e.target) && this.$element.has(e.target).length === 0) {
                    this.closeDropdown();
                }
            });

            this.$element.on('click', '.robots-tag-selector-tag-remove', (e) => {
                const $tag = $(e.target).closest('.robots-tag-selector-tag');
                const index = $tag.data('index');
                this.removeTagByIndex(index);
            });

            this.$element.on('click', '.robots-tag-selector-option:not(.disabled)', (e) => {
                e.stopPropagation();
                const $option = $(e.target).closest('.robots-tag-selector-option');

                if ($option.hasClass('with-input')) return;

                const value = $option.data('value');
                const directive = this.findDirective(value);

                if (directive && directive.hasModification) {
                    this.showModificationInput(directive, $option);
                } else {
                    this.addTag({ value: value, bot: '', modification: '' });
                }
            });

            this.$element.on('click', '.robots-tag-selector-create-custom', (e) => {
                e.stopPropagation();
                const customValue = this.currentInput.trim();
                if (customValue) {
                    this.addTag({ value: customValue, bot: '', modification: '' }, true);
                }
            });
        }

        handleKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $firstOption = this.$dropdown.find('.robots-tag-selector-option:not(.disabled):first');
                if ($firstOption.length) {
                    $firstOption.click();
                } else if (this.options.allowCustom && this.currentInput.trim()) {
                    this.addTag({ value: this.currentInput.trim(), bot: '', modification: '' }, true);
                }
            } else if (e.key === 'Backspace' && !this.currentInput) {
                e.preventDefault();
                if (this.selectedTags.length > 0) {
                    this.removeTagByIndex(this.selectedTags.length - 1);
                }
            } else if (e.key === 'Escape') {
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

            const allDirectives = this.getAllDirectives();
            const filtered = allDirectives.filter(d => {
                if (this.isValueSelected(d)) return false;
                if (!searchTerm) return true;
                return d.label.toLowerCase().includes(searchTerm) ||
                    (d.description && d.description.toLowerCase().includes(searchTerm));
            });

            if (filtered.length > 0) {
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

            if (this.options.allowCustom && searchTerm && !this.isValueSelected(searchTerm)) {
                const $createCustom = $('<div class="robots-tag-selector-create-custom"></div>');
                $createCustom.html('<span class="robots-tag-selector-create-custom-icon">+</span> ' +
                    $t('Create custom:') + ' <strong>' + this.escapeHtml(searchTerm) + '</strong>');
                $content.append($createCustom);
            }

            this.$dropdown.html($content);
            this.$dropdown.addClass('show');
        }

        createOptionElement(directive) {
            const $option = $('<div class="robots-tag-selector-option" data-value="' + directive.value + '"></div>');
            if (this.isValueSelected(directive.value)) {
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

        showModificationInput(directive, $option) {
            $option.addClass('with-input');
            $option.empty();

            const $form = $('<div class="robots-tag-input-form"></div>');
            const $label = $('<div class="robots-tag-selector-option-main"></div>').text(directive.label);
            $form.append($label);

            let $inputField;
            let $dateInput, $timeInput; // For datetime type
            let getValueFn; // Custom function to get the value

            if (directive.modificationType === 'select') {
                $inputField = $('<select class="robots-tag-modification-input"></select>');
                directive.modificationOptions.forEach(opt => {
                    $inputField.append($('<option></option>').val(opt).text(opt));
                });
                getValueFn = () => $inputField.val().trim();
            } else if (directive.modificationType === 'datetime') {
                // Create date and optional time inputs for datetime type (ISO 8601 format)
                const $wrapper = $('<div class="robots-tag-datetime-wrapper"></div>');
                $dateInput = $('<input type="date" class="robots-tag-modification-input robots-tag-date-input">');
                $timeInput = $('<input type="time" class="robots-tag-modification-input robots-tag-time-input">');
                $timeInput.attr('placeholder', $t('optional'));

                // Set default date to tomorrow
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                $dateInput.val(tomorrow.toISOString().split('T')[0]);

                $wrapper.append($dateInput);
                $wrapper.append($('<span class="robots-tag-datetime-separator">-</span>'));
                $wrapper.append($timeInput);
                $wrapper.append($('<span class="robots-tag-datetime-hint">' + $t('(time optional)') + '</span>'));
                $inputField = $wrapper;

                // Return ISO 8601 formatted datetime string
                // Example: 2020-09-21 or 2020-09-21T14:30:00
                getValueFn = () => {
                    const date = $dateInput.val();
                    const time = $timeInput.val();
                    if (date) {
                        if (time) {
                            return date + 'T' + time + ':00';
                        }
                        return date;
                    }
                    return '';
                };
            } else {
                $inputField = $('<input type="' + (directive.modificationType || 'text') + '" class="robots-tag-modification-input">');
                $inputField.attr('placeholder', directive.modificationPlaceholder || '');
                if (directive.modificationType === 'number') {
                    $inputField.attr('min', -1);
                }
                getValueFn = () => $inputField.val().trim();
            }

            const $button = $('<button type="button" class="robots-tag-add-btn">' + $t('Add') + '</button>');

            $form.append($inputField);
            $form.append($button);
            $option.append($form);

            // Focus on appropriate input
            if ($dateInput) {
                $dateInput.focus();
            } else {
                $inputField.focus();
            }

            const addWithModification = () => {
                const modValue = getValueFn();
                if (modValue) {
                    this.addTag({
                        value: directive.value,
                        bot: '',
                        modification: modValue
                    });
                    $option.remove();
                    this.updateDropdown();
                }
            };

            $button.on('click', addWithModification);

            // Handle enter key on inputs
            const handleEnter = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addWithModification();
                }
            };

            if ($dateInput) {
                $dateInput.on('keypress', handleEnter);
                $timeInput.on('keypress', handleEnter);
            } else if ($inputField.is('input')) {
                $inputField.on('keypress', handleEnter);
            }
        }

        addTag(tagData, isCustom = false) {
            // Preserve isCustom from tagData, or use parameter, or auto-detect
            const directiveValue = tagData.value || '';
            const detectedCustom = tagData.isCustom !== undefined
                ? tagData.isCustom
                : (isCustom || (directiveValue && !this.findDirective(directiveValue)));

            const tag = {
                value: directiveValue,
                bot: tagData.bot || '',
                modification: tagData.modification || '',
                isCustom: detectedCustom
            };

            if (tag.value === '') return;

            // Check for duplicates
            const isDuplicate = this.selectedTags.some(t =>
                t.value === tag.value && t.bot === tag.bot && t.modification === tag.modification
            );
            if (isDuplicate) return;

            // Check max tags
            if (this.options.maxTags && this.selectedTags.length >= this.options.maxTags) {
                return;
            }

            // Handle conflicts
            const directive = this.findDirective(tag.value);
            if (directive && directive.conflicts) {
                directive.conflicts.forEach(conflict => {
                    const conflictIndex = this.selectedTags.findIndex(t =>
                        t.value === conflict && t.bot === tag.bot
                    );
                    if (conflictIndex > -1) {
                        this.selectedTags.splice(conflictIndex, 1);
                    }
                });
            }

            // Replace existing directive with modification if same value+bot
            if (tag.modification) {
                const existingIndex = this.selectedTags.findIndex(t =>
                    t.value === tag.value && t.bot === tag.bot
                );
                if (existingIndex > -1) {
                    this.selectedTags.splice(existingIndex, 1);
                }
            }

            this.selectedTags.push(tag);
            this.currentInput = '';
            this.$input.val('');

            this.render();
            this.updateDropdown();
            this.triggerChange();
        }

        removeTagByIndex(index) {
            if (index >= 0 && index < this.selectedTags.length) {
                this.selectedTags.splice(index, 1);
                this.render();
                this.updateDropdown();
                this.triggerChange();
            }
        }

        isValueSelected(d) {
            return this.selectedTags.some(t => t.value === d.value && t.bot === '');
        }

        render() {
            this.$tagsContainer.find('.robots-tag-selector-tag').remove();

            this.selectedTags.forEach((tag, index) => {
                const $tag = this.createTagElement(tag, index);
                this.$inputWrapper.before($tag);
            });
        }

        createTagElement(tag, index) {
            const $tag = $('<div class="robots-tag-selector-tag"></div>');
            $tag.data('index', index);

            const directive = this.findDirective(tag.value);
            if (directive && directive.description) {
                $tag.attr('title', directive.description);
            }

            if (tag.isCustom) {
                $tag.addClass('custom');
            }
            if (tag.modification) {
                $tag.addClass('with-value');
            }
            if (tag.bot) {
                $tag.addClass('with-bot');
            }

            // Bot name section (if enabled)
            if (this.options.enableBotNames) {
                const $botSection = $('<div class="robots-tag-bot-section"></div>');
                const $botDisplay = $('<span class="robots-tag-bot-display"></span>');
                const $botInput = $('<input type="text" class="robots-tag-bot-input" placeholder="all bots">');

                if (tag.bot) {
                    $botDisplay.text(tag.bot);
                    $botInput.val(tag.bot);
                    $botSection.addClass('has-bot');
                } else {
                    $botDisplay.text($t('all bots'));
                }

                $botSection.append($botDisplay);
                $botSection.append($botInput);

                $botDisplay.on('click', (e) => {
                    e.stopPropagation();
                    $botSection.addClass('editing');
                    $botInput.focus().select();
                });

                const saveBotName = () => {
                    const newBot = $botInput.val().trim();
                    tag.bot = newBot;

                    if (newBot) {
                        $botDisplay.text(newBot);
                        $botSection.addClass('has-bot');
                        $tag.addClass('with-bot');
                    } else {
                        $botDisplay.text($t('all bots'));
                        $botSection.removeClass('has-bot');
                        $tag.removeClass('with-bot');
                    }

                    $botSection.removeClass('editing');
                    this.triggerChange();
                };

                $botInput.on('blur', saveBotName);
                $botInput.on('keydown', (e) => {
                    e.stopPropagation();
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveBotName();
                    } else if (e.key === 'Escape') {
                        $botInput.val(tag.bot || '');
                        $botSection.removeClass('editing');
                    }
                });
                $botInput.on('click', (e) => e.stopPropagation());

                $tag.append($botSection);
                $tag.append($('<span class="robots-tag-separator">:</span>'));
            }

            // Value display
            let displayText = tag.value;
            if (tag.modification) {
                displayText += ':' + tag.modification;
            }

            const $text = $('<span class="robots-tag-selector-tag-text"></span>').text(displayText);
            const $remove = $('<span class="robots-tag-selector-tag-remove">&times;</span>');

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
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Public API methods

        /**
         * Get current value as array of structured objects
         * @returns {Array<{value: string, bot: string, modification: string}>}
         */
        getValue() {
            return this.selectedTags.map(tag => ({
                value: tag.value,
                bot: tag.bot,
                modification: tag.modification,
                isCustom: tag.isCustom || false
            }));
        }

        /**
         * Set value from array of structured objects
         * @param {Array<{value: string, bot: string, modification: string}>} values
         */
        setValue(values) {
            this.clear();
            if (Array.isArray(values)) {
                values.forEach(v => {
                    if (typeof v === 'object' && v !== null) {
                        this.addTag(v);
                    } else if (typeof v === 'string') {
                        // Legacy string format support
                        this.addTag(this.parseStringToStructured(v));
                    }
                });
            }
        }

        /**
         * Parse legacy string format to structured object
         * @param {string} str
         * @returns {{value: string, bot: string, modification: string}}
         */
        parseStringToStructured(str) {
            const result = { value: '', bot: '', modification: '' };
            const parts = str.split(':');
            const advancedDirectives = ['max-snippet', 'max-image-preview', 'max-video-preview', 'unavailable_after'];

            if (parts.length === 1) {
                result.value = parts[0];
            } else if (parts.length === 2) {
                const firstLower = parts[0].toLowerCase();
                if (advancedDirectives.includes(firstLower)) {
                    result.value = parts[0];
                    result.modification = parts[1];
                } else if (this.findDirective(parts[1])) {
                    result.bot = parts[0];
                    result.value = parts[1];
                } else {
                    result.value = parts[0];
                    result.modification = parts[1];
                }
            } else if (parts.length >= 3) {
                result.bot = parts[0];
                result.value = parts[1];
                result.modification = parts.slice(2).join(':');
            }

            return result;
        }

        clear() {
            this.selectedTags = [];
            this.render();
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
