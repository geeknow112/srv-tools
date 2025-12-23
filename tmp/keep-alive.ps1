# PC自動キープアライブスクリプト
# ScrollLockキーをランダム間隔で押してスクリーンロックを防ぐ

Add-Type -AssemblyName System.Windows.Forms

Write-Host "キープアライブスクリプト開始 - Ctrl+Cで停止"
Write-Host "30-60秒のランダム間隔でScrollLockキーを送信します"

try {
    while ($true) {
        # ScrollLockを2回押して元の状態に戻す
        [System.Windows.Forms.SendKeys]::SendWait("{SCROLLLOCK}")
        Start-Sleep -Milliseconds 100
        [System.Windows.Forms.SendKeys]::SendWait("{SCROLLLOCK}")
        
        # ランダムな間隔を生成 (30-60秒)
        $randomInterval = Get-Random -Minimum 30 -Maximum 61
        
        $currentTime = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        Write-Host "[$currentTime] キープアライブ信号送信 - 次回まで${randomInterval}秒"
        
        Start-Sleep -Seconds $randomInterval
    }
}
catch {
    Write-Host "スクリプトが停止されました"
}