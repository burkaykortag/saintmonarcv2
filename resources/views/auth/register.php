<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - SaintMonarc</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .container {
            width: 100%;
            max-width: 480px;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.8s ease-in-out;
        }
        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #c5a880, #e5d1b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 24px;
        }
        .title {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 24px;
            text-align: center;
            color: #e2e8f0;
        }
        .form-row {
            display: flex;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 20px;
            width: 100%;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #c5a880;
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        .strength-container {
            margin-top: 8px;
        }
        .strength-bar {
            height: 4px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        .strength-text {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
            display: block;
        }
        .consent-group {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .consent-group input[type="checkbox"] {
            margin-top: 3px;
            cursor: pointer;
            accent-color: #c5a880;
        }
        .consent-group label {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.4;
            cursor: pointer;
        }
        .consent-group a {
            color: #c5a880;
            text-decoration: none;
        }
        .consent-group a:hover {
            text-decoration: underline;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #c5a880, #b09168);
            border: none;
            border-radius: 12px;
            color: #0f0c20;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.3);
            margin-top: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(197, 168, 128, 0.5);
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .links {
            margin-top: 20px;
            font-size: 13px;
            text-align: center;
        }
        .links a {
            color: #c5a880;
            text-decoration: none;
        }
        .links a:hover {
            color: #e5d1b8;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">SAINTMONARC</div>
            <div class="title">Yeni Hesap Oluştur</div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Ad</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="John">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Soyad</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Doe">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
                    <input type="email" id="email" name="email" required placeholder="john@doe.com">
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <div class="strength-container">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Güvenlik: Çok Zayıf</span>
                    </div>
                </div>

                <div class="consent-group">
                    <input type="checkbox" id="kvkk_consent" name="kvkk_consent" value="1" required>
                    <label for="kvkk_consent"><a href="<?= url('/legal/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum ve kabul ediyorum.</label>
                </div>

                <div class="consent-group">
                    <input type="checkbox" id="terms_consent" name="terms_consent" value="1" required>
                    <label for="terms_consent"><a href="<?= url('/legal/terms') ?>" target="_blank">Kullanım Koşulları ve Gizlilik Politikası</a>'nı onaylıyorum.</label>
                </div>

                <button type="submit" class="btn">Kayıt Ol</button>
            </form>

            <div class="links">
                Zaten hesabınız var mı? <a href="<?= url('/login') ?>">Giriş Yap</a>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;

            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) score++;

            let color = '#ef4444';
            let width = '20%';
            let text = 'Çok Zayıf';

            if (score === 2) {
                color = '#f97316';
                width = '40%';
                text = 'Zayıf';
            } else if (score === 3) {
                color = '#eab308';
                width = '60%';
                text = 'Orta';
            } else if (score === 4) {
                color = '#22c55e';
                width = '80%';
                text = 'Güçlü';
            } else if (score === 5) {
                color = '#10b981';
                width = '100%';
                text = 'Çok Güçlü';
            }

            strengthFill.style.width = width;
            strengthFill.style.backgroundColor = color;
            strengthText.textContent = `Güvenlik: ${text}`;
        });
    </script>
</body>
</html>
