<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Model\Config\Backend;

use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class RobotsRules extends ArraySerialized
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
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
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
            unset($value['__empty']);

            foreach ($value as $key => $rule) {
                if (is_int($key)) {
                    $key = '_rule' . (string)$key;
                }

                // Ensure arrays for directives
                $metaDirectives = $rule['meta_directives'] ?? [];
                $xrobotsDirectives = $rule['xrobots_directives'] ?? [];

                // Convert to arrays if needed (from multiselect they should already be arrays)
                if (!is_array($metaDirectives)) {
                    $metaDirectives = $metaDirectives ? [$metaDirectives] : [];
                }
                if (!is_array($xrobotsDirectives)) {
                    $xrobotsDirectives = $xrobotsDirectives ? [$xrobotsDirectives] : [];
                }

                // Validate meta directives
                if (!empty($metaDirectives)) {
                    $validation = $this->robotsList->validateDirectives($metaDirectives);
                    if (!$validation['valid']) {
                        throw new LocalizedException(
                            __('Invalid meta directives for pattern "%1": %2',
                                $rule['pattern'] ?? '',
                                implode(', ', $validation['errors'])
                            )
                        );
                    }
                }

                // Validate X-Robots directives
                if (!empty($xrobotsDirectives)) {
                    $validation = $this->robotsList->validateDirectives($xrobotsDirectives);
                    if (!$validation['valid']) {
                        throw new LocalizedException(
                            __('Invalid X-Robots directives for pattern "%1": %2',
                                $rule['pattern'] ?? '',
                                implode(', ', $validation['errors'])
                            )
                        );
                    }
                }

                $value[$key]['meta_directives'] = $metaDirectives;
                $value[$key]['xrobots_directives'] = $xrobotsDirectives;

                // Ensure priority is set
                $value[$key]['priority'] = isset($rule['priority']) ? (int)$rule['priority'] : 0;
            }

            // Sort by priority (highest first)
            usort($value, function ($a, $b) {
                return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
            });
        }

        $this->setValue($value);
        return parent::beforeSave();
    }
}
