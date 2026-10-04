# =====================================================
# ngrok-start.ps1 — Auto-start ngrok & update .env
# Jalankan: .\ngrok-start.ps1
# =====================================================

$port = 8000
$envFile = Join-Path $PSScriptRoot ".env"

Write-Host "🚀 Memulai ngrok tunnel ke port $port..." -ForegroundColor Cyan

# Matikan ngrok yang sudah berjalan (jika ada)
Get-Process -Name "ngrok" -ErrorAction SilentlyContinue | Stop-Process -Force

# Jalankan ngrok di background
Start-Process -FilePath "ngrok" -ArgumentList "http $port" -WindowStyle Hidden

# Tunggu ngrok siap
Write-Host "⏳ Menunggu ngrok siap..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

# Ambil URL publik dari ngrok API
try {
    $tunnels = (Invoke-RestMethod -Uri "http://localhost:4040/api/tunnels").tunnels
    $publicUrl = $tunnels | Where-Object { $_.proto -eq "https" } | Select-Object -First 1 -ExpandProperty public_url

    if (-not $publicUrl) {
        $publicUrl = $tunnels | Select-Object -First 1 -ExpandProperty public_url
    }

    if ($publicUrl) {
        Write-Host "✅ URL Ngrok: $publicUrl" -ForegroundColor Green

        # Update APP_URL di .env
        $envContent = Get-Content $envFile -Raw
        $envContent = $envContent -replace "APP_URL=.*", "APP_URL=$publicUrl"
        Set-Content $envFile -Value $envContent -NoNewline

        Write-Host "✅ APP_URL di .env sudah diupdate!" -ForegroundColor Green
        Write-Host ""
        Write-Host "📱 Buka di HP / browser:" -ForegroundColor White
        Write-Host "   $publicUrl" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "⚠️  Jangan tutup jendela ini selama ngrok aktif." -ForegroundColor DarkYellow

        # Buka URL di browser otomatis (opsional)
        # Start-Process $publicUrl

    } else {
        Write-Host "❌ Gagal mendapatkan URL ngrok. Coba jalankan ulang." -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
    Write-Host "Pastikan ngrok sudah terinstall dan port $port sedang berjalan." -ForegroundColor Yellow
}
