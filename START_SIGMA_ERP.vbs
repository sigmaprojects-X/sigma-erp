Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' Dapatkan folder direktori kerja secara dinamis
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = scriptDir

' 1. Cek apakah server sudah aktif di port 8008
Dim checkCmd, execObj, output
checkCmd = "cmd /c netstat -ano | findstr :8008 | findstr LISTENING"
Set execObj = WshShell.Exec(checkCmd)
output = execObj.StdOut.ReadAll()

' Jika belum aktif, hidupkan server secara otomatis di background tanpa jendela hitam
If InStr(output, "LISTENING") = 0 Then
    WshShell.Run "cmd /c php artisan serve --host=0.0.0.0 --port=8008", 0, False
    WScript.Sleep 1500
End If

' 2. Buka dashboard utama ERP di browser default
WshShell.Run "http://localhost:8008", 1, False
