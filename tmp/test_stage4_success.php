<?php

require_once __DIR__ . '/../../gdata.php';
require_once __DIR__ . '/GitHubShellManager.php';

// 設定読み込み
$config = require __DIR__ . '/config.php';

// Stage 4成功をシミュレーション
$manager = new GitHubShellManager($config, 'srv-tools#282', 4);

// 成功時のレポート生成をテスト
try {
    echo "=== Stage 4 成功時レポートテスト ===\n";
    
    // 手動で成功レポートを生成
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('generateExecutionReport');
    $method->setAccessible(true);
    
    // 成功時のレポート生成（エラーなし）
    $method->invoke($manager, 'migration20250720010', 'migration20250720010.go', 10, null);
    
    echo "✅ 成功時レポート生成テスト完了\n";
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
