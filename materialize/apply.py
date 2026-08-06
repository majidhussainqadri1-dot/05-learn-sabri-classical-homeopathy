#!/usr/bin/env python3
from pathlib import Path
import base64, json, shutil, zlib

ROOT = Path(__file__).resolve().parent.parent
chunks = sorted((ROOT / "materialize").glob("chunk-*.txt"))
data = "".join(p.read_text(encoding="ascii") for p in chunks)
files = json.loads(zlib.decompress(base64.b85decode(data.encode("ascii"))).decode("utf-8"))

# Workflow files are committed separately through the authorized GitHub connector.
files = {path: content for path, content in files.items() if not path.startswith(".github/workflows/")}

old_plugin = ROOT / "sabri-learning"
if old_plugin.exists():
    shutil.rmtree(old_plugin)

for rel, content in files.items():
    path = ROOT / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8", newline="\n")

shutil.rmtree(ROOT / "materialize")
print(f"Materialized {len(files)} governed non-workflow repository files.")
