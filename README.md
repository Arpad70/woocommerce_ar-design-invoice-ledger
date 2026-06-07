# AR Design Invoice Ledger for WooCommerce

Samostatný AR Design modul, ktorý pridáva pod **WooCommerce → PDF Invoices** položku menu **Evidenčná kniha faktúr**.

## Funkcie
- zoznam vystavených faktúr (faktúra, objednávka, dátum, kupujúci, suma, mena, stav),
- filtre podľa stavu objednávky, dátumu, kupujúceho a čísla faktúry,
- export podľa filtra do CSV,
- voľba predvoleného profilu exportu pre ekonomický SW.

## Kostra modulu
- `ar-design-invoice-ledger.php` — bootstrap pluginu + constants + dependency checks
- `includes/AdminPage.php` — admin UI, filter logika, export
- `includes/Updater.php` — update kanál cez GitHub releases
- `scripts/build-plugin.sh` — lokálny release build
- `scripts/verify-version-consistency.php` — kontrola verzií
- `.github/workflows/release.yml` — automatický release workflow

## Build
bash scripts/build-plugin.sh

## Release
Pozri `RELEASE.md` a `RELEASE_CHECKLIST.md`.
