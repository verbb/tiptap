<?php

namespace verbb\tiptap\tests;

use PHPUnit\Framework\TestCase;
use verbb\tiptap\Normalizer;
use verbb\tiptap\RichText;

class NormalizerTest extends TestCase
{
    public function testNormalizesDocWrapperToContentArray(): void
    {
        $content = Normalizer::normalize([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Hello'],
                    ],
                ],
            ],
        ]);

        $this->assertSame([
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ], $content);
    }

    public function testNormalizesLegacyNodeNames(): void
    {
        $content = Normalizer::normalize([
            [
                'type' => 'bullet_list',
                'content' => [
                    [
                        'type' => 'list_item',
                        'content' => [
                            ['type' => 'text', 'text' => 'Item'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('bulletList', $content[0]['type']);
        $this->assertSame('listItem', $content[0]['content'][0]['type']);
    }

    public function testStripsInvisibleCharactersFromTextNodes(): void
    {
        $content = Normalizer::normalizeContentArray([
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => "Hello\u{200B}"],
                    ['type' => 'text', 'text' => ''],
                ],
            ],
        ]);

        $this->assertSame([
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ], $content);
    }

    public function testCreatesParagraphFromPlainString(): void
    {
        $content = Normalizer::normalize('Hello world');

        $this->assertSame('paragraph', $content[0]['type']);
        $this->assertSame('Hello world', $content[0]['content'][0]['text']);
    }
}
