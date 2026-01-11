<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Multiselect renderer for robots directives using custom RobotsTagSelector
 */
class DirectiveMultiselect extends Select
{
    private bool $enableBotNames = false;

    public function __construct(
        Context $context,
        private readonly RobotsListInterface $robotsList,
        private readonly SecureHtmlRenderer $secureHtmlRenderer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Enable bot name per directive
     *
     * @param bool $enable
     * @return $this
     */
    public function setEnableBotNames(bool $enable)
    {
        $this->enableBotNames = $enable;
        return $this;
    }

    /**
     * Set input name
     *
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value)
    {
        return $this->setData('name', $value . '[]');
    }

    /**
     * Set input id
     *
     * @param string $value
     * @return $this
     */
    public function setInputId(string $value)
    {
        return $this->setId($value);
    }

    /**
     * Render HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $basicDirectives = $this->robotsList->getBasicDirectives();
            $options = [];

            foreach ($basicDirectives as $directive) {
                $options[] = [
                    'value' => $directive,
                    'label' => strtoupper($directive)
                ];
            }
            $this->setOptions($options);
        }

        // Add class for identification and hide the original select
        $this->setClass('directive-multiselect-hidden');
        $this->setData('style', 'display:none !important');
        $this->setExtraParams('multiple="multiple"');

        $html = parent::_toHtml();

        // Add extra CSS to ensure it's hidden overriding any inline styles if necessary
        $html .= $this->secureHtmlRenderer->renderTag('style', [], '.directive-multiselect-hidden { display: none !important; }', false);

        // Add container for the custom selector
        // We use a wrapper div that will be populated by the plugin
        $containerId = $this->getId() . '_container';

        $html .= '<div id="' . $containerId . '" class="robots-tag-selector-wrapper" data-enable-bot-names="' . ($this->enableBotNames ? 'true' : 'false') . '"></div>';

        $html .= $this->getInitScript();

        return $html;
    }

    /**
     * @var array
     */
    private $directives = [];

    /**
     * Set directives configuration
     *
     * @param array $directives
     * @return $this
     */
    public function setDirectives(array $directives)
    {
        $this->directives = $directives;
        return $this;
    }

    /**
     * Get Initialization Script
     *
     * @return string
     */
    private function getInitScript(): string
    {
        $directivesJson = json_encode($this->directives ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        return $this->secureHtmlRenderer->renderTag(
            'script',
            [],
            <<<JS
require(['jquery', 'robotsTagSelector'], function($) {
    $(document).ready(function() {
        var directives = $directivesJson;
        
        var initSelector = function(selectElement) {
            var \$select = $(selectElement);

            // Avoid double initialization
            if (\$select.data('initialized')) {
                return;
            }

            var \$wrapper = \$select.next('.robots-tag-selector-wrapper');
            if (!\$wrapper.length) {
                // If wrapper is not immediately next (e.g. some magento styling), try to find by ID
                var wrapperId = \$select.attr('id') + '_container';
                \$wrapper = $('#' + wrapperId);
            }

            if (!\$wrapper.length) {
                // Create wrapper if missing
                \$wrapper = $('<div class="robots-tag-selector-wrapper"></div>');
                \$select.after(\$wrapper);
            }

            // Check if bot names per directive are enabled
            var enableBotNames = \$wrapper.data('enable-bot-names') === 'true' || \$wrapper.attr('data-enable-bot-names') === 'true';

            // Get initial values from data-init-value attribute
            var initialValues = [];

            \$select.find('option:selected').each(function() {
              initialValues.push($(this).val());
            });
            
            // Initialize the plugin on the wrapper
            var options = {
                placeholder: 'Add directives...',
                enableBotNames: enableBotNames,
                onChange: function(values) {
                    // 1. Clear current selection
                    \$select.find('option').prop('selected', false);

                    // 2. Process each new value
                    values.forEach(function(val) {
                        var \$option = \$select.find('option[value="' + val + '"]');

                        if (\$option.length) {
                            // Select existing option
                            \$option.prop('selected', true);
                        } else {
                            // Create new option for custom tags/values (with bot name if included)
                            \$select.append(
                                $('<option></option>').val(val).text(val).prop('selected', true)
                            );
                        }
                    });

                    // Trigger change on select to notify other listeners (if any)
                    \$select.trigger('change');
                }
            };

            if (directives && Object.keys(directives).length > 0) {
                options.directives = directives;
            }

            \$wrapper.robotsTagSelector(options);

            // Set initial values in the plugin
            if (initialValues && initialValues.length > 0) {
                // Use a short timeout to ensure the plugin is fully initialized
                setTimeout(function() {
                    \$wrapper.robotsTagSelector('setValue', initialValues);
                }, 50);
            }

            \$select.data('initialized', true);
        };

        // Initialize all existing instances
        $('.directive-multiselect-hidden').each(function() {
            initSelector(this);
        });

        // Handle dynamic rows addition
        $(document).on('contentUpdated', function() {
            $('.directive-multiselect-hidden').each(function() {
                initSelector(this);
            });
        });
        
        // Use MutationObserver as a backup for dynamic rows
        var observer = new MutationObserver(function(mutations) {
            var shouldCheck = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    shouldCheck = true;
                }
            });
            
            if (shouldCheck) {
                $('.directive-multiselect-hidden').each(function() {
                    initSelector(this);
                });
            }
        });
        
        // Observe config form
        var configForm = document.querySelector('#config-edit-form');
        if (configForm) {
            observer.observe(configForm, { childList: true, subtree: true });
        }
        
        $('.field-array').each(function() {
            observer.observe(this, { childList: true, subtree: true });
        });
    });
});
JS,
    false
);
    }
}
