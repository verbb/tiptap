<?php
namespace verbb\tiptap;

class Normalizer
{
    // Static Methods
    // =========================================================================

    /**
     * Normalize any supported rich text value to a content array (`doc.content`).
     */
    public static function normalize(mixed $value): array
    {
        if ($value instanceof RichText) {
            return $value->getSchema();
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return self::normalize($decoded);
            }

            if (self::isHtml($value)) {
                return RichText::fromHtml($value)->getSchema();
            }

            return self::_createParagraphContent($value);
        }

        if (is_object($value)) {
            return self::normalize((array)$value);
        }

        if (!is_array($value)) {
            return self::_createParagraphContent((string)$value);
        }

        $value = self::_normalizeNodeValue($value);
        $value = self::_pruneEmptyTextNodes($value);

        if (($value['type'] ?? null) === 'doc') {
            return self::normalize($value['content'] ?? []);
        }

        if (isset($value['type'])) {
            return [$value];
        }

        return is_array($value) ? $value : [];
    }

    public static function normalizeContentArray(mixed $content): array
    {
        if (!is_array($content)) {
            return [];
        }

        $normalized = [];

        foreach ($content as $node) {
            if ($node === null) {
                continue;
            }

            if (is_array($node) && !isset($node['type']) && isset($node['content']) && is_array($node['content'])) {
                $normalized = array_merge($normalized, self::normalizeContentArray($node['content']));
                continue;
            }

            if (!is_array($node) || !isset($node['type'])) {
                continue;
            }

            if ($node['type'] === 'text') {
                $text = self::stripInvisibleChars((string)($node['text'] ?? ''));

                if ($text === '') {
                    continue;
                }

                $normalized[] = array_merge($node, ['text' => $text]);
                continue;
            }

            if (isset($node['content']) && is_array($node['content'])) {
                $node['content'] = self::normalizeContentArray($node['content']);
            }

            $normalized[] = self::_stripDefaultTextAlign($node);
        }

        return $normalized;
    }

    public static function stripInvisibleChars(string $value): string
    {
        return str_replace(self::INVISIBLE_CHARS, '', $value);
    }

    public static function isHtml(string $value): bool
    {
        return (bool)preg_match('/<\s*[a-z!\/][^>]*>/i', $value);
    }

    private static function _createParagraphContent(string $text): array
    {
        $text = self::stripInvisibleChars($text);

        if ($text === '') {
            return [];
        }

        return [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                ],
            ],
        ];
    }

    private static function _normalizeNodeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key)
                    ? str_replace(array_keys(self::LEGACY_NODE_NAMES), array_values(self::LEGACY_NODE_NAMES), $key)
                    : $key;
                $normalized[$normalizedKey] = self::_normalizeNodeValue($item);
            }

            return $normalized;
        }

        if (is_object($value)) {
            return self::_normalizeNodeValue((array)$value);
        }

        if (is_string($value)) {
            return self::stripInvisibleChars(
                str_replace(array_keys(self::LEGACY_NODE_NAMES), array_values(self::LEGACY_NODE_NAMES), $value)
            );
        }

        return $value;
    }

    private static function _pruneEmptyTextNodes(mixed $value): mixed
    {
        if (!is_array($value)) {
            if (is_object($value)) {
                return self::_pruneEmptyTextNodes((array)$value);
            }

            return $value;
        }

        $isList = array_is_list($value);
        $cleaned = [];

        foreach ($value as $key => $item) {
            $cleanedItem = self::_pruneEmptyTextNodes($item);

            if ($cleanedItem === null) {
                continue;
            }

            if ($isList) {
                $cleaned[] = $cleanedItem;
            } else {
                $cleaned[$key] = $cleanedItem;
            }
        }

        if (($cleaned['type'] ?? null) === 'text' && ($cleaned['text'] ?? '') === '') {
            return null;
        }

        return $cleaned;
    }

    private static function _stripDefaultTextAlign(array $node): array
    {
        $attrs = $node['attrs'] ?? null;

        if (!is_array($attrs)) {
            return $node;
        }

        if (($attrs['textAlign'] ?? null) === 'left') {
            unset($attrs['textAlign']);
        }

        if ($attrs === []) {
            unset($node['attrs']);
        } else {
            $node['attrs'] = $attrs;
        }

        return $node;
    }


    // Constants
    // =========================================================================

    private const INVISIBLE_CHARS = [
        "\u{200B}",
        "\u{200C}",
        "\u{200D}",
        "\u{2060}",
        "\u{FEFF}",
    ];

    private const LEGACY_NODE_NAMES = [
        'bullet_list' => 'bulletList',
        'code_block' => 'codeBlock',
        'hard_break' => 'hardBreak',
        'horizontal_rule' => 'horizontalRule',
        'list_item' => 'listItem',
        'ordered_list' => 'orderedList',
    ];
}
