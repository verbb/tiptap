<?php
namespace verbb\tiptap\extensions;

use Tiptap\Core\Extension;
use Tiptap\Utils\InlineStyle;

class FontVariantCaps extends Extension
{
    // Properties
    // =========================================================================

    public static $name = 'fontVariantCaps';


    // Public Methods
    // =========================================================================

    public function addGlobalAttributes(): array
    {
        return [[
            'types' => ['textStyle'],
            'attributes' => [
                'fontVariantCaps' => [
                    'default' => null,
                    'parseHTML' => static function($DOMNode): ?string {
                        $value = InlineStyle::getAttribute($DOMNode, 'font-variant-caps');

                        return $value === 'small-caps' ? $value : null;
                    },
                    'renderHTML' => static function($attributes): ?array {
                        return ($attributes?->fontVariantCaps ?? null) === 'small-caps'
                            ? ['style' => 'font-variant-caps: small-caps']
                            : null;
                    },
                ],
            ],
        ]];
    }
}
