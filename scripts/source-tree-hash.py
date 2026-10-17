#!/usr/bin/env python3
from __future__ import annotations
import argparse, hashlib
from pathlib import Path

ROOT = '05-learn-sabri-classical-homeopathy'

def files(base: Path):
    root = base / ROOT
    if not root.is_dir():
        raise SystemExit(f'Missing plugin root: {root}')
    return sorted((p for p in root.rglob('*') if p.is_file()), key=lambda p: p.relative_to(base).as_posix())

def manifest(base: Path) -> str:
    lines=[]
    for p in files(base):
        rel=p.relative_to(base).as_posix()
        lines.append(f'{hashlib.sha256(p.read_bytes()).hexdigest()}  {rel}')
    return '\n'.join(lines)+'\n'

def main():
    ap=argparse.ArgumentParser(); ap.add_argument('--root',default='.'); ap.add_argument('--manifest'); args=ap.parse_args()
    base=Path(args.root).resolve(); text=manifest(base); digest=hashlib.sha256(text.encode()).hexdigest()
    if args.manifest: Path(args.manifest).write_text(text,encoding='utf-8',newline='\n')
    print(digest)
if __name__=='__main__': main()
