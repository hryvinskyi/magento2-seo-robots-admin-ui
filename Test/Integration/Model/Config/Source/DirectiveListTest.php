<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Test\Integration\Model\Config\Source;

use Hryvinskyi\SeoRobotsAdminUi\Model\Config\Source\DirectiveList;
use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class DirectiveListTest extends TestCase
{
    private ?DirectiveList $directiveList = null;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->directiveList = $objectManager->create(DirectiveList::class);
    }

    protected function tearDown(): void
    {
        $this->directiveList = null;
    }

    public function testToOptionArrayReturnsArray(): void
    {
        $result = $this->directiveList->toOptionArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testToOptionArrayHasCorrectStructure(): void
    {
        $result = $this->directiveList->toOptionArray();

        foreach ($result as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertIsString($option['value']);
            $this->assertNotEmpty($option['label']);
        }
    }

    public function testToOptionArrayContainsBasicDirectives(): void
    {
        $result = $this->directiveList->toOptionArray();
        $values = array_column($result, 'value');

        $expectedDirectives = [
            RobotsListInterface::DIRECTIVE_INDEX,
            RobotsListInterface::DIRECTIVE_NOINDEX,
            RobotsListInterface::DIRECTIVE_FOLLOW,
            RobotsListInterface::DIRECTIVE_NOFOLLOW,
            RobotsListInterface::DIRECTIVE_NOARCHIVE,
            RobotsListInterface::DIRECTIVE_NOSNIPPET,
            RobotsListInterface::DIRECTIVE_NOTRANSLATE,
            RobotsListInterface::DIRECTIVE_NOIMAGEINDEX,
            RobotsListInterface::DIRECTIVE_NONE,
            RobotsListInterface::DIRECTIVE_ALL,
        ];

        foreach ($expectedDirectives as $directive) {
            $this->assertContains($directive, $values, "Directive '$directive' should be in the options");
        }
    }

    public function testToOptionArrayLabelsAreUppercase(): void
    {
        $result = $this->directiveList->toOptionArray();

        foreach ($result as $option) {
            $this->assertEquals(
                strtoupper($option['value']),
                $option['label'],
                "Label should be uppercase version of value"
            );
        }
    }

    public function testToOptionArrayValuesAreLowercase(): void
    {
        $result = $this->directiveList->toOptionArray();

        foreach ($result as $option) {
            $this->assertEquals(
                strtolower($option['value']),
                $option['value'],
                "Value should be lowercase"
            );
        }
    }

    public function testToOptionArrayHasExpectedCount(): void
    {
        $result = $this->directiveList->toOptionArray();

        $this->assertCount(10, $result, "Should have 10 basic directives");
    }
}
