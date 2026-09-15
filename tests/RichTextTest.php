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

    public function testSmallCapsRoundTripsThroughTextStyle(): void
    {
        $richText = RichText::fromHtml('<p><span style="font-variant-caps: small-caps">NASA</span></p>');
        $text = $richText->getSchema()[0]['content'][0];

        $this->assertSame([[
            'type' => 'textStyle',
            'attrs' => ['fontVariantCaps' => 'small-caps'],
        ]], $text['marks']);
        $this->assertStringContainsString('font-variant-caps: small-caps', $richText->toHtml());
        $this->assertSame('NASA', $richText->toPlainText());
    }

    public function testRejectsUnsupportedFontVariantCapsValues(): void
    {
        $richText = RichText::fromHtml('<p><span style="font-variant-caps: titling-caps">NASA</span></p>');
        $mark = $richText->getSchema()[0]['content'][0]['marks'][0];

        $this->assertArrayNotHasKey('attrs', $mark);
        $this->assertStringNotContainsString('font-variant-caps', $richText->toHtml());
    }

    public function testTextStyleKitAttributesRoundTripTogether(): void
    {
        $richText = RichText::fromHtml('<p><span style="font-family: Georgia, serif; font-size: 18px; color: #2563eb; background-color: #dbeafe; line-height: 1.5; font-variant-caps: small-caps">Styled</span></p>');
        $attributes = $richText->getSchema()[0]['content'][0]['marks'][0]['attrs'];

        $this->assertSame([
            'color' => '#2563eb',
            'backgroundColor' => '#dbeafe',
            'fontFamily' => 'Georgia, serif',
            'fontSize' => '18px',
            'lineHeight' => '1.5',
            'fontVariantCaps' => 'small-caps',
        ], $attributes);

        $html = $richText->toHtml();
        $this->assertStringContainsString('font-family: Georgia, serif', $html);
        $this->assertStringContainsString('font-size: 18px', $html);
        $this->assertStringContainsString('background-color: #dbeafe', $html);
        $this->assertStringContainsString('font-variant-caps: small-caps', $html);
    }

    public function testEmptyTextStyleAttributesDoNotRenderEmptyCss(): void
    {
        $richText = RichText::from([[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'NASA',
                'marks' => [[
                    'type' => 'textStyle',
                    'attrs' => [
                        'color' => '',
                        'backgroundColor' => '',
                        'fontFamily' => '',
                        'fontSize' => '',
                        'lineHeight' => '',
                        'fontVariantCaps' => 'small-caps',
                    ],
                ]],
            ]],
        ]]);

        $html = $richText->toHtml();
        $this->assertStringContainsString('font-variant-caps: small-caps', $html);
        $this->assertStringNotContainsString('color: ;', $html);
        $this->assertStringNotContainsString('font-family: ;', $html);
        $this->assertStringNotContainsString('font-size: ;', $html);
        $this->assertStringNotContainsString('line-height: ;', $html);
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
