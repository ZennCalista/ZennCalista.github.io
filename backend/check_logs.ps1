# Check PHP error logs
$errorLog = 'C:\xampp\php\logs\php_error_log'
if (Test-Path $errorLog) {
    Write-Host 'Recent PHP errors:'
    Get-Content $errorLog -Tail 10
} else {
    Write-Host 'No PHP error log found at' $errorLog
}

# Check Apache error log
$apacheLog = 'C:\xampp\apache\logs\error.log'
if (Test-Path $apacheLog) {
    Write-Host 'Recent Apache errors:'
    Get-Content $apacheLog -Tail 10
} else {
    Write-Host 'No Apache error log found at' $apacheLog
}
