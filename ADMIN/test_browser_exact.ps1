# Calculate the exact URL that '../backend/api.php' resolves to from Certificates.html
# If Certificates.html is at /Etracker/ADMIN/Certificates.html
# Then '../backend/api.php' should resolve to /Etracker/backend/api.php

Write-Host 'Testing resolved URL: http://localhost/Etracker/backend/api.php?action=all_programs'

$response = Invoke-WebRequest -Uri 'http://localhost/Etracker/backend/api.php?action=all_programs' -Method GET -UserAgent 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
Write-Host 'Status:' $response.StatusCode
Write-Host 'Content-Type:' $response.Headers.'Content-Type'
Write-Host 'Content-Length:' $response.Content.Length

# Check if response starts with JSON
if ($response.Content.StartsWith('[{')) {
    Write-Host 'Response starts with JSON array - GOOD'
} else {
    Write-Host 'Response does NOT start with JSON array'
    Write-Host 'First 100 chars:' $response.Content.Substring(0, [Math]::Min(100, $response.Content.Length))
}
