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
     * Normalizes the given value.
     *
     * @param mixed $value The value to be normalized.
     * @return mixed The normalized value.
     */
    private function normalizeValue($value)
    {
        if (is_array($value)) {
            uasort($value, [$this, 'sortAlphabet']);
            uasort($value, [$this, 'sortCondition']);
        }

        return $value;
    }

    /**
     * Sorts an array of elements alphabetically based on the 'pattern' key.
     *
     * @param array $elem1 The first element to compare.
     * @param array $elem2 The second element to compare.
     * @return int Returns a negative, zero, or positive integer depending on whether the first element is less than,
     *             equal to, or greater than the second element.
     */
    private function sortAlphabet($elem1, $elem2)
    {
        return $elem1['pattern'] <=> $elem2['pattern'];
    }

    /**
     * Sorts two elements based on their pattern length and pattern occurrence in reversed form.
     *
     * @param array $elem1 The first element to compare.
     * @param array $elem2 The second element to compare.
     * @return int Returns -1 if the pattern of $elem1 appears in reversed form in $elem2, returns the comparison
     *             between the length of $elem2's pattern and $elem1's pattern if they have different lengths, or
     *             returns the comparison between $elem1's pattern and $elem2's pattern if none of the conditions above apply.
     */
    private function sortCondition($elem1, $elem2)
    {
        $lengthComparison = strlen($elem2['pattern']) <=> strlen($elem1['pattern']);

        if ($lengthComparison !== 0) {
            return $lengthComparison;
        }

        $elem1Replaced = str_replace(['/', '*'], [' ', ''], $elem1['pattern']);
        $elem2Replaced = str_replace(['/', '*'], [' ', ''], $elem2['pattern']);

        if (strrpos($elem2Replaced, $elem1Replaced) !== false) {
            return -1;
        }

        return $elem1Replaced <=> $elem2Replaced;
    }
}
