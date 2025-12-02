# Test the exact URL that the browser would access from Certificates.html
# The relative path '../backend/api.php' from ADMIN/ would be 'backend/api.php' from the root
$response = Invoke-WebRequest -Uri "http://localhost/Etracker/backend/api.php?action=all_programs" -Method GET
Write-Host "Status Code:" $response.StatusCode
Write-Host "Content-Type:" $response.Headers.'Content-Type'
Write-Host "Content Length:" $response.Content.Length

# Check if the response is valid JSON
try {
    $json = $response.Content | ConvertFrom-Json
    Write-Host "JSON is valid. Number of programs:" $json.Count
} catch {
    Write-Host "ERROR: Invalid JSON -" $_.Exception.Message
    Write-Host "First 200 chars:" $response.Content.Substring(0, [Math]::Min(200, $response.Content.Length))
}
