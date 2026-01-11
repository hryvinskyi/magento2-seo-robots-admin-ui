<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\SeoRobotsAdminUi\Block\Adminhtml\System\Config\Form\Field\Renderer\DirectiveMultiselect;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Dynamic field array for X-Robots-Tag HTTP header rules configuration
 */
class XRobotsTagRules extends AbstractFieldArray
{
    /**
     * @var DirectiveMultiselect
     */
    private $directiveRenderer;

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('priority', [
            'label' => __('Priority'),
            'class' => 'validate-number',
            'style' => 'width: 80px'
        ]);

        $this->addColumn('pattern', [
            'label' => __('URL Pattern'),
            'style' => 'width: 250px'
        ]);

        $this->addColumn('xrobots_directives', [
            'label' => __('X-Robots-Tag Directives'),
            'renderer' => $this->getDirectiveRenderer(),
            'style' => 'width: 350px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Rule');
    }

    /**
     * Get directive renderer
     *
     * @return DirectiveMultiselect
     * @throws LocalizedException
     */
    /**
     * Get directive renderer
     *
     * @return DirectiveMultiselect
     * @throws LocalizedException
     */
    private function getDirectiveRenderer()
    {
        if (!$this->directiveRenderer) {
            $this->directiveRenderer = $this->getLayout()->createBlock(
                DirectiveMultiselect::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            // Enable bot names per directive for X-Robots-Tag
            $this->directiveRenderer->setEnableBotNames(true);
            $this->directiveRenderer->setDirectives($this->getXRobotsTagDirectives());
        }
        return $this->directiveRenderer;
    }

    /**
     * Get directives for X-Robots-Tag
     *
     * @return array
     */
    private function getXRobotsTagDirectives(): array
    {
        return [
            'indexing' => [
                [
                    'value' => 'all',
                    'label' => 'all',
                    'description' => 'No restrictions for indexing or serving (default)',
                    'conflicts' => ['noindex', 'nofollow', 'none']
                ],
                [
                    'value' => 'index',
                    'label' => 'index',
                    'description' => 'Allow different indexing',
                    'conflicts' => ['noindex', 'none']
                ],
                [
                    'value' => 'follow',
                    'label' => 'follow',
                    'description' => 'Follow links on this page',
                    'conflicts' => ['nofollow', 'none']
                ],
                [
                    'value' => 'noindex',
                    'label' => 'noindex',
                    'description' => 'Do not show this page in search results',
                    'conflicts' => ['index', 'all']
                ],
                [
                    'value' => 'nofollow',
                    'label' => 'nofollow',
                    'description' => 'Do not follow links on this page',
                    'conflicts' => ['follow', 'all']
                ],
                [
                    'value' => 'none',
                    'label' => 'none',
                    'description' => 'Equivalent to noindex, nofollow',
                    'conflicts' => ['index', 'follow', 'all']
                ]
            ],
            'snippets' => [
                [
                    'value' => 'noarchive',
                    'label' => 'noarchive',
                    'description' => 'Do not show a cached link in search results'
                ],
                [
                    'value' => 'nosnippet',
                    'label' => 'nosnippet',
                    'description' => 'Do not show a text snippet or video preview'
                ],
                [
                    'value' => 'max-snippet',
                    'label' => 'max-snippet',
                    'description' => 'Maximum text length of snippet',
                    'hasInput' => true,
                    'inputType' => 'number',
                    'inputPlaceholder' => 'Enter character count',
                    'formatter' => 'numericColon'
                ],
                [
                    'value' => 'max-image-preview',
                    'label' => 'max-image-preview',
                    'description' => 'Maximum size of image preview',
                    'hasInput' => true,
                    'inputType' => 'select',
                    'inputOptions' => ['none', 'standard', 'large'],
                    'formatter' => 'standardColon'
                ],
                [
                    'value' => 'max-video-preview',
                    'label' => 'max-video-preview',
                    'description' => 'Maximum video preview duration in seconds',
                    'hasInput' => true,
                    'inputType' => 'number',
                    'inputPlaceholder' => 'Enter seconds (-1 for no limit)',
                    'formatter' => 'numericColon'
                ]
            ],
            'images' => [
                [
                    'value' => 'noimageindex',
                    'label' => 'noimageindex',
                    'description' => 'Do not index images on this page'
                ]
            ],
            'translations' => [
                [
                    'value' => 'notranslate',
                    'label' => 'notranslate',
                    'description' => 'Do not offer translation of this page'
                ]
            ],
            'crawling' => [
                [
                    'value' => 'unavailable_after',
                    'label' => 'unavailable_after',
                    'description' => 'Do not show after specified date/time',
                    'hasInput' => true,
                    'inputType' => 'datetime',
                    'inputPlaceholder' => 'YYYY-MM-DD HH:MM:SS UTC',
                    'formatter' => 'standardColon'
                ],
                [
                    'value' => 'indexifembedded',
                    'label' => 'indexifembedded',
                    'description' => 'Allow indexing when embedded via iframe'
                ]
            ]
        ];
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $xrobotsDirectives = $row->getData('xrobots_directives');
        if ($xrobotsDirectives && is_array($xrobotsDirectives)) {
            foreach ($xrobotsDirectives as $directive) {
                $options['option_' . $this->getDirectiveRenderer()->calcOptionHash($directive)] = 'selected="selected"';
            }
        }

        $row->setData('option_extra_attrs', $options);
    }
}
