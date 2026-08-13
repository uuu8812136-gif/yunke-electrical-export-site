<#
.SYNOPSIS
    YKE smoke + hard-constraint E2E tests
.DESCRIPTION
    Scope: A smoke (S01-S07) + B contact (C01-C06) + C case (K05/K07/K08)
    Browser-render checks (K01-K04/K06) handled by tests/playwright-mcp/cases.md
    Exit code: 0=all PASS, 1=any FAIL
    Output: PASS:/FAIL: lines
#>

$ErrorActionPreference = 'SilentlyContinue'
$BASE = 'http://localhost:8081'
$script:PASS = 0
$script:FAIL = 0

function Check($Id, $Ok, $Detail) {
    if ($Ok) {
        Write-Host "PASS: $Id $Detail"
        $script:PASS++
    } else {
        Write-Host "FAIL: $Id $Detail"
        $script:FAIL++
    }
}

function Get-Status($Path) {
    $url = "$BASE$Path"
    $line = & curl.exe -s -o NUL -w "%{http_code}|%{redirect_url}" $url
    $parts = $line -split '\|', 2
    return @{ Code = $parts[0]; Redirect = if ($parts.Length -gt 1) { $parts[1] } else { '' } }
}

function Get-Html($Path) {
    return & curl.exe -s "$BASE$Path"
}

function Count-Match($Html, $Pattern) {
    $m = [regex]::Matches($Html, $Pattern)
    return $m.Count
}

Write-Host "=== YKE Smoke + Hard-Constraint Tests ==="
Write-Host "Target: $BASE"
Write-Host ""

# ============ A. Smoke ============
$s = Get-Status '/'
Check 'S01' ($s.Code -eq '200') "home=$($s.Code)"

$h = Get-Html '/zh/'
Check 'S02' ($h -and $h -match 'lang="zh') "zh page lang"

$s = Get-Status '/products/'
# /products/ is the products overview page (build_products_overview_content), expects 200
Check 'S03' ($s.Code -eq '200') "products=$($s.Code) (overview page)"

$s = Get-Status '/products/mv-switchgear/'
Check 'S04' ($s.Code -eq '200') "mv-switchgear=$($s.Code)"

$h = Get-Html '/case-studies/'
$cardCount = if ($h) { Count-Match $h '(<article class="yke-case-card")' } else { 0 }
Check 'S05' ($h -and $cardCount -ge 4) "cases cards=$cardCount"

$h = Get-Html '/contact/'
$formOk = $h -and ($h -match 'yke-inquiry-shell|wpcf7-form|yke-form-grid')
Check 'S06' $formOk "contact form present"

$s = Get-Status '/distributor-partnership/'
Check 'S07' ($s.Code -eq '301' -and $s.Redirect -match '/contact/') "distributor=$($s.Code) -> $($s.Redirect)"

# ============ B. Contact hard constraints ============
$hEn = Get-Html '/contact/'
$hZh = Get-Html '/zh/contact/'

# C02: zh company name (use Unicode escapes to avoid PS5 ANSI source encoding issues)
$zhCompanyName = -join @([char]0x5E7F,[char]0x5DDE,[char]0x4E91,[char]0x79D1,[char]0x7535,[char]0x6C14,[char]0x8BBE,[char]0x5907,[char]0x6709,[char]0x9650,[char]0x516C,[char]0x53F8)
$tmpFile = [System.IO.Path]::GetTempFileName()
& curl.exe -s "$BASE/zh/contact/" -o $tmpFile
$hZhUtf8 = Get-Content $tmpFile -Raw -Encoding UTF8
Remove-Item $tmpFile -Force
$c02 = $hZhUtf8 -and ($hZhUtf8 -match $zhCompanyName)
Check 'C02' $c02 "zh company name"

# C03: en company name
$c03 = $hEn -and ($hEn -match 'Guangzhou Yunke Electrical Equipment Co\., Ltd\.')
Check 'C03' $c03 "en company name"

# C04: legacy intro/form body removed
$c04 = $hEn -and -not ($hEn -match 'We typically respond|Fill out the form below|reach our team')
Check 'C04' $c04 "no legacy intro/form body copy"

# C05: no shared CTA banner (page-contact.html dedicated template)
$c05 = $hEn -and -not ($hEn -match 'shared-inquiry-cta|yke-cta-banner')
Check 'C05' $c05 "no shared CTA banner"

# C06: placeholder contact values filtered
$c06 = $hEn -and -not ($hEn -match '\+1\s?555|123 Main St|placeholder@example\.com|000-000')
Check 'C06' $c06 "placeholder contact values filtered"

# ============ C. Case hard constraints (HTML/source-checkable) ============
$h = Get-Html '/case-studies/'

