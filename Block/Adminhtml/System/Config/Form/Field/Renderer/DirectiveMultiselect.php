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
 * Renderer for robots directives using custom RobotsTagSelector with JSON storage
 */
class DirectiveMultiselect extends Select
{
    private bool $enableBotNames = false;
    private array $directives = [];

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
        // Ensure we don't use array syntax correctly for single JSON string field
        if (str_ends_with($value, '[]')) {
            $value = substr($value, 0, -2);
        }
        return $this->setData('name', $value);
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
     * Render HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        // Prepare initial value
        $values = $this->getValue();
        if (is_array($values)) {
            $valueData = json_encode($values);
        } elseif (is_string($values)) {
            // Check if it looks like JSON
            $decoded = json_decode($values);
            if (json_last_error() === JSON_ERROR_NONE) {
                $valueData = $values;
            } else {
                // Legacy CSV or raw string handling if needed
                $valueData = json_encode([$values]);
            }
        } else {
            $valueData = '[]';
        }

        $textareaId = $this->getId();
        $textareaName = $this->getName();
        $directivesJson = json_encode($this->directives ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        // Detect if this is a template row for dynamic rows
        $isTemplateRow = strpos($textareaName, '<%- _id %>') !== false;

        // For template rows, don't escape the ID/name as they contain underscore.js template syntax
        // that needs to be preserved for Magento's dynamic row functionality
        if ($isTemplateRow) {
            $escapedId = $textareaId;
            $escapedName = $textareaName;
        } else {
            $escapedId = $this->escapeHtmlAttr($textareaId);
            $escapedName = $this->escapeHtmlAttr($textareaName);
        }

        $element = $this->getData('element');
        $disabled = '';

        if ($element) {
            $disabled = $this->getElement()->getDisabled() ? ' disabled="disabled" readonly="1"' : '';
        }

        // Render hidden textarea with configuration attached as data attribute
        // Note: template rows are skipped in JavaScript by checking for <%- _id %> in name
        return sprintf(
            '<div class="directive-multiselect-container">' .
            '<textarea id="%s" name="%s" %s style="display:none !important;" class="directive-multiselect-json" data-directives=\'%s\' data-enable-bot-names=\'%s\'>%s</textarea>' .
            '</div>',
            $escapedId,
            $escapedName,
            $disabled,
            $directivesJson,
            $this->enableBotNames ? 'true' : 'false',
            $this->escapeHtml($valueData)
        );
    }
}
