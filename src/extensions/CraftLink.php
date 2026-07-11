<?php
namespace verbb\tiptap\extensions;

use Craft;
use craft\helpers\Html as CraftHtml;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;

use Tiptap\Marks\Link;
use Tiptap\Utils\HTML;

class CraftLink extends Link
{
    // Public Methods
    // =========================================================================

    public function renderHTML($mark, $HTMLAttributes = [])
    {
        $href = $HTMLAttributes['href'] ?? ($mark->attrs->href ?? '');
        $attributes = HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes);

        if (is_string($href) && $href !== '') {
            $attributes['href'] = self::_parseRefTags($href);
        }

        if (isset($mark->attrs)) {
            foreach ((array)$mark->attrs as $key => $value) {
                if ($value === null) {
                    unset($attributes[$key]);
                }
            }
        }

        $target = $attributes['target'] ?? null;

        if ($target === '_blank') {
            $resolvedHref = is_string($attributes['href'] ?? null) ? $attributes['href'] : (string)$href;
            $isInternal = self::_isInternalUrl((string)$href) || self::_isInternalUrl($resolvedHref);

            if (!$isInternal) {
                $attributes['rel'] = 'noopener noreferrer nofollow';
            } else {
                unset($attributes['rel']);
            }
        }

        return [
            'a',
            $attributes,
            0,
        ];
    }


    // Private Methods
    // =========================================================================

    private static function _parseRefTags(string $value): string
    {
        $value = preg_replace_callback('/([^\'"\?#]*)(\?[^\'"\?#]+)?(#[^\'"\?#]+)?(?:#|%23)([\w]+)\:(\d+)(?:@(\d+))?(\:(?:transform\:)?' . HandleValidator::$handlePattern . ')?/', function ($matches) {
            [, $url, $query, $hash, $elementType, $ref, $siteId, $transform] = array_pad($matches, 10, null);

            $ref = $elementType . ':' . $ref . ($siteId ? "@$siteId" : '') . ($transform ?: ':url');

            if ($query || $hash) {
                $parsed = Craft::$app->getElements()->parseRefs("{{$ref}}");

                if ($query) {
                    $query = CraftHtml::decode($query);

                    if (str_contains($parsed, $query)) {
                        $url .= $query;
                        $query = '';
                    }
                }

                if ($hash && str_contains($parsed, $hash)) {
                    $url .= $hash;
                    $hash = '';
                }
            }

            return '{' . $ref . '||' . $url . '}' . $query . $hash;
        }, $value) ?? $value;

        if (StringHelper::contains($value, '{')) {
            $value = Craft::$app->getElements()->parseRefs($value);
        }

        return $value;
    }

    private static function _isInternalUrl(string $href): bool
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#')) {
            return true;
        }

        if (preg_match('/\{[\w]+:\d+/', $href)) {
            return true;
        }

        if (UrlHelper::isRootRelativeUrl($href)) {
            return true;
        }

        if (!UrlHelper::isAbsoluteUrl($href) && !UrlHelper::isProtocolRelativeUrl($href)) {
            return true;
        }

        if (UrlHelper::isAbsoluteUrl($href) && !preg_match('/^https?:\/\//i', $href)) {
            return true;
        }

        $linkHost = parse_url($href, PHP_URL_HOST);

        if ($linkHost === null && UrlHelper::isProtocolRelativeUrl($href)) {
            $linkHost = parse_url('https:' . $href, PHP_URL_HOST);
        }

        if ($linkHost === null) {
            return true;
        }

        if (!Craft::$app) {
            return false;
        }

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteHost = parse_url($site->getBaseUrl(), PHP_URL_HOST);

            if ($siteHost && strcasecmp($linkHost, $siteHost) === 0) {
                return true;
            }
        }

        return false;
    }
}
