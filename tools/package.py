"""Create a reproducible Blueprint ZIP with package files at the archive root."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo
import hashlib
import argparse

parser = argparse.ArgumentParser()
parser.add_argument("edition", nargs="?", choices=["bedrock"], default="bedrock")
edition = parser.parse_args().edition
project_root = Path(__file__).resolve().parent.parent
root = project_root
identifier = "bedrockplayers"
output = project_root / "dist" / (identifier + ".blueprint")
output.parent.mkdir(exist_ok=True)
files = [root / "conf.yml", root / "README.md", root / "LICENSE", root / "VALIDATION.md"]
directories = ["admin", "app", "assets", "components", "private", "routes"]
if edition == "bedrock": directories.append("bridges")
for directory in directories:
    files.extend(path for path in (root / directory).rglob("*") if path.is_file() and "__pycache__" not in path.parts)
with ZipFile(output, "w", compression=ZIP_DEFLATED, compresslevel=9) as archive:
    for path in sorted(files):
        name = path.relative_to(root).as_posix()
        content = path.read_bytes()
        if path.suffix.lower() in {".yml", ".yaml", ".md", ".php", ".ts", ".tsx", ".css", ".svg", ".mjs"} or path.name == "LICENSE":
            content = content.replace(b"\r\n", b"\n")
        info = ZipInfo(name, date_time=(2026, 10, 13, 0, 0, 0))
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        archive.writestr(info, content)
digest = hashlib.sha256(output.read_bytes()).hexdigest()
(output.parent / (identifier + ".blueprint.sha256")).write_text(f"{digest}  {identifier}.blueprint\n", encoding="utf-8")
print(f"Packaged {len(files)} files: {output}")
print(f"SHA256: {digest}")
