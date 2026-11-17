# Banglish Slug

A small WordPress plugin that turns Bangla titles into clean Banglish (English-letter) URL slugs.

```
আমার সোনার বাংলা   →  /amar-sonar-bangla/
ছুটির নিয়ম          →  /chhutir-niyom/
ঢাকা ২০২৬          →  /dhaka-2026/
```

Without it, WordPress keeps Bangla titles as percent-encoded slugs like
`/%e0%a6%86%e0%a6%ae%e0%a6%be%e0%a6%b0-.../`, which look broken when shared on Facebook, WhatsApp or in email.

## Features

- **Works offline.** Rule-based transliteration in PHP. No API key, no external request, no cost.
- **Posts, pages, custom post types, categories, tags and custom taxonomies.** Each group can be switched on or off.
- **Bulk fixer.** *Tools → Banglish Slug* lists posts that still have Bangla slugs and converts the ones you select. WordPress keeps the old slug, so old links of published posts redirect to the new URL.
- **Your slug wins.** If you type a slug by hand in English, it is kept.
- **Bangla digits** (০–৯) become 0–9.

## Install

1. Download this repository as a ZIP (*Code → Download ZIP*).
2. In WordPress go to *Plugins → Add New → Upload Plugin* and upload the ZIP.
3. Activate. New posts get Banglish slugs automatically.
4. To fix old posts, open *Tools → Banglish Slug*. Take a database backup first.

## Requirements

WordPress 5.8+ and PHP 7.4+.

## Known limits

Bangla spelling does not always show where the inherent "o" sound is dropped, so the rules sometimes add an extra "o" (for example `টিপস` → `tipos`). You can always edit the slug by hand in the post editor. Better rules are welcome as pull requests.

## Run the tests

```
php tests/test-transliterator.php
```

To report a word that comes out wrong, open an issue with the Bangla word and the spelling you expected, or add a case to `tests/test-transliterator.php`.

## Contributing

Issues and pull requests are welcome, especially:

- transliteration rules for words that come out wrong
- translations of the admin screen
- tests

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
