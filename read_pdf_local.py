import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

path = '/work/云科电气设备公司简介.pdf'
print(f"===== {path} =====")

try:
    from pypdf import PdfReader
    reader = PdfReader(path)
    print(f"Pages: {len(reader.pages)}")
    for i, page in enumerate(reader.pages):
        print(f"--- Page {i+1} ---")
        print(page.extract_text())
except Exception as e:
    print(f"pypdf ERROR: {e}")

try:
    import subprocess
    subprocess.run(['pip', 'install', 'pdfplumber', '-q'], capture_output=True)
    import pdfplumber
    with pdfplumber.open(path) as pdf:
        print(f"\npdfplumber Pages: {len(pdf.pages)}")
        for i, page in enumerate(pdf.pages):
            print(f"--- Page {i+1} ---")
            print(page.extract_text())
except Exception as e:
    print(f"pdfplumber ERROR: {e}")
