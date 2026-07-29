# File 05 Release Lock — v1.0.0 RC1

## Locked source facts

- Corrective branch: `audit/file-05-source-review`
- Plugin version: `1.0.0`
- Schema version: `2`
- Plugin root: `sabri-learning/`
- Plugin files: `21`
- PHP files: `15`
- Plugin source bytes: `130503`
- Canonical source-tree SHA-256: `ec12e3a34702dbdd180382b6cd2ac61cb9b0e58394492807fadc91a7b7080b27`
- Expected release ZIP: `05-learn-sabri-classical-homeopathy-1.0.0-RC1.zip`
- Expected release ZIP bytes: `42297`
- Expected release ZIP SHA-256: `4a12cb82ec475f4e1a0e3c2561bc60ba27b0e35bb0117b11bab1c6e3e66400cf`

## Lock conditions

The release is accepted only when GitHub Actions builds a byte-identical ZIP with the locked hash from the exact corrective commit. Any source, documentation that enters the plugin ZIP, timestamp algorithm, compression setting, filename, root-folder, or package-byte change invalidates this lock and requires regeneration plus full affected retesting.

## Authorization

This lock proves reproducibility only. It does not authorize merge, staging acceptance, production, or live deployment. The full `STAGING-ACCEPTANCE.md` record must pass.
