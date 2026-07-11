<?php
namespace verbb\tiptap\twig;

use verbb\tiptap\RichText;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Extension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getFilters(): array
    {
        return [
            new TwigFilter('tiptapHtml', [$this, 'tiptapHtml'], ['is_safe' => ['html']]),
            new TwigFilter('tiptapPlain', [$this, 'tiptapPlain']),
            new TwigFilter('tiptapJson', [$this, 'tiptapJson']),
            new TwigFilter('tiptapToken', [$this, 'tiptapToken']),
        ];
    }

    public function tiptapHtml(mixed $value, bool $nl2br = false): string
    {
        return RichText::from($value)->toHtml(nl2br: $nl2br);
    }

    public function tiptapPlain(mixed $value): string
    {
        return RichText::from($value)->toPlainText();
    }

    public function tiptapJson(mixed $value): string
    {
        return RichText::from($value)->toJson();
    }

    public function tiptapToken(mixed $value): string
    {
        return RichText::from($value)->toTokenString();
    }
}
