<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

class DirectiveArray extends Value
{
    private Json $serializer;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?Json $serializer = null
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Before save - normalize and encode as JSON
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (empty($value)) {
            $this->setValue(null);
            return parent::beforeSave();
        }

        // Handle JSON string from form
        if (is_string($value)) {
            try {
                $decoded = $this->serializer->unserialize($value);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            } catch (\Exception $e) {
                $this->setValue(null);
                return parent::beforeSave();
            }
        }

        if (!is_array($value)) {
            $this->setValue(null);
            return parent::beforeSave();
        }

        // Normalize to structured format
        $normalized = $this->normalizeDirectives($value);

        if (empty($normalized)) {
            $this->setValue(null);
            return parent::beforeSave();
        }

        $this->setValue($this->serializer->serialize($normalized));

        return parent::beforeSave();
    }

    /**
     * After load - decode JSON
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();

        if ($value !== null && $value !== '') {
            try {
                $decoded = $this->serializer->unserialize($value);
                if (is_array($decoded)) {
                    // Normalize in case old data format
                    $normalized = $this->normalizeDirectives($decoded);
                    $this->setValue($normalized);
                }
            } catch (\Exception $e) {
                $this->setValue([]);
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Normalize directives to structured format
     */
    private function normalizeDirectives(array $value): array
    {
        $normalized = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                // Legacy string format
                $normalized[] = $this->parseStringToStructured($item);
            } elseif (is_array($item)) {
                // Could be ["index"] format or proper structured format
                if (isset($item['value'])) {
                    // Proper structured format
                    $normalized[] = [
                        'value' => trim((string)($item['value'] ?? '')),
                        'bot' => trim((string)($item['bot'] ?? '')),
                        'modification' => trim((string)($item['modification'] ?? '')),
                    ];
                } elseif (isset($item[0])) {
                    // Array format like ["index"] or ["index", "googlebot", ""]
                    $normalized[] = [
                        'value' => trim((string)($item[0] ?? '')),
                        'bot' => trim((string)($item[1] ?? '')),
                        'modification' => trim((string)($item[2] ?? '')),
                    ];
                }
            }
        }

        // Filter out empty values
        return array_filter($normalized, function ($item) {
            return !empty($item['value']);
        });
    }

    /**
     * Parse legacy string format to structured
     */
    private function parseStringToStructured(string $str): array
    {
        $result = ['value' => '', 'bot' => '', 'modification' => ''];
        $parts = explode(':', $str);
        $advancedDirectives = ['max-snippet', 'max-image-preview', 'max-video-preview', 'unavailable_after'];

        if (count($parts) === 1) {
            $result['value'] = $parts[0];
        } elseif (count($parts) === 2) {
            $firstLower = strtolower($parts[0]);
            if (in_array($firstLower, $advancedDirectives, true)) {
                $result['value'] = $parts[0];
                $result['modification'] = $parts[1];
            } else {
                $result['bot'] = $parts[0];
                $result['value'] = $parts[1];
            }
        } elseif (count($parts) >= 3) {
            $result['bot'] = $parts[0];
            $result['value'] = $parts[1];
            $result['modification'] = implode(':', array_slice($parts, 2));
        }

        return $result;
    }
}
