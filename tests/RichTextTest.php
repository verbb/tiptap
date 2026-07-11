<?php

namespace verbb\tiptap\tests;

use PHPUnit\Framework\TestCase;
use verbb\tiptap\RichText;
use verbb\tiptap\TokenSerializer;

class RichTextTest extends TestCase
{
    public function testRendersBasicHtml(): void
    {
        $richText = RichText::from([
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Hello world'],
                ],
            ],
        ]);

        $this->assertStringContainsString('Hello world', $richText->toHtml());
        $this->assertSame('Hello world', $richText->toPlainText());
    }

    public function testRendersVariableTagsAsTokens(): void
    {
        $richText = RichText::from([
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'variableTag',
                        'attrs' => [
                            'label' => 'Total',
                            'value' => '{field:total}',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('{field:total}', $richText->toHtml());
        $this->assertSame('{field:total}', $richText->toPlainText());
    }

    public function testSerializesVariableTagsWithTransformMetadata(): void
    {
        $value = TokenSerializer::contentToValue([
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'variableTag',
                        'attrs' => [
                            'value' => '{field:total}',
                            'default' => '0',
                            'transformerId' => 'round',
                            'transformerParams' => ['decimals' => '2'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('{field:total;transform=round;decimals=2|0}', $value);
    }

    public function testImportsHtmlIntoSchema(): void
    {
        $richText = RichText::fromHtml('<p>Imported <strong>HTML</strong></p>');

        $this->assertFalse($richText->isEmpty());
        $this->assertStringContainsString('<strong>HTML</strong>', $richText->toHtml());
    }

    public function testNl2brModeFlattensParagraphs(): void
    {
        $richText = RichText::from([
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Line one'],
                ],
            ],
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Line two'],
                ],
            ],
        ]);

        $html = $richText->toHtml(nl2br: true);

        $this->assertStringNotContainsString('<p>', $html);
        $this->assertStringContainsString('Line one', $html);
        $this->assertStringContainsString('Line two', $html);
    }

    public function testJsonSerializeReturnsSchema(): void
    {
        $schema = [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Saved'],
                ],
            ],
        ];

        $richText = RichText::from($schema);

        $this->assertSame($schema, $richText->jsonSerialize());
    }
}
