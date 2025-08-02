<?php
// index.php
// 这是活码系统对外展示的页面。它会读取当前设置的二维码图片并显示出来。
// 为避免浏览器缓存旧的二维码，页面会发送 no-cache 头部。

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$codesDir = __DIR__ . '/codes';
$currentFile = __DIR__ . '/current.json';

// 创建目录（如果不存在）
if (!is_dir($codesDir)) {
    mkdir($codesDir, 0777, true);
}

// 如果还没有 current.json，则尝试把第一个二维码作为默认
if (!file_exists($currentFile)) {
    $files = glob($codesDir . '/*.{png,jpg,jpeg,gif}', GLOB_BRACE);
    if ($files) {
        $first = basename($files[0]);
        // 初始化 current.json 时同时记录当前时间为 last_update
        file_put_contents($currentFile, json_encode([
            'current' => $first,
            'last_update' => time(),
        ]));
    }
}

// 读取当前二维码信息
// 读取当前二维码及上次更新时间等信息
$current = '';
$data = [];
if (file_exists($currentFile)) {
    $data = json_decode(file_get_contents($currentFile), true);
    if (is_array($data) && isset($data['current'])) {
        $current = $data['current'];
    }
}

// 自动轮播逻辑：每7天更新一次
$rotationDays = 7;
$rotationInterval = $rotationDays * 24 * 60 * 60; // 秒数
// 获取最后一次更新的时间戳，如果不存在则默认为0
$lastUpdate = isset($data['last_update']) ? (int)$data['last_update'] : 0;
// 如果时间间隔超过或等于设置的轮播周期，则轮播到下一个二维码
if (time() - $lastUpdate >= $rotationInterval) {
    // 获取所有二维码文件
    $files = glob($codesDir . '/*.{png,jpg,jpeg,gif}', GLOB_BRACE);
    sort($files); // 按字母序排序
    if ($files) {
        // 获取文件名列表
        $basenames = array_map('basename', $files);
        // 如果当前值不存在于列表中，则从列表第一个开始
        $currentIndex = array_search($current, $basenames);
        if ($currentIndex === false) {
            $currentIndex = 0;
        } else {
            // 移动到下一个索引，循环
            $currentIndex = ($currentIndex + 1) % count($basenames);
        }
        $nextCode = $basenames[$currentIndex];
        // 更新数据并写入文件
        $data['current'] = $nextCode;
        $data['last_update'] = time();
        file_put_contents($currentFile, json_encode($data));
        $current = $nextCode;
    }
}

// 构建二维码路径（相对路径）
$imgPath = '';
if ($current) {
    $fullPath = $codesDir . '/' . $current;
    if (is_file($fullPath)) {
        $imgPath = 'codes/' . $current;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>微信群活码</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        img { max-width: 80%%; height: auto; border: 1px solid #ddd; padding: 4px; }
        .note { color: #888; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>欢迎扫码加入微信群</h1>
    <?php if ($imgPath): ?>
        <img src="<?php echo htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8'); ?>" alt="群二维码">
        <div class="note">如二维码已失效或加入人数已满，请稍后刷新本页面获取新的二维码。</div>
    <?php else: ?>
        <p>当前没有可用的群二维码，请联系管理员更新。</p>
    <?php endif; ?>
</body>
</html>
