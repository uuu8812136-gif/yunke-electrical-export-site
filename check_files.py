import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

src_dir = '/docs'
files = os.listdir(src_dir)

for f in files:
    path = os.path.join(src_dir, f)
    with open(path, 'rb') as fp:
        header = fp.read(16)
    size = os.path.getsize(path)
    print(f"{f}")
    print(f"  Size: {size}")
    print(f"  Header: {header}")
    print()
