<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Model\Config\Source;

use Hryvinskyi\SeoRobots\Model\RobotsList;
use Magento\Framework\Data\OptionSourceInterface;

class MetaRobots implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [
            0 => __('Use Robots Meta Header'),
            RobotsList::NOINDEX_FOLLOW => 'NOINDEX, FOLLOW',
            RobotsList::NOINDEX_NOFOLLOW => 'NOINDEX, NOFOLLOW',
            RobotsList::INDEX_FOLLOW => 'INDEX, FOLLOW',
            RobotsList::INDEX_NOFOLLOW => 'INDEX, NOFOLLOW',
        ];
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [];
        foreach ($this->getOptions() as $key => $option) {
            $result[] = ['value' => $key, 'label' => $option];
        }

        return $result;
    }
}
