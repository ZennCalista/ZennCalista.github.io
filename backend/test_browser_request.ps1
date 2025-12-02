# Test the API endpoint directly via curl to see what the browser would get
$response = Invoke-WebRequest -Uri "http://localhost/Etracker/backend/api.php?action=all_programs" -Method GET
Write-Host "Status Code:" $response.StatusCode
Write-Host "Content-Type:" $response.Headers.'Content-Type'
Write-Host "Content Length:" $response.Content.Length
Write-Host "First 500 chars of response:"
Write-Host $response.Content.Substring(0, [Math]::Min(500, $response.Content.Length))
