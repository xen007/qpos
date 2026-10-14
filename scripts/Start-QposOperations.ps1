param([switch]$CheckOnly,[string]$PhpPath='C:\xampp\php\php.exe')
$ErrorActionPreference='Stop'
$qposRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$artisan=Join-Path $qposRoot 'artisan'
if(!(Test-Path -LiteralPath $PhpPath -PathType Leaf) -or !(Test-Path -LiteralPath $artisan -PathType Leaf)){throw 'PHP or artisan missing.'}
$definitions=@(
    @{Name='scheduler';Args=@('schedule:work')},
    @{Name='notifications';Args=@('queue:work','notification_database','--queue=qpos-notifications','--sleep=3','--tries=1','--timeout=90')},
    @{Name='backups';Args=@('queue:work','backup_database','--queue=qpos-backups','--sleep=5','--tries=1','--timeout=1200')}
)
$processes=@(Get-CimInstance Win32_Process -Filter "Name='php.exe'")
foreach($definition in $definitions){
    $pattern=[regex]::Escape($artisan)+'.*'+[regex]::Escape(($definition.Args -join ' '))
    $running=@($processes | Where-Object { $_.ExecutablePath -eq $PhpPath -and $_.CommandLine -match $pattern })
    if($CheckOnly){Write-Output ($definition.Name+': command validated; running='+$running.Count);continue}
    if($running.Count -gt 0){Write-Output ($definition.Name+': already running');continue}
    $arguments=@('"'+$artisan+'"')+$definition.Args
    $process=Start-Process -FilePath $PhpPath -ArgumentList $arguments -WorkingDirectory $qposRoot -WindowStyle Hidden -PassThru
    Write-Output ($definition.Name+': started PID '+$process.Id)
}
