# Changelog

## 1.0.0 - 2026-07-10

### Added
- Initial release.
- `RichText` value object for normalizing, serializing, and rendering Tiptap content.
- `Normalizer` for project config / database value normalization.
- `EditorFactory` with extensions aligned to `@verbb/plugin-kit-tiptap-core`.
- `VariableTag` extension for token rendering.
- `CraftLink` extension with Craft ref-tag parsing and internal/external `rel` handling.
- `TokenSerializer` for single-line TiptapInput token strings.
- Twig filters: `tiptapHtml`, `tiptapPlain`, `tiptapJson`, `tiptapToken`.
- Feed Me `ContentParser` helper.
- Optional Yii module bootstrap for Twig filters.
