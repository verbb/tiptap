<?php
namespace verbb\tiptap\extensions;

use Tiptap\Core\Extension;
use Tiptap\Utils\InlineStyle;

class FontSize extends Extension
{
    // Properties
    // =========================================================================

    public static $name = 'fontSize';


    // Public Methods
    // =========================================================================

    public function addGlobalAttributes(): array
    {
        return [[
            'types' => ['textStyle'],
            'attributes' => [
                'fontSize' => [
                    'default' => null,
                    'parseHTML' => static fn($DOMNode): ?string => InlineStyle::getAttribute($DOMNode, 'font-size'),
                    'renderHTML' => static function($attributes): ?array {
                        $value = $attributes?->fontSize ?? null;

                        return $value === null ? null : ['style' => "font-size: {$value}"];
                    },
                ],
            ],
        ]];
    }
}
