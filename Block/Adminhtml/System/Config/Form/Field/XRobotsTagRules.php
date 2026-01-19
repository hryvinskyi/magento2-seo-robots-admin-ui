<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field\Renderer\DirectiveMultiselect;
use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Dynamic field array for X-Robots-Tag HTTP header rules configuration
 */
class XRobotsTagRules extends AbstractFieldArray
{
    /**
     * @var DirectiveMultiselect
     */
    private $directiveRenderer;

    public function __construct(
        Context $context,
        private readonly RobotsListInterface $robotsList,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('priority', [
            'label' => __('Priority'),
            'class' => 'validate-number',
            'style' => 'width: 50px'
        ]);

        $this->addColumn('pattern', [
            'label' => __('URL Pattern'),
            'style' => 'width: 250px'
        ]);

        $this->addColumn('xrobots_directives', [
            'label' => __('X-Robots-Tag Directives'),
            'renderer' => $this->getDirectiveRenderer(),
            'style' => 'width: 350px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Rule');
        parent::_construct();
    }

    /**
     * Get directive renderer
     *
     * @return DirectiveMultiselect
     * @throws LocalizedException
     */
    private function getDirectiveRenderer()
    {
        if (!$this->directiveRenderer) {
            $this->directiveRenderer = $this->getLayout()->createBlock(
                DirectiveMultiselect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            // Enable bot names per directive for X-Robots-Tag
            $this->directiveRenderer->setEnableBotNames(true);
            $this->directiveRenderer->setDirectives($this->robotsList->getRobotsDirectives());
        }
        return $this->directiveRenderer;
    }
}
