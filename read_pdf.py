import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# Try multiple PDF libraries
pdf_path = '/work/云科电气设备公司简介.pdf'
print(f"===== {pdf_path} =====")

# Check if file exists
import os
if not os.path.exists(pdf_path):
    # Try listing work dir
    for f in os.listdir('/work'):
        if f.endswith('.pdf'):
            pdf_path = os.path.join('/work', f)
            print(f"Found: {pdf_path}")
            break

# Check file header
with open(pdf_path, 'rb') as f:
    header = f.read(20)
    print(f"Header: {header}")

# Try pypdf
try:
    from pypdf import PdfReader
    reader = PdfReader(pdf_path)
    print(f"Pages: {len(reader.pages)}")
    for i, page in enumerate(reader.pages):
        print(f"--- Page {i+1} ---")
        print(page.extract_text())
except Exception as e:
    print(f"pypdf ERROR: {e}")

# Try pdfplumber
try:
    import subprocess
    subprocess.run(['pip', 'install', 'pdfplumber', '-q'], capture_output=True)
    import pdfplumber
    with pdfplumber.open(pdf_path) as pdf:
        print(f"\npdfplumber Pages: {len(pdf.pages)}")
        for i, page in enumerate(pdf.pages):
            print(f"--- Page {i+1} ---")
            print(page.extract_text())
except Exception as e:
    print(f"pdfplumber ERROR: {e}")
