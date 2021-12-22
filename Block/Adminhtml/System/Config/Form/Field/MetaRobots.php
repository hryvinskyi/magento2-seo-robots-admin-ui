<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

class MetaRobots extends AbstractFieldArray
{
    /**
     * @var MetaRobotsCell\MetaRobots
     */
    private $optionsRenderer;

    /**
     * @return void
     * @noinspection MagicMethodsValidityInspection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        $this->addColumn('priority', [
            'label' => __('Priority'),
            'style' => 'width: 150px',
        ]);
        $this->addColumn('pattern', [
            'label' => __('URL Pattern'),
            'style' => 'width: 300px',
        ]);
        $select = $this->getOptionsRenderer();

        $this->addColumn('option', [
            'label' => __('Option'),
            'renderer' => $select,
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $option = $row->getData('option');

        if ($option) {
            $options['option_' . $this->getOptionsRenderer()->calcOptionHash($option)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }


    /**
     * @return MetaRobotsCell\MetaRobots
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOptionsRenderer()
    {
        if (!$this->optionsRenderer) {
            $this->optionsRenderer = $this->getLayout()
                ->createBlock(
                    MetaRobotsCell\MetaRobots::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            $this->optionsRenderer->setClass('customer_options_select');
            $this->optionsRenderer->setExtraParams('style="width:150px"');
        }

        return $this->optionsRenderer;
    }
}
