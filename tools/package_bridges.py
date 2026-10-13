"""Build the pure-Python Endstone wheel using standard-library tools only."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import base64
import csv
import hashlib
import io

root = Path(__file__).resolve().parent.parent
source = root / "bridges" / "endstone"
output = root / "dist" / "endstone_bedrock_snapshots-1.0.0-py3-none-any.whl"
output.parent.mkdir(exist_ok=True)
info = "endstone_bedrock_snapshots-1.0.0.dist-info"
files = {path.relative_to(source).as_posix(): path.read_bytes().replace(b"\r\n", b"\n")
         for path in (source / "endstone_bedrock_snapshots").glob("*.py")}
files[info + "/METADATA"] = b"Metadata-Version: 2.1\nName: endstone-bedrock-snapshots\nVersion: 1.0.0\nSummary: Read-only Aquadactyl Bedrock player snapshots\nAuthor: RepGraphics\nRequires-Python: >=3.10\nLicense: BSD-2-Clause\n\n"
files[info + "/WHEEL"] = b"Wheel-Version: 1.0\nGenerator: Aquadactyl snapshot builder\nRoot-Is-Purelib: true\nTag: py3-none-any\n"
files[info + "/entry_points.txt"] = b"[endstone]\nbedrock-snapshots = endstone_bedrock_snapshots:BedrockSnapshots\n"
files[info + "/LICENSE"] = (root / "LICENSE").read_bytes().replace(b"\r\n", b"\n")
records = io.StringIO(newline="")
writer = csv.writer(records)
for name, data in files.items():
    digest = base64.urlsafe_b64encode(hashlib.sha256(data).digest()).rstrip(b"=").decode("ascii")
    writer.writerow([name, "sha256=" + digest, len(data)])
writer.writerow([info + "/RECORD", "", ""])
files[info + "/RECORD"] = records.getvalue().encode("utf-8")
with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for name, data in files.items():
        archive.writestr(name, data)
with ZipFile(output) as archive:
    assert archive.testzip() is None
print(output)
