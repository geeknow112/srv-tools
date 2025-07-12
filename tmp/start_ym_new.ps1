param(
    [string]$Group, 
    [string]$Name,          # 名前を受け取る文字列型のパラメータ
    [int]$Age = 30,         # 年齢を受け取る整数型のパラメータ (デフォルト値30)
    [switch]$VerboseOutput  # 詳細な出力を制御するスイッチパラメータ
)

if ($VerboseOutput) {
    Write-Host ""詳細モードが有効です。""
}

$hidemaru = 'C:\Program Files (x86)\Hidemaru\Hidemaru.exe'

# include config
. "..\urls.ps1"

#Start-Process -FilePath chrome -ArgumentList '--incognito', '--new-window'
#Start-Process -FilePath chrome -ArgumentList '--new-window'
# Start-Process -FilePath chrome -ArgumentList ""--new-window"", ""https://www.google.com"", ""https://www.yahoo.co.jp"", ""https://www.bing.com""

# Chromeに渡す引数リストを構築
# --new-window を先頭に追加し、その後にすべてのURLを追加
$chromeArguments = @("--incognito", "--new-window") + $urls
# 構築した引数リストを一度に渡してChromeを起動
#Start-Process -FilePath chrome -ArgumentList $chromeArguments

switch ($Group) {
    'url0' {
        # 
    }
    'url1' {
        # 
        $arg_att = @("--incognito", "--new-window") + $urls_att
        Start-Process -FilePath chrome -ArgumentList $arg_att
    }
    'url2' {
        # 
        $arg_att2 = @("--incognito", "--new-window") + $urls_att2
        Start-Process -FilePath chrome -ArgumentList $arg_att2
    }
    'work1' {
        # 
        $arg_work = @("--incognito", "--new-window") + $urls_work
        Start-Process -FilePath chrome -ArgumentList $arg_work
    }
    'work2' {
        # 
        $arg_work2 = @("--incognito", "--new-window") + $urls_work2
        Start-Process -FilePath chrome -ArgumentList $arg_work2
    }
    Default {
        Write-host "Non Group.."
    }
}