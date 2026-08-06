#!/usr/bin/env python3
from pathlib import Path
import base64, json, shutil, zlib
ROOT = Path(__file__).resolve().parent.parent
chunks = sorted((ROOT / "materialize").glob("chunk-*.txt"))
data = "".join(p.read_text(encoding="ascii") for p in chunks)
files = json.loads(zlib.decompress(base64.b85decode(data.encode("ascii"))).decode("utf-8"))
old_plugin = ROOT / "sabri-learning"
if old_plugin.exists(): shutil.rmtree(old_plugin)
old_workflow = ROOT / ".github/workflows/corrective-integrity.yml"
if old_workflow.exists(): old_workflow.unlink()
for rel, content in files.items():
    path = ROOT / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8", newline="\n")
bootstrap = ROOT / ".github/workflows/materialize-file05.yml"
if bootstrap.exists(): bootstrap.unlink()
shutil.rmtree(ROOT / "materialize")
print(f"Materialized {len(files)} governed repository files.")
