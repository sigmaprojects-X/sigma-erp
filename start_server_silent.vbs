Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' Dapatkan folder direktori tempat script ini berada secara dinamis
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = scriptDir

' Cek apakah port 8008 sudah aktif
Dim checkCmd, execObj, output
checkCmd = "cmd /c netstat -ano | findstr :8008 | findstr LISTENING"
Set execObj = WshShell.Exec(checkCmd)
output = execObj.StdOut.ReadAll()

' Jika belum aktif, nyalakan php artisan serve secara silent (0 = completely hidden)
If InStr(output, "LISTENING") = 0 Then
    WshShell.Run "cmd /c php artisan serve --host=0.0.0.0 --port=8008", 0, False
End If
