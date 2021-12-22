<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field\MetaRobotsCell;

use Hryvinskyi\SeoRobotsAdminUi\Model\Config\Source\MetaRobots as SourceMetaRobots;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * @method getIsRenderToJsTemplate()
 */
class MetaRobots extends Select
{
    /**
     * @var SourceMetaRobots
     */
    private $metaRobots;

    /**
     * @param Context $context
     * @param SourceMetaRobots $metaRobots
     * @param array $data
     */
    public function __construct(
        Context $context,
        SourceMetaRobots $metaRobots,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->metaRobots = $metaRobots;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setInputName(string $value): MetaRobots
    {
        $this->setData('name', $value);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setInputId(string $value): MetaRobots
    {
        $this->setId($value);

        return $this;
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->metaRobots->toOptionArray());
        }

        return parent::_toHtml();
    }
}