# K05: card structure (region/title/excerpt/supply/result/footer)
$hasRegion  = $h -and ($h -match 'yke-case-card-region')
$hasTitle   = $h -and ($h -match 'yke-case-card-title')
$hasExcerpt = $h -and ($h -match 'yke-case-card-excerpt')
$hasSupply  = $h -and ($h -match 'yke-case-card-supply')
$hasResult  = $h -and ($h -match 'yke-case-card-result')
$hasFooter  = $h -and ($h -match 'yke-case-card-footer')
Check 'K05' ($hasRegion -and $hasTitle -and $hasExcerpt -and $hasSupply -and $hasResult -and $hasFooter) "card structure 6/6"

# K07: no 3D tilt on .yke-case-card in site.css
$cssPath = Join-Path $PSScriptRoot '..\..\wordpress\wp-content\themes\yke-global\assets\css\site.css'
$cssOk = $true
if (Test-Path $cssPath) {
    $css = Get-Content $cssPath -Raw -Encoding UTF8
    $tiltMatches = [regex]::Matches($css, '\.yke-case-card[^{]*\{[^}]*?(perspective|preserve-3d|rotateX|rotateY)[^}]*?\}')
    if ($tiltMatches.Count -gt 0) {
        $cssOk = $false
    }
}
Check 'K07' $cssOk "no 3D tilt on case-card in site.css"

# K08: no wpautop pollution around yke-case-card
$k08 = $true
if ($h) {
    if ($h -match '<p>\s*</article>|<article class="yke-case-card">\s*<p>') {
        $k08 = $false
    }
}
Check 'K08' $k08 "no wpautop pollution around yke-case-card"

# ============ D. Full-page structure coverage (status codes via curl) ============
# Detailed DOM assertions handled by render-tests.js; here only HTTP + key marker checks
Write-Host ""
Write-Host "--- D. Full-page structure (HTTP + markers) ---"

# D01-D04: 4 other product lines
$productLines = @(
    @{ id='D01'; path='/products/lv-switchgear/'; name='LV Switchgear' },
    @{ id='D02'; path='/products/transformers/'; name='Transformers' },
    @{ id='D03'; path='/products/new-energy-electrical/'; name='New Energy' },
    @{ id='D04'; path='/products/prefabricated-substations/'; name='Prefabricated' }
)
foreach ($p in $productLines) {
    $s = Get-Status $p.path
    Check $p.id ($s.Code -eq '200') "$($p.name)=$($s.Code)"
}

# D05: About page
$s = Get-Status '/about-yke/'
Check 'D05' ($s.Code -eq '200') "about-yke=$($s.Code)"

# D06-D09: 4 case study detail pages (EN)
$cases = @(
    @{ id='D06'; path='/case-studies/saudi-arabia-industrial-park-power-distribution-project/'; name='Saudi' },
    @{ id='D07'; path='/case-studies/vietnam-manufacturing-group-factory-distribution-upgrade/'; name='Vietnam' },
    @{ id='D08'; path='/case-studies/nigeria-grid-upgrade-transformer-supply/'; name='Nigeria' },
    @{ id='D09'; path='/case-studies/indonesia-50mw-rooftop-solar-grid-connection-package/'; name='Indonesia' }
)
foreach ($c in $cases) {
    $s = Get-Status $c.path
    Check $c.id ($s.Code -eq '200') "$($c.name)=$($s.Code)"
}

# D10-D14: Chinese page parity
$zhPages = @(
    @{ id='D10'; path='/zh/products/'; name='zh-products' },
    @{ id='D11'; path='/zh/products/lv-switchgear/'; name='zh-lv' },
    @{ id='D12'; path='/zh/about-yke/'; name='zh-about' },
    @{ id='D13'; path='/zh/case-studies/'; name='zh-cases' },
    @{ id='D14'; path='/zh/products/mv-switchgear/'; name='zh-mv' }
)
foreach ($z in $zhPages) {
    $s = Get-Status $z.path
    Check $z.id ($s.Code -eq '200') "$($z.name)=$($s.Code)"
}

# D15: sitemap_index.xml (Yoast)
$s = Get-Status '/sitemap_index.xml'
$h = Get-Html '/sitemap_index.xml'
$sitemapOk = ($s.Code -eq '200') -and ($h -match 'sitemapindex') -and ($h -match 'page-sitemap\.xml')
Check 'D15' $sitemapOk "sitemap_index=$($s.Code) hasSitemap=$($h -match 'sitemapindex')"

# D16: wp-sitemap.xml redirects to Yoast (301)
$s = Get-Status '/wp-sitemap.xml'
Check 'D16' ($s.Code -eq '301' -or $s.Code -eq '200') "wp-sitemap=$($s.Code) (Yoast takeover)"

# ============ Summary ============
Write-Host ""
Write-Host "=== Summary ==="
Write-Host "PASS: $script:PASS"
Write-Host "FAIL: $script:FAIL"
$total = $script:PASS + $script:FAIL
Write-Host "Total: $total"

if ($script:FAIL -gt 0) {
    exit 1
}
exit 0
