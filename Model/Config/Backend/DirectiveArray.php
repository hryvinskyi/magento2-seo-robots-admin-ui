<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Model\Config\Backend;

use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class DirectiveArray extends Value
{
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly RobotsListInterface $robotsList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Process data before save
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (is_array($value)) {
            // Validate directives
            $validation = $this->robotsList->validateDirectives($value);
            if (!$validation['valid']) {
                throw new LocalizedException(
                    __('Invalid directives: %1', implode(', ', $validation['errors']))
                );
            }

            // Encode as JSON
            $this->setValue(json_encode($value));
        }

        return parent::beforeSave();
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();

        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            $this->setValue(is_array($decoded) ? $decoded : []);
        } elseif (empty($value)) {
            $this->setValue([]);
        }

        parent::_afterLoad();
    }
}
