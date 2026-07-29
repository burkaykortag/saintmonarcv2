<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktif Oturumlar - SaintMonarc</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f0c20 0%, #15102a 50%, #060210 100%);
            color: #ffffff;
            min-height: 100vh;
            padding: 50px 20px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #e2e8f0;
        }
        .desc {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 30px;
        }
        .session-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .session-card:hover {
            border-color: rgba(197, 168, 128, 0.3);
            background: rgba(255, 255, 255, 0.04);
        }
        .session-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .device {
            font-size: 16px;
            font-weight: 600;
            color: #e2e8f0;
        }
        .metadata {
            font-size: 13px;
            color: #94a3b8;
            display: flex;
            gap: 12px;
        }
        .metadata span {
            display: inline-flex;
            align-items: center;
        }
        .current-badge {
            background: rgba(34, 197, 94, 0.1);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 8px;
        }
        .btn-revoke {
            padding: 8px 16px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-revoke:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Güvenlik ve Oturum Yönetimi</h1>
        <p class="desc">Hesabınıza bağlı aktif cihaz oturumlarını görüntüleyin ve dilediğinizi sonlandırın.</p>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sessions)): ?>
            <?php foreach ($sessions as $session): ?>
                <div class="session-card">
                    <div class="session-info">
                        <div class="device">
                            IP Adresi: <?= htmlspecialchars($session['ip_address'] ?? 'Bilinmiyor') ?>
                            <?php if (session_id() === $session['id']): ?>
                                <span class="current-badge">Bu Cihaz</span>
                            <?php endif; ?>
                        </div>
                        <div class="metadata">
                            <span>Son Görülme: <?= date('Y-m-d H:i:s', $session['last_activity']) ?></span>
                            <span>Tarayıcı: <?= htmlspecialchars(substr($session['user_agent'] ?? '', 0, 50)) ?>...</span>
                        </div>
                    </div>
                    <?php if (session_id() !== $session['id']): ?>
                        <form method="POST">
                            <input type="hidden" name="session_id" value="<?= htmlspecialchars($session['id']) ?>">
                            <button type="submit" class="btn-revoke">Oturumu Kapat</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aktif başka bir oturum bulunamadı.</p>
        <?php endif; ?>
    </div>
</body>
</html>
