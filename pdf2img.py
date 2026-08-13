import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

try:
    from pdf2image import convert_from_path
    images = convert_from_path('/work/云科电气设备公司简介.pdf', dpi=150)
    print(f"Pages: {len(images)}")
    for i, img in enumerate(images):
        img.save(f'/work/pdf_page_{i+1}.png')
        print(f"Saved page {i+1}")
except Exception as e:
    print(f"ERROR: {e}")
    # Try alternative: use PyMuPDF
    try:
        import subprocess
        subprocess.run(['pip', 'install', 'PyMuPDF', '-q'], capture_output=True)
        import fitz
        doc = fitz.open('/work/云科电气设备公司简介.pdf')
        print(f"PyMuPDF Pages: {len(doc)}")
        for i, page in enumerate(doc):
            pix = page.get_pixmap(dpi=150)
            pix.save(f'/work/pdf_page_{i+1}.png')
            print(f"Saved page {i+1}")
    except Exception as e2:
        print(f"PyMuPDF ERROR: {e2}")
