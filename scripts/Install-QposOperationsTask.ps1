param([switch]$CheckOnly)
$ErrorActionPreference='Stop'
$qposRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$launcher=Join-Path $qposRoot 'scripts\Start-QposOperations.ps1'
$digest=[Security.Cryptography.SHA256]::Create().ComputeHash([Text.Encoding]::UTF8.GetBytes($qposRoot))
$taskName='QPOS-Operations-'+([BitConverter]::ToString($digest).Replace('-','').Substring(0,12))
$arguments='-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "'+$launcher+'"'
if($CheckOnly){Write-Output ('Task validated: '+$taskName);return}
$identity=[Security.Principal.WindowsIdentity]::GetCurrent().Name
$action=New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $arguments -WorkingDirectory $qposRoot
$trigger=New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$settings=New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 2) -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal=New-ScheduledTaskPrincipal -UserId $identity -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force | Out-Null
Write-Output ('Installed: '+$taskName+'; current user session required.')
