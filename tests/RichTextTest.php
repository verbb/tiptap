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
                            'openOnInsert' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $html = $richText->toHtml();

        // Token must appear once as text content — not also as HTML attributes
        // (which would double-resolve under Formie References::parseContent).
        $this->assertSame(1, substr_count($html, '{field:total}'));
        $this->assertStringContainsString('data-type="variableTag"', $html);
        $this->assertStringNotContainsString('value="{field:total}"', $html);
        $this->assertStringNotContainsString('label="Total"', $html);
        $this->assertStringNotContainsString('openOnInsert=', $html);
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
