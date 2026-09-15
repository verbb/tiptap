<?php
namespace verbb\tiptap;

use verbb\tiptap\extensions\BackgroundColor;
use verbb\tiptap\extensions\CraftLink;
use verbb\tiptap\extensions\FontSize;
use verbb\tiptap\extensions\FontVariantCaps;
use verbb\tiptap\extensions\LineHeight;
use verbb\tiptap\extensions\VariableTag;

use Tiptap\Editor;
use Tiptap\Extensions\Color;
use Tiptap\Extensions\FontFamily;
use Tiptap\Extensions\StarterKit;
use Tiptap\Extensions\TextAlign;
use Tiptap\Marks;
use Tiptap\Nodes;

class EditorFactory
{
    // Static Methods
    // =========================================================================

    public static function pluginKitExtensions(bool $includeVariableTag = true): array
    {
        $extensions = [
            new StarterKit([
                'heading' => [
                    'levels' => [1, 2, 3, 4, 5, 6],
                ],
            ]),
            new Marks\Highlight,
            new Marks\TextStyle,
            new Color,
            new BackgroundColor,
            new FontFamily,
            new FontSize,
            new LineHeight,
            new FontVariantCaps,
            new CraftLink([
                'HTMLAttributes' => [
                    'target' => null,
                    'rel' => null,
                ],
            ]),
            new Marks\Subscript,
            new Marks\Superscript,
            new Marks\Underline,
            new Nodes\Table,
            new Nodes\TableCell,
            new Nodes\TableHeader,
            new Nodes\TableRow,
            new TextAlign([
                'types' => ['heading', 'paragraph'],
                'defaultAlignment' => 'left',
            ]),
        ];

        if ($includeVariableTag) {
            $extensions[] = new VariableTag;
        }

        return $extensions;
    }

    public static function create(array $additionalExtensions = [], bool $includeVariableTag = true): Editor
    {
        return new Editor([
            'extensions' => array_merge(
                self::pluginKitExtensions($includeVariableTag),
                $additionalExtensions,
            ),
        ]);
    }

    public static function htmlToContent(string $html, array $additionalExtensions = []): array
    {
        $editor = self::create($additionalExtensions);
        $document = $editor->setContent($html)->getDocument();

        return is_array($document) ? ($document['content'] ?? []) : [];
    }

    public static function contentToHtml(array $content, array $additionalExtensions = []): string
    {
        if ($content === []) {
            return '';
        }

        $editor = self::create($additionalExtensions);

        return $editor->setContent([
            'type' => 'doc',
            'content' => $content,
        ])->getHTML();
    }

    public static function contentToPlainText(array $content, array $additionalExtensions = []): string
    {
        return PlainTextRenderer::render($content);
    }
}
