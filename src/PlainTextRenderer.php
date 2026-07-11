<?php
namespace verbb\tiptap;

class PlainTextRenderer
{
    // Static Methods
    // =========================================================================

    public static function render(array $content, string $blockSeparator = "\n\n"): string
    {
        if ($content === []) {
            return '';
        }

        $blocks = [];

        foreach ($content as $node) {
            if (!is_array($node)) {
                continue;
            }

            $blocks[] = self::_renderNode($node);
        }

        return implode($blockSeparator, array_filter($blocks, fn (string $block) => $block !== ''));
    }

    private static function _renderNode(array $node): string
    {
        $type = $node['type'] ?? null;

        if ($type === 'text') {
            return Normalizer::stripInvisibleChars((string)($node['text'] ?? ''));
        }

        if ($type === 'variableTag') {
            return TokenSerializer::serializeVariableTag($node['attrs'] ?? []);
        }

        if ($type === 'hardBreak') {
            return "\n";
        }

        if (!isset($node['content']) || !is_array($node['content'])) {
            return '';
        }

        return self::_renderInline($node['content']);
    }

    private static function _renderInline(array $nodes): string
    {
        $parts = [];

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $parts[] = self::_renderNode($node);
        }

        return implode('', $parts);
    }
}
