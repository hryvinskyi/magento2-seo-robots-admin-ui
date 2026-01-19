<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field\Renderer\DirectiveMultiselect;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class RobotsTagSelectorField extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var DirectiveMultiselect $renderer */
        $renderer = $this->getLayout()->createBlock(DirectiveMultiselect::class);

        $renderer->setElement($element);
        $renderer->setInputName($element->getName());
        $renderer->setInputId($element->getHtmlId());
        $renderer->setValue($element->getValue());
        $renderer->setOptions($element->getValues());

        // Check for custom config to enable bot names if needed
        if ($element->getFieldConfig('enable_bot_names')) {
            $renderer->setEnableBotNames((bool) $element->getFieldConfig('enable_bot_names'));
        }

        return $renderer->toHtml();
    }
}
