<?php
namespace verbb\tiptap;

use craft\helpers\Json;

use JsonSerializable;
use Stringable;

class RichText implements JsonSerializable, Stringable
{
    // Static Methods
    // =========================================================================

    public static function from(mixed $value = null): self
    {
        return $value instanceof self ? $value : new self($value);
    }

    public static function fromHtml(string $html, array $additionalExtensions = []): self
    {
        $content = EditorFactory::htmlToContent($html, $additionalExtensions);

        return new self($content);
    }


    // Properties
    // =========================================================================

    private array $_value = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = null)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->toPlainText();
    }

    public function setValue(mixed $value): void
    {
        $this->_value = Normalizer::normalizeContentArray(Normalizer::normalize($value));
    }

    public function isEmpty(): bool
    {
        return $this->_value === [];
    }

    public function getValue(): array
    {
        return $this->_value;
    }

    public function getSchema(): array
    {
        return $this->_value;
    }

    public function toDoc(): array
    {
        return [
            'type' => 'doc',
            'content' => $this->getSchema(),
        ];
    }

    public function toJson(): string
    {
        return Json::encode($this->getSchema());
    }

    /**
     * Receives HTML from `$resolveReferences` and returns resolved HTML.
     */
    public function toHtml(?callable $resolveReferences = null, bool $nl2br = false, array $additionalExtensions = []): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $html = EditorFactory::contentToHtml($this->_value, $additionalExtensions);

        if ($nl2br) {
            $html = str_replace(['<p>', '</p>'], ['', '<br>'], $html);
            $html = preg_replace('/(<br>)+$/', '', $html) ?? $html;
        }

        if ($resolveReferences) {
            $html = $resolveReferences($html);
        }

        return Normalizer::stripInvisibleChars(html_entity_decode($html));
    }

    public function toPlainText(?callable $resolveReferences = null, array $additionalExtensions = []): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $text = EditorFactory::contentToPlainText($this->_value, $additionalExtensions);

        if ($resolveReferences) {
            $text = $resolveReferences($text);
        }

        return Normalizer::stripInvisibleChars($text);
    }

    /**
     * Convert stored content to a single-line token string (TiptapInput mode).
     */
    public function toTokenString(): string
    {
        return TokenSerializer::contentToValue($this->_value);
    }

    public function jsonSerialize(): array
    {
        return $this->getSchema();
    }
}
