<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Model\Config\Source;

use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\Framework\Data\OptionSourceInterface;

class DirectiveList implements OptionSourceInterface
{
    public function __construct(private readonly RobotsListInterface $robotsList)
    {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $directives = $this->robotsList->getBasicDirectives();
        $options = [];

        foreach ($directives as $directive) {
            $options[] = [
                'value' => $directive,
                'label' => strtoupper($directive)
            ];
        }

        return $options;
    }
}
