<?php
namespace verbb\tiptap\extensions;

use Tiptap\Core\Extension;
use Tiptap\Utils\InlineStyle;

class BackgroundColor extends Extension
{
    // Properties
    // =========================================================================

    public static $name = 'backgroundColor';


    // Public Methods
    // =========================================================================

    public function addGlobalAttributes(): array
    {
        return [[
            'types' => ['textStyle'],
            'attributes' => [
                'backgroundColor' => [
                    'default' => null,
                    'parseHTML' => static function($DOMNode): ?string {
                        $value = InlineStyle::getAttribute($DOMNode, 'background-color');

                        return $value === null ? null : preg_replace('/[\'\"]+/', '', $value);
                    },
                    'renderHTML' => static function($attributes): ?array {
                        $value = $attributes?->backgroundColor ?? null;

                        return $value === null ? null : ['style' => "background-color: {$value}"];
                    },
                ],
            ],
        ]];
    }
}
