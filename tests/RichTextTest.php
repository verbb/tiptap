<?php

namespace verbb\tiptap\tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tiptap\Nodes\Paragraph;
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

    public function testRejectsMalformedAndUnsafeLinkTargets(): void
    {
        foreach ([['invalid'], 42, 'javascript:alert(1)'] as $href) {
            $richText = RichText::from([[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Linked text',
                    'marks' => [[
                        'type' => 'link',
                        'attrs' => ['href' => $href],
                    ]],
                ]],
            ]]);

            $html = $richText->toHtml();

            $this->assertStringContainsString('Linked text', $html);
            $this->assertStringNotContainsString('invalid', $html);
            $this->assertStringNotContainsString('javascript:', $html);
        }
    }

    public function testPreservesValidLinkTargets(): void
    {
        $richText = RichText::from([[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'Linked text',
                'marks' => [[
                    'type' => 'link',
                    'attrs' => [
                        'href' => 'https://verbb.io/',
                    ],
                ]],
            ]],
        ]]);

        $html = $richText->toHtml();

        $this->assertStringContainsString('href="https://verbb.io/"', $html);
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

    public function testPreservesTransformParametersInHtmlAndPlainText(): void
    {
        $richText = RichText::from([[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'variableTag',
                'attrs' => [
                    'value' => '{field:items;scope=all}',
                    'transformerId' => 'join',
                    'transformerParams' => ['separator' => ' | '],
                ],
            ]],
        ]]);
        $expected = '{field:items;scope=all;transform=join;separator=%20%7C%20}';
        $this->assertSame($expected, $richText->toPlainText());
        $this->assertStringContainsString($expected, $richText->toHtml());
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

        $this->assertSame('Line one<br>Line two', $html);
    }

    public static function paragraphAlignments(): array
    {
        return array_map(fn($alignment) => [$alignment], ['start', 'left', 'center', 'right', 'justify', 'end']);
    }

    #[DataProvider('paragraphAlignments')]
    public function testNl2brModeFlattensAlignedParagraphs(string $alignment): void
    {
        $schema = [[
            'type' => 'paragraph',
            'attrs' => ['textAlign' => $alignment],
            'content' => [['type' => 'text', 'text' => 'Hello']],
        ]];
        // Exercise stored JSON, including the logical alignment found in legacy Formie values.
        $richText = RichText::from(json_encode($schema));
        $normalizedSchema = $richText->getSchema();
        $normalHtml = $alignment === 'left' ? '<p>Hello</p>' : '<p style="text-align: ' . $alignment . ';">Hello</p>';

        $this->assertSame($normalHtml, $richText->toHtml(nl2br: false));
        $this->assertSame('Hello', $richText->toHtml(nl2br: true));
        $this->assertSame($normalizedSchema, $richText->getSchema());
    }

    public function testNl2brModePreservesInlineMarkupAndParagraphSpacing(): void
    {
        $richText = RichText::fromHtml('<p style="text-align: start">First <strong>bold</strong><br>line</p><p></p><p style="text-align: right"><a href="https://verbb.io/">Last</a></p><p style="text-align: center"></p>');

        $this->assertSame(
            'First <strong>bold</strong><br>line<br><br><a href="https://verbb.io/">Last</a>',
            $richText->toHtml(nl2br: true),
        );
    }

    public function testNl2brModePreservesOtherBlocksAndFlattensNestedParagraphs(): void
    {
        $richText = RichText::fromHtml('<h2 style="text-align: center">Heading</h2><blockquote><p style="text-align: start">Quote</p></blockquote><ul><li><p style="text-align: right">Item</p></li></ul><pre><code>Code</code></pre>');

        $this->assertSame(
            '<h2 style="text-align: center;">Heading</h2><blockquote>Quote<br></blockquote><ul><li>Item<br></li></ul><pre><code>Code</code></pre>',
            $richText->toHtml(nl2br: true),
        );
    }

    public function testNl2brModeFlattensParagraphsWithExtensionAttributes(): void
    {
        $richText = RichText::fromHtml('<p>Hello</p>');
        $extensions = [new Paragraph(['HTMLAttributes' => [
            'class' => 'custom-paragraph',
            'data-label' => 'A > B "quoted"',
        ]])];

        // Generated attribute values are escaped before paragraph flattening occurs.
        $this->assertSame('Hello', $richText->toHtml(nl2br: true, additionalExtensions: $extensions));
    }

    public function testNl2brModeRunsBeforeReferenceResolution(): void
    {
        $richText = RichText::fromHtml('<p style="text-align: start">Token</p>');

        $html = $richText->toHtml(function(string $html): string {
            $this->assertSame('Token', $html);

            return '<p class="resolved">Resolved</p>';
        }, nl2br: true);

        $this->assertSame('<p class="resolved">Resolved</p>', $html);
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
