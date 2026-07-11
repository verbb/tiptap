<?php
namespace verbb\tiptap\extensions;

use verbb\tiptap\TokenSerializer;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class VariableTag extends Node
{
    // Public Methods
    // =========================================================================

    public static $name = 'variableTag';

    public function addOptions()
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function parseHTML()
    {
        return [
            [
                'tag' => 'variable-tag',
                'getAttrs' => function ($DOMNode) {
                    $value = $DOMNode->getAttribute('value');
                    $label = $DOMNode->getAttribute('label');

                    if ($value !== '') {
                        return array_filter([
                            'label' => $label ?: null,
                            'value' => $value,
                            'default' => $DOMNode->getAttribute('default') ?: null,
                        ], fn ($item) => $item !== null && $item !== '');
                    }

                    $inner = trim($DOMNode->textContent ?? '');

                    if ($inner === '') {
                        return false;
                    }

                    $decoded = json_decode(html_entity_decode($inner), true);

                    return is_array($decoded) ? $decoded : false;
                },
            ],
        ];
    }

    public function addAttributes()
    {
        return [
            'label' => [],
            'value' => [],
            'openOnInsert' => [],
            'default' => [],
            'transformerId' => [],
            'transformerParams' => [],
        ];
    }

    public function renderText($node)
    {
        $attrs = $node->attrs ?? (object)[];
        $value = (string)($attrs->value ?? '');

        if ($value === '') {
            return '';
        }

        return TokenSerializer::serializeVariableTag((array)$attrs);
    }

    public function renderHTML($node, $HTMLAttributes = [])
    {
        return [
            'span',
            HTML::mergeAttributes(
                ['data-type' => self::$name],
                $this->options['HTMLAttributes'],
                $HTMLAttributes,
            ),
            0,
        ];
    }
}
