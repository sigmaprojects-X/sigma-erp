Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' Dapatkan folder direktori tempat script ini berada secara dinamis
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = scriptDir

' Tentukan binary PHP (Gunakan bundled PHP jika tersedia)
Dim phpExe
If fso.FileExists(scriptDir & "\php\php.exe") Then
    phpExe = """" & scriptDir & "\php\php.exe"""
Else
    phpExe = "php"
End If

' Cek apakah port 8008 sudah aktif
Dim checkCmd, execObj, output
checkCmd = "cmd /c netstat -ano | findstr :8008 | findstr LISTENING"
Set execObj = WshShell.Exec(checkCmd)
output = execObj.StdOut.ReadAll()

' Jika belum aktif, nyalakan php artisan serve secara silent (0 = completely hidden)
If InStr(output, "LISTENING") = 0 Then
    WshShell.Run "cmd /c " & phpExe & " artisan serve --host=0.0.0.0 --port=8008", 0, False
End If

' Pastikan Laravel Scheduler (php artisan schedule:work) berjalan di latar belakang
Dim schedCheckCmd, schedExecObj, schedOutput
schedCheckCmd = "powershell -NoProfile -Command ""if (Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -like '*artisan*schedule:work*' }) { Write-Output 'RUNNING' }"""
Set schedExecObj = WshShell.Exec(schedCheckCmd)
schedOutput = schedExecObj.StdOut.ReadAll()

If InStr(schedOutput, "RUNNING") = 0 Then
    WshShell.Run "powershell -NoProfile -WindowStyle Hidden -Command ""$php = if (Test-Path '" & scriptDir & "\php\php.exe') { '" & scriptDir & "\php\php.exe' } else { 'php' }; $p = Start-Process -FilePath $php -ArgumentList 'artisan','schedule:work' -WorkingDirectory '" & scriptDir & "' -WindowStyle Hidden -PassThru; Set-Content -Path '" & scriptDir & "\storage\framework\scheduler.pid' -Value $p.Id -NoNewline""", 0, False
End If
