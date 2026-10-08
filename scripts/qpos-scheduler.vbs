Option Explicit
Dim shell, fs, scriptPath
Set shell = CreateObject("WScript.Shell")
Set fs = CreateObject("Scripting.FileSystemObject")
scriptPath = fs.BuildPath(fs.GetParentFolderName(WScript.ScriptFullName), "qpos-scheduler.cmd")
shell.Run """" & scriptPath & """", 0, True
