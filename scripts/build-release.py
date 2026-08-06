#!/usr/bin/env python3
from __future__ import annotations
import argparse, hashlib, os, stat, zipfile
from pathlib import Path
ROOT='05-learn-sabri-classical-homeopathy'
EPOCH=(2026,8,6,0,0,0)

def main():
    ap=argparse.ArgumentParser(); ap.add_argument('--root',default='.'); ap.add_argument('--output',required=True); args=ap.parse_args()
    base=Path(args.root).resolve(); plugin=base/ROOT
    if not plugin.is_dir(): raise SystemExit(f'Missing {plugin}')
    output=Path(args.output).resolve(); output.parent.mkdir(parents=True,exist_ok=True)
    paths=sorted((p for p in plugin.rglob('*') if p.is_file()),key=lambda p:p.relative_to(base).as_posix())
    with zipfile.ZipFile(output,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for path in paths:
            rel=path.relative_to(base).as_posix(); info=zipfile.ZipInfo(rel,EPOCH); info.create_system=3
            info.external_attr=(stat.S_IFREG|0o644)<<16; info.compress_type=zipfile.ZIP_DEFLATED
            z.writestr(info,path.read_bytes())
    digest=hashlib.sha256(output.read_bytes()).hexdigest(); output.with_suffix(output.suffix+'.sha256').write_text(f'{digest}  {output.name}\n',encoding='utf-8',newline='\n')
    print(f'{output.name}\t{output.stat().st_size}\t{digest}')
if __name__=='__main__': main()
