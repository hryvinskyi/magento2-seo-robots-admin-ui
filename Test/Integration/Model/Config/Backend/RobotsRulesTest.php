<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Test\Integration\Model\Config\Backend;

use Hryvinskyi\SeoRobotsAdminUi\Model\Config\Backend\RobotsRules;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class RobotsRulesTest extends TestCase
{
    private ?RobotsRules $model = null;
    private ?Json $serializer = null;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->model = $objectManager->create(RobotsRules::class);
        $this->serializer = $objectManager->get(Json::class);
    }

    protected function tearDown(): void
    {
        $this->model = null;
        $this->serializer = null;
    }

    public function testInstanceOfArraySerialized(): void
    {
        $this->assertInstanceOf(ArraySerialized::class, $this->model);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithArrayValue(): void
    {
        $inputValue = [
            'row1' => [
                'priority' => '10',
                'pattern' => '/checkout/*',
                'meta_directives' => [
                    ['value' => 'noindex', 'bot' => '', 'modification' => ''],
                ],
            ],
            'row2' => [
                'priority' => '20',
                'pattern' => '/customer/*',
                'meta_directives' => [
                    ['value' => 'nofollow', 'bot' => 'googlebot', 'modification' => ''],
                ],
            ],
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $this->assertIsString($savedValue);

        $decoded = $this->serializer->unserialize($savedValue);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithEmptyValue(): void
    {
        $this->model->setValue([]);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $this->assertNull($savedValue);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveRemovesDeletedRows(): void
    {
        $inputValue = [
            'row1' => [
                'priority' => '10',
                'pattern' => '/checkout/*',
                'meta_directives' => [],
            ],
            'row2' => [
                'priority' => '20',
                'pattern' => '/customer/*',
                'meta_directives' => [],
                '__deleted' => true,
            ],
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('row1', $decoded);
        $this->assertArrayNotHasKey('row2', $decoded);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testAfterLoadWithValidJson(): void
    {
        $data = [
            'row1' => [
                'priority' => '10',
                'pattern' => '/test/*',
                'meta_directives' => [],
            ],
        ];
        $jsonValue = $this->serializer->serialize($data);

        $this->model->setValue($jsonValue);

        $reflection = new \ReflectionMethod($this->model, '_afterLoad');
        $reflection->setAccessible(true);
        $reflection->invoke($this->model);

        $loadedValue = $this->model->getValue();
        $this->assertIsArray($loadedValue);
        $this->assertArrayHasKey('row1', $loadedValue);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testAfterLoadWithNullValue(): void
    {
        $this->model->setValue(null);

        $reflection = new \ReflectionMethod($this->model, '_afterLoad');
        $reflection->setAccessible(true);
        $reflection->invoke($this->model);

        $this->assertNull($this->model->getValue());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSavePreservesRuleStructure(): void
    {
        $inputValue = [
            'rule_1' => [
                'priority' => '100',
                'pattern' => '/cart/*',
                'meta_directives' => [
                    ['value' => 'noindex', 'bot' => '', 'modification' => ''],
                    ['value' => 'nofollow', 'bot' => '', 'modification' => ''],
                ],
            ],
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertArrayHasKey('rule_1', $decoded);
        $this->assertEquals('100', $decoded['rule_1']['priority']);
        $this->assertEquals('/cart/*', $decoded['rule_1']['pattern']);
        $this->assertIsArray($decoded['rule_1']['meta_directives']);
    }
}
