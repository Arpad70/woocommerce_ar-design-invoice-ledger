# RELEASE CHECKLIST

## 1. Version consistency
- Check plugin header `Version`.
- Check runtime constant `AR_DESIGN_INVOICE_LEDGER_VERSION`.
- Check `VERSION` file value.
- All three must match exactly (`X.Y.Z`).

## 2. Activation and fatal-free load
- Plugin activates without fatal error.
- Submenu under PDF Invoices is visible.
- PHP log has no new fatals/warnings.

## 3. Core feature tests
- Invoice ledger page renders.
- Filters return expected rows.
- CSV export is downloadable.

## 4. Update path
- Verify Git tag format `vX.Y.Z`.
- Verify GitHub release exists.
- Verify ZIP asset `ar-design-invoice-ledger-<version>.zip` exists.
- Verify WP update detection works.

## 5. Rollback readiness
- Keep previous stable ZIP available.
- Keep previous plugin folder backup available.
- Document rollback target release.

## 6. Sign-off
- Record tested environment.
- Record tester name and timestamp.
- Confirm release approved for rollout.
