"""核验首页可疑文案是否在 PPT 中有依据"""
from docx import Document

doc = Document(r'f:\云科电气\云科简介(2).docx')
text = '\n'.join([p.text for p in doc.paragraphs if p.text.strip()])

# 可疑文案关键词
checks = {
    # CTA 承诺
    '24小时': ['24小时', '24 小时', '24h', '24 hours', '回复'],
    # 拉美市场
    '拉美': ['拉美', '拉丁美洲', 'Latin America'],
    # 零投诉
    '零投诉': ['零投诉', '零质量', 'zero complaint', '零投诉'],
    # ABB 授权
    'ABB授权': ['ABB授权', 'ABB 授权', 'MD-MAX', 'ABB authorized'],
    # 案例 38 天
    '38天': ['38天', '38 天', '38 days'],
    # 50MW
    '50MW': ['50MW', '50 MW', '50兆瓦'],
    # 中东核心优势
    '核心优势': ['核心优势', 'core advantage', '核心产品'],
}

for label, keywords in checks.items():
    found = []
    for kw in keywords:
        count = text.count(kw)
        if count > 0:
            found.append(f'{kw}({count}次)')
    if found:
        print(f'✅ {label}: {", ".join(found)}')
    else:
        print(f'❌ {label}: PPT 未提及')

# 打印包含"沙特"的段落，看案例细节
print('\n=== 沙特相关段落 ===')
for i, p in enumerate(doc.paragraphs):
    if '沙特' in p.text or 'Saudi' in p.text:
        print(f'P{i}: {p.text[:200]}')

print('\n=== 印尼相关段落 ===')
for i, p in enumerate(doc.paragraphs):
    if '印尼' in p.text or '印度尼西亚' in p.text or 'Indonesia' in p.text:
        print(f'P{i}: {p.text[:200]}')

print('\n=== 尼日利亚相关段落 ===')
for i, p in enumerate(doc.paragraphs):
    if '尼日利亚' in p.text or 'Nigeria' in p.text:
        print(f'P{i}: {p.text[:200]}')

print('\n=== 越南相关段落 ===')
for i, p in enumerate(doc.paragraphs):
    if '越南' in p.text or 'Vietnam' in p.text:
        print(f'P{i}: {p.text[:200]}')
