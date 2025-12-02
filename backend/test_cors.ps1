# Test OPTIONS request (CORS preflight)
$response = Invoke-WebRequest -Uri "http://localhost/Etracker/backend/api.php?action=all_programs" -Method OPTIONS
Write-Host "OPTIONS Status:" $response.StatusCode
Write-Host "OPTIONS Headers:"
$response.Headers | Format-Table -AutoSize

# Test with Origin header (simulating browser CORS)
$headers = @{
    'Origin' = 'http://localhost'
    'Access-Control-Request-Method' = 'GET'
}
$response = Invoke-WebRequest -Uri "http://localhost/Etracker/backend/api.php?action=all_programs" -Method GET -Headers $headers
Write-Host "CORS GET Status:" $response.StatusCode
Write-Host "CORS Headers:"
$response.Headers | Select-Object 'Access-Control-Allow-Origin', 'Access-Control-Allow-Methods', 'Access-Control-Allow-Headers' | Format-Table -AutoSize
