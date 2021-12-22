<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized as SerializedArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;

class ArraySerialized extends SerializedArraySerialized
{
    /**
     * @var SerializerInterface
     */
    private $serialize;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serialize
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serialize,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );

        $this->serialize = $serialize;
    }

    /**
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if (is_string($value)) {
            $this->setValue(empty($value) ? false : $this->serialize->unserialize($value));
        }
    }

    /**
     * @return SerializedArraySerialized
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (is_array($value)) {
            unset($value['__empty']);
        }

        if ($this->getField() === 'meta_robots') {
            $value = $this->normalizeValue($value);
        }

        $this->setValue($value);

        return parent::beforeSave();
    }

    /**
     * @param array|string $value
     *
     * @return array|string
     */
    private function normalizeValue($value)
    {
        $sortAlphabet = static function ($elem1, $elem2) {
            return $elem1['pattern'] > $elem2['pattern'];
        };

        $sortCondition = static function ($elem1, $elem2) {
            return strlen($elem2['pattern']) > strlen($elem1['pattern'])
                && strrpos(
                    str_replace(['/', '*'], [' ', ''], $elem2['pattern']),
                    str_replace(['/', '*'], [' ', ''], $elem1['pattern'])
                ) !== false;
        };

        if (is_array($value)) {
            uasort($value, $sortAlphabet);
            uasort($value, $sortCondition);
        }

        return $value;
    }
}
