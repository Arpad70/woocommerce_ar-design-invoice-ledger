# Release Process

## Samostatné verzovanie

1. uprav `VERSION`
2. uprav verziu v `ar-design-invoice-ledger.php`
3. uprav `AR_DESIGN_INVOICE_LEDGER_VERSION`
4. doplň záznam do `CHANGELOG.md`
5. merge PR do `main`

Kontrola konzistencie:

php scripts/verify-version-consistency.php

Po merge do `main` sa automaticky spustí workflow `.github/workflows/release.yml`.
Ak sa v commite zmenil súbor `VERSION`, workflow:

- overí syntax PHP súborov,
- overí konzistenciu verzie,
- vytvorí tag `v<version>`,
- vytvorí GitHub Release,
- priloží asset `ar-design-invoice-ledger-<version>.zip`.

## Lokálny build ZIP balíka (voliteľné)

bash scripts/build-plugin.sh

Výstup lokálne:

- ZIP sa vytvorí do `build/`
- názov súboru bude `ar-design-invoice-ledger-<version>.zip`

## Produkčný update

1. zazálohuj databázu
2. zazálohuj adresár `wp-content/plugins/ar-design-invoice-ledger`
3. nech WordPress detegovať novú verziu pluginu z GitHub release
4. spusť štandardnú aktualizáciu pluginu v administrácii
5. over zobrazenie evidenčnej knihy a export CSV
