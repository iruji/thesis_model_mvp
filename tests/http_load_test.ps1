# Sends 50 concurrent requests and measures response time
$url = "http://localhost/thesis_model/api.php?action=chat"
$body = '{"message":"how much is tuition","session_id":"load-test"}'
$jobs = @()

$start = Get-Date

1..50 | ForEach-Object {
    $jobs += Start-Job -ScriptBlock {
        param($url, $body)
        $start = Get-Date
        Invoke-RestMethod -Uri $url -Method POST `
            -ContentType "application/json" -Body $body | Out-Null
        ((Get-Date) - $start).TotalMilliseconds
    } -ArgumentList $url, $body
}

$results = $jobs | Wait-Job | Receive-Job
$jobs | Remove-Job

$total = (Get-Date) - $start
$avg   = ($results | Measure-Object -Average).Average

Write-Host "50 concurrent requests completed"
Write-Host "Total time: $([math]::Round($total.TotalMilliseconds))ms"
Write-Host "Average response: $([math]::Round($avg, 2))ms"
Write-Host "Min: $([math]::Round(($results | Measure-Object -Minimum).Minimum, 2))ms"
Write-Host "Max: $([math]::Round(($results | Measure-Object -Maximum).Maximum, 2))ms"