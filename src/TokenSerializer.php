<?php
namespace verbb\tiptap;

class TokenSerializer
{
    // Static Methods
    // =========================================================================

    /**
     * Convert a content array to a single-line token string (TiptapInput mode).
     */
    public static function contentToValue(mixed $content): string
    {
        if (!$content) {
            return '';
        }

        $items = is_array($content) ? $content : [];
        $result = '';

        self::_visitNodes($items, function (array $node) use (&$result): void {
            $type = $node['type'] ?? null;

            if ($type === 'text') {
                $result .= Normalizer::stripInvisibleChars((string)($node['text'] ?? ''));
            } elseif ($type === 'variableTag') {
                $result .= self::serializeVariableTag($node['attrs'] ?? []);
            }
        });

        return preg_replace('/[\r\n]+/', ' ', $result) ?? '';
    }

    /**
     * Serialize variable tag attrs back to a token string.
     */
    public static function serializeVariableTag(array $attrs): string
    {
        $value = (string)($attrs['value'] ?? '');

        if ($value === '') {
            return '';
        }

        return self::serializeTokenMetadata($value, [
            'defaultIfEmpty' => isset($attrs['default']) ? (string)$attrs['default'] : null,
            'transformerId' => isset($attrs['transformerId']) ? (string)$attrs['transformerId'] : null,
            'transformerParams' => is_array($attrs['transformerParams'] ?? null) ? $attrs['transformerParams'] : null,
        ]);
    }

    public static function serializeTokenMetadata(string $baseToken, array $metadata): string
    {
        if (!preg_match('/^\{([^}]*)\}$/', $baseToken, $matches)) {
            return $baseToken;
        }

        $parts = [$matches[1]];
        $transformerId = trim((string)($metadata['transformerId'] ?? ''));

        if ($transformerId !== '') {
            $parts[] = self::TRANSFORMER_ID_PREFIX . rawurlencode($transformerId);

            foreach ($metadata['transformerParams'] ?? [] as $key => $value) {
                $normalizedKey = trim((string)$key);

                if ($normalizedKey === '' || $normalizedKey === 'transform') {
                    continue;
                }

                $parts[] = $normalizedKey . '=' . rawurlencode($value === null ? '' : (string)$value);
            }
        }

        $tokenBody = implode(';', array_filter($parts, fn ($part) => $part !== ''));
        $defaultIfEmpty = trim((string)($metadata['defaultIfEmpty'] ?? ''));

        return $defaultIfEmpty !== '' ? '{' . $tokenBody . '|' . $defaultIfEmpty . '}' : '{' . $tokenBody . '}';
    }

    private static function _visitNodes(array $nodes, callable $visitor): void
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $type = $node['type'] ?? null;

            if ($type === 'paragraph' && isset($node['content']) && is_array($node['content'])) {
                self::_visitNodes($node['content'], $visitor);
                continue;
            }

            $visitor($node);

            if (isset($node['content']) && is_array($node['content'])) {
                self::_visitNodes($node['content'], $visitor);
            }
        }
    }


    // Constants
    // =========================================================================

    private const TRANSFORMER_ID_PREFIX = 'transform=';
}
