#!/usr/bin/env python3
from __future__ import annotations
import argparse
import hashlib
import json
import re
import stat
import zipfile
from pathlib import Path

ROOT = '05-learn-sabri-classical-homeopathy'
EPOCH = (2026, 8, 10, 0, 0, 0)
CREATED = '2026-08-10T00:00:00Z'


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def plugin_version(plugin: Path) -> str:
    main = (plugin / 'learn-sabri-classical-homeopathy.php').read_text(encoding='utf-8')
    match = re.search(r'^\s*\*\s*Version:\s*([^\s]+)', main, re.MULTILINE)
    if not match:
        raise SystemExit('Plugin Version header is missing.')
    return match.group(1).strip()


def zip_entry(path: str, data: bytes) -> tuple[zipfile.ZipInfo, bytes]:
    info = zipfile.ZipInfo(path, EPOCH)
    info.create_system = 3
    info.external_attr = (stat.S_IFREG | 0o644) << 16
    info.compress_type = zipfile.ZIP_DEFLATED
    return info, data


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument('--root', default='.')
    ap.add_argument('--output', required=True)
    args = ap.parse_args()

    base = Path(args.root).resolve()
    plugin = base / ROOT
    if not plugin.is_dir():
        raise SystemExit(f'Missing {plugin}')

    version = plugin_version(plugin)
    output = Path(args.output).resolve()
    output.parent.mkdir(parents=True, exist_ok=True)

    paths = sorted(
        (p for p in plugin.rglob('*') if p.is_file()),
        key=lambda p: p.relative_to(plugin).as_posix(),
    )
    source = [(p.relative_to(plugin).as_posix(), p.read_bytes()) for p in paths]
    source_hashes = {rel: sha256(data) for rel, data in source}
    source_tree_digest = sha256(
        ''.join(f'{source_hashes[rel]}  {rel}\n' for rel in sorted(source_hashes)).encode('utf-8')
    )

    sbom_obj = {
        'spdxVersion': 'SPDX-2.3',
        'dataLicense': 'CC0-1.0',
        'SPDXID': 'SPDXRef-DOCUMENT',
        'name': f'{ROOT}-{version}',
        'documentNamespace': f'https://sabrihomeopathy.com/spdx/file05/{version}/{source_tree_digest}',
        'creationInfo': {
            'created': CREATED,
            'creators': ['Organization: Sabri Social Homeopathy Platform'],
        },
        'packages': [{
            'name': 'Learn Sabri Classical Homeopathy',
            'SPDXID': 'SPDXRef-Package-File05',
            'versionInfo': version,
            'downloadLocation': 'NOASSERTION',
            'filesAnalyzed': True,
            'licenseConcluded': 'GPL-2.0-or-later',
            'licenseDeclared': 'GPL-2.0-or-later',
            'copyrightText': 'NOASSERTION',
            'checksums': [{'algorithm': 'SHA256', 'checksumValue': source_tree_digest}],
        }],
        'files': [
            {
                'fileName': rel,
                'SPDXID': f"SPDXRef-File-{hashlib.sha256(rel.encode('utf-8')).hexdigest()[:24]}",
                'checksums': [{'algorithm': 'SHA256', 'checksumValue': source_hashes[rel]}],
                'licenseConcluded': 'NOASSERTION',
                'copyrightText': 'NOASSERTION',
            }
            for rel in sorted(source_hashes)
        ],
        'relationships': [
            {
                'spdxElementId': 'SPDXRef-DOCUMENT',
                'relationshipType': 'DESCRIBES',
                'relatedSpdxElement': 'SPDXRef-Package-File05',
            }
        ],
        'annotations': [{
            'annotationDate': CREATED,
            'annotationType': 'OTHER',
            'annotator': 'Organization: Sabri Social Homeopathy Platform',
            'comment': 'Deterministic release SBOM generated from the exact File 05 plugin source tree; staging/live status is not implied.',
        }],
    }
    sbom = (json.dumps(sbom_obj, ensure_ascii=False, sort_keys=True, separators=(',', ':')) + '\n').encode('utf-8')

    manifest_lines = [f'{source_hashes[rel]}  {rel}' for rel in sorted(source_hashes)]
    manifest_lines.append(f'{sha256(sbom)}  SBOM.spdx.json')
    manifest = ('\n'.join(manifest_lines) + '\n').encode('utf-8')

    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
        for rel, data in source:
            info, payload = zip_entry(f'{ROOT}/{rel}', data)
            zf.writestr(info, payload)
        for rel, data in [('SBOM.spdx.json', sbom), ('MANIFEST.sha256', manifest)]:
            info, payload = zip_entry(f'{ROOT}/{rel}', data)
            zf.writestr(info, payload)

    digest = sha256(output.read_bytes())
    output.with_suffix(output.suffix + '.sha256').write_text(
        f'{digest}  {output.name}\n', encoding='utf-8', newline='\n'
    )
    print(f'{output.name}\t{output.stat().st_size}\t{digest}\tplugin={version}\tsource={source_tree_digest}')


if __name__ == '__main__':
    main()
