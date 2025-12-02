# Find PHP and Apache log files
Get-ChildItem -Path C:\xampp -Filter *.log -Recurse | Select-Object FullName | Select-Object -First 10
