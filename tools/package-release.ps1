<#
.SYNOPSIS
  Assemble the deployable release artifacts (docs/launch.md sect. 2).

.DESCRIPTION
  Stages clean copies of the theme and plugin — honouring the launch
  runbook's include/exclude list — and zips each into release/. The zips
  are what you upload: wp-admin (Appearance/Plugins -> Add New -> Upload)
  or extract over wp-content via SFTP/File Manager.

  Read-only against the source tree; writes only under release/.
  Run AFTER `npm run theme:build` so build/ is fresh.

.EXAMPLE
  pwsh tools/package-release.ps1
#>

$ErrorActionPreference = 'Stop'
$root  = Split-Path $PSScriptRoot -Parent
$stage = Join-Path $root 'release\_stage'
$out   = Join-Path $root 'release'

$themeVer  = (Select-String -Path (Join-Path $root 'wp-content\themes\skirttique\style.css') -Pattern 'Version:\s*(.+)').Matches.Groups[1].Value.Trim()
$pluginVer = (Select-String -Path (Join-Path $root 'wp-content\plugins\skirttique-core\skirttique-core.php') -Pattern 'Version:\s*(.+)').Matches.Groups[1].Value.Trim()

# Clean slate.
if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force -Path $stage | Out-Null

# --- Theme: ship build/ + assets, drop dev-only weight. ------------------
$themeSrc   = Join-Path $root 'wp-content\themes\skirttique'
$themeStage = Join-Path $stage 'skirttique'
# /XD excludes directories; the theme's src/ and node_modules/ never ship.
robocopy $themeSrc $themeStage /E /NFL /NDL /NJH /NJS /NP `
  /XD (Join-Path $themeSrc 'node_modules') (Join-Path $themeSrc 'src') `
      (Join-Path $themeSrc '.cache') (Join-Path $themeSrc 'tools') | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy (theme) failed: $LASTEXITCODE" }

# --- Plugin: src/ IS the code; drop only the test harness. ---------------
$pluginSrc   = Join-Path $root 'wp-content\plugins\skirttique-core'
$pluginStage = Join-Path $stage 'skirttique-core'
robocopy $pluginSrc $pluginStage /E /NFL /NDL /NJH /NJS /NP `
  /XD (Join-Path $pluginSrc 'tests') (Join-Path $pluginSrc '.phpunit.cache') `
  /XF (Join-Path $pluginSrc 'phpunit.xml.dist') | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy (plugin) failed: $LASTEXITCODE" }

# --- Zip each (folder at archive root, ready to drop into wp-content). ---
$themeZip  = Join-Path $out "skirttique-theme-$themeVer.zip"
$pluginZip = Join-Path $out "skirttique-core-$pluginVer.zip"
Remove-Item $themeZip, $pluginZip -ErrorAction SilentlyContinue
Compress-Archive -Path $themeStage  -DestinationPath $themeZip  -Force
Compress-Archive -Path $pluginStage -DestinationPath $pluginZip -Force

Remove-Item $stage -Recurse -Force

Write-Host ''
Write-Host 'Release artifacts:'
Get-ChildItem $out -Filter *.zip | ForEach-Object {
  '{0,-34} {1,8:N0} KB' -f $_.Name, ($_.Length / 1KB)
}
Write-Host ''
Write-Host "theme $themeVer  |  plugin $pluginVer"
