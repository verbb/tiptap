<?php
namespace verbb\tiptap\feedme;

use verbb\tiptap\EditorFactory;
use verbb\tiptap\Normalizer;
use verbb\tiptap\RichText;

use craft\helpers\Json;

class ContentParser
{
    // Static Methods
    // =========================================================================

    /**
     * Parse imported Feed Me (or similar) values into encoded content JSON.
     */
    public static function parse(mixed $value, array $additionalExtensions = []): string
    {
        if (is_string($value) && Json::isJsonObject($value)) {
            return $value;
        }

        if (is_string($value) && Normalizer::isHtml($value)) {
            return RichText::fromHtml($value, $additionalExtensions)->toJson();
        }

        if (!$value) {
            return '[]';
        }

        if (is_array($value) && array_key_exists('content', $value)) {
            $editor = EditorFactory::create($additionalExtensions);
            $document = $editor->setContent($value)->getDocument();

            if (is_array($document) && array_key_exists('content', $document)) {
                return Json::encode($document['content']);
            }

            return '[]';
        }

        return RichText::from($value)->toJson();
    }
}
