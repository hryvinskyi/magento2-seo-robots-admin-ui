<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Test\Integration\Model\Config\Backend;

use Hryvinskyi\SeoRobotsAdminUi\Model\Config\Backend\DirectiveArray;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class DirectiveArrayTest extends TestCase
{
    private ?DirectiveArray $model = null;
    private ?Json $serializer = null;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->model = $objectManager->create(DirectiveArray::class);
        $this->serializer = $objectManager->get(Json::class);
    }

    protected function tearDown(): void
    {
        $this->model = null;
        $this->serializer = null;
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithEmptyValue(): void
    {
        $this->model->setValue('');
        $this->model->beforeSave();

        $this->assertNull($this->model->getValue());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithNullValue(): void
    {
        $this->model->setValue(null);
        $this->model->beforeSave();

        $this->assertNull($this->model->getValue());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithStructuredArrayValue(): void
    {
        $inputValue = [
            ['value' => 'noindex', 'bot' => '', 'modification' => ''],
            ['value' => 'nofollow', 'bot' => 'googlebot', 'modification' => ''],
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $this->assertIsString($savedValue);

        $decoded = $this->serializer->unserialize($savedValue);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals('noindex', $decoded[0]['value']);
        $this->assertEquals('nofollow', $decoded[1]['value']);
        $this->assertEquals('googlebot', $decoded[1]['bot']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithJsonStringValue(): void
    {
        $inputValue = [
            ['value' => 'noarchive', 'bot' => '', 'modification' => ''],
        ];
        $jsonString = $this->serializer->serialize($inputValue);

        $this->model->setValue($jsonString);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(1, $decoded);
        $this->assertEquals('noarchive', $decoded[0]['value']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithLegacyStringFormat(): void
    {
        $inputValue = [
            'noindex',
            'googlebot:nofollow',
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(2, $decoded);
        $this->assertEquals('noindex', $decoded[0]['value']);
        $this->assertEquals('', $decoded[0]['bot']);
        $this->assertEquals('nofollow', $decoded[1]['value']);
        $this->assertEquals('googlebot', $decoded[1]['bot']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithAdvancedDirective(): void
    {
        $inputValue = [
            'max-snippet:100',
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(1, $decoded);
        $this->assertEquals('max-snippet', $decoded[0]['value']);
        $this->assertEquals('100', $decoded[0]['modification']);
        $this->assertEquals('', $decoded[0]['bot']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithIndexedArrayFormat(): void
    {
        $inputValue = [
            ['noindex'],
            ['nofollow', 'googlebot', ''],
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(2, $decoded);
        $this->assertEquals('noindex', $decoded[0]['value']);
        $this->assertEquals('nofollow', $decoded[1]['value']);
        $this->assertEquals('googlebot', $decoded[1]['bot']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveFiltersEmptyValues(): void
    {
        $inputValue = [
            ['value' => 'noindex', 'bot' => '', 'modification' => ''],
            ['value' => '', 'bot' => '', 'modification' => ''],
            ['value' => 'nofollow', 'bot' => '', 'modification' => ''],
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(2, $decoded);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveWithInvalidJsonString(): void
    {
        $this->model->setValue('invalid json {{{');
        $this->model->beforeSave();

        $this->assertNull($this->model->getValue());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testAfterLoadWithValidJson(): void
    {
        $data = [
            ['value' => 'noindex', 'bot' => '', 'modification' => ''],
            ['value' => 'nofollow', 'bot' => 'googlebot', 'modification' => ''],
        ];
        $jsonValue = $this->serializer->serialize($data);

        $this->model->setValue($jsonValue);

        $reflection = new \ReflectionMethod($this->model, '_afterLoad');
        $reflection->setAccessible(true);
        $reflection->invoke($this->model);

        $loadedValue = $this->model->getValue();
        $this->assertIsArray($loadedValue);
        $this->assertCount(2, $loadedValue);
        $this->assertEquals('noindex', $loadedValue[0]['value']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testAfterLoadWithInvalidJson(): void
    {
        $this->model->setValue('invalid json');

        $reflection = new \ReflectionMethod($this->model, '_afterLoad');
        $reflection->setAccessible(true);
        $reflection->invoke($this->model);

        $this->assertEquals([], $this->model->getValue());
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
    public function testBeforeSaveWithBotAndModification(): void
    {
        $inputValue = [
            'googlebot:max-snippet:150',
        ];

        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertCount(1, $decoded);
        $this->assertEquals('googlebot', $decoded[0]['bot']);
        $this->assertEquals('max-snippet', $decoded[0]['value']);
        $this->assertEquals('150', $decoded[0]['modification']);
    }

    /**
     * @dataProvider trimsWhitespaceDataProvider
     * @magentoDbIsolation enabled
     *
     * @param array $inputValue
     * @param string $expectedValue
     * @param string $expectedBot
     */
    public function testBeforeSaveTrimsWhitespace(array $inputValue, string $expectedValue, string $expectedBot): void
    {
        $this->model->setValue($inputValue);
        $this->model->beforeSave();

        $savedValue = $this->model->getValue();
        $decoded = $this->serializer->unserialize($savedValue);

        $this->assertEquals($expectedValue, $decoded[0]['value']);
        $this->assertEquals($expectedBot, $decoded[0]['bot']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function trimsWhitespaceDataProvider(): array
    {
        return [
            'whitespace in value' => [
                'inputValue' => [['value' => '  noindex  ', 'bot' => '', 'modification' => '']],
                'expectedValue' => 'noindex',
                'expectedBot' => '',
            ],
            'whitespace in bot' => [
                'inputValue' => [['value' => 'noindex', 'bot' => '  googlebot  ', 'modification' => '']],
                'expectedValue' => 'noindex',
                'expectedBot' => 'googlebot',
            ],
        ];
    }
}
