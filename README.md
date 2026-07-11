# Tiptap for Craft CMS

Tiptap is a library for Craft plugins and modules for working with rich text content produced by [`@verbb/plugin-kit-tiptap-core`](https://github.com/verbb/plugin-kit/tree/main/plugin-kit-tiptap-core) in [verbb/plugin-kit](https://github.com/verbb/plugin-kit). It normalizes stored JSON, renders HTML and plain text server-side, and provides Twig filters for templates.

> This isn't a Craft plugin for end-users. Install it in your own Craft plugins and modules to normalize, render, and output Tiptap content.

## Install

You can install the package via Composer:

```bash
composer require verbb/tiptap
```

## Bootstrap (optional)

Register the Yii module to auto-load Twig filters:

```php
// config/app.php
return [
    'modules' => [
        'tiptap' => \verbb\tiptap\Tiptap::class,
    ],
    'bootstrap' => ['tiptap'],
];
```

Or register the Twig extension manually in your plugin's `init()`:

```php
use verbb\tiptap\twig\Extension;

Craft::$app->getView()->registerTwigExtension(new Extension());
```

## Usage

As a quick example, use the following to normalize stored content and render it in Twig or PHP.

### Settings model

```php
use verbb\tiptap\RichText;

class Settings extends Model
{
    public RichText $footerCopy;

    public function __construct($config = [])
    {
        $this->footerCopy = RichText::from(null);
        parent::__construct($config);
    }

    public function setFooterCopy(mixed $value): void
    {
        $this->footerCopy = RichText::from($value);
    }
}
```

The value serializes to a content array (`doc.content`) for project config and the database.

### Twig

```twig
{{ settings.footerCopy|tiptapHtml }}
{{ settings.footerCopy|tiptapPlain }}
{{ settings.footerCopy|tiptapToken }}
```

### PHP

```php
use verbb\tiptap\RichText;

$content = RichText::from($settings->footerCopy);

$content->isEmpty();
$content->getSchema();
$content->toHtml();
$content->toPlainText();
$content->toTokenString();
$content->toJson();
```

### Resolving references

Pass a callback to post-process rendered output (for example, Formie submission tokens):

```php
$html = $content->toHtml(fn (string $html) => References::parseContent($html, $submission));
```

### Feed Me

```php
use verbb\tiptap\feedme\ContentParser;

public function parseField(): string
{
    return ContentParser::parse($this->fetchValue(), [
        // new MyCustomNode(),
    ]);
}
```

Be sure to check out the full [documentation](https://verbb.io/packages/tiptap).

## Storage format

Store the **content array** (`doc.content`), not the full `{ type: 'doc' }` wrapper. This matches `@verbb/plugin-kit-tiptap-core` and `@verbb/plugin-kit-web` serialization.

## Documentation
Visit the [Auth package page](https://verbb.io/packages/tiptap) for all documentation and developer resources.

## Credits
Built on [ueberdosis/tiptap-php](https://github.com/ueberdosis/tiptap-php).

## Sponsor
Auth is licensed under the MIT license, meaning it will always be free and open source – we love free stuff! If you'd like to show your support to the plugin regardless, [Sponsor](https://github.com/sponsors/verbb) development.

<h2></h2>

<a href="https://verbb.io" target="_blank">
    <img width="101" height="33" src="https://verbb.io/assets/img/verbb-pill.svg" alt="Verbb">
</a>
