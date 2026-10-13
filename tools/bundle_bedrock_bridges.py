"""Bundle optional game-server exporters and their source, excluding SDK/build caches."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import hashlib

root = Path(__file__).resolve().parent.parent
output = root / "dist" / "bedrock-snapshot-exporters.zip"
artifacts = ["bedrock-snapshots-nukkit-1.0.0.jar", "endstone_bedrock_snapshots-1.0.0-py3-none-any.whl", "bedrock-snapshots-pocketmine-1.0.0.phar"]
with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for name in artifacts:
        archive.write(root / "dist" / name, name)
    archive.write(root / "bridges/README.md", "README.md")
    archive.write(root / "LICENSE", "LICENSE")
    for path in (root / "bridges").rglob("*"):
        if path.is_file() and path.suffix in {".py", ".java", ".php", ".yml", ".toml"}:
            archive.write(path, "source/" + path.relative_to(root / "bridges").as_posix())
with ZipFile(output) as archive:
    assert archive.testzip() is None
digest = hashlib.sha256(output.read_bytes()).hexdigest()
output.with_suffix(".zip.sha256").write_text(f"{digest}  {output.name}\n", encoding="utf-8")
print(output)
