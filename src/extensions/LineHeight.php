<?php
namespace verbb\tiptap\extensions;

use Tiptap\Core\Extension;
use Tiptap\Utils\InlineStyle;

class LineHeight extends Extension
{
    // Properties
    // =========================================================================

    public static $name = 'lineHeight';


    // Public Methods
    // =========================================================================

    public function addGlobalAttributes(): array
    {
        return [[
            'types' => ['textStyle'],
            'attributes' => [
                'lineHeight' => [
                    'default' => null,
                    'parseHTML' => static fn($DOMNode): ?string => InlineStyle::getAttribute($DOMNode, 'line-height'),
                    'renderHTML' => static function($attributes): ?array {
                        $value = $attributes?->lineHeight ?? null;

                        return $value === null ? null : ['style' => "line-height: {$value}"];
                    },
                ],
            ],
        ]];
    }
}
