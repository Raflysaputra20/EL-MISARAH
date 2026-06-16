<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kost Elmi Sarah</title>

    <link rel="stylesheet" href="frontend/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="frontend/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<?php include __DIR__ . '/../components/navbar.php'; ?>

<main>
    <?php include $content; ?>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
<script src="frontend/assets/js/bootstrap.bundle.min.js"></script>

<!-- LOGIN MODAL OVERLAY -->
<style>
.login-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    padding: 20px;
}
.login-overlay.active {
    opacity: 1;
    visibility: visible;
}
.login-modal {
    position: relative;
    width: 100%;
    max-width: 1000px;
    background: #EBE7DE;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    transform: translateY(20px);
    transition: all 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
}
.login-overlay.active .login-modal {
    transform: translateY(0);
}
.login-close {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    font-size: 30px;
    line-height: 1;
    color: #000;
    cursor: pointer;
    z-index: 10;
}
.login-close:hover { color: #ef4444; }
.login-wrapper-modal {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 480px;
}
.login-content-modal {
    padding: 30px 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.login-box-modal {
    width: 100%;
    max-width: 400px;
}
.login-title { font-size: 32px; font-weight: 800; color: #000; margin: 0 0 8px; line-height: 1.2; }
.login-subtitle { font-size: 13px; color: #525252; margin: 0 0 20px; }
.login-message { padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.login-message.error { background: #ffe3e3; color: #c1121f; }
.form-group-modal { margin-bottom: 15px; }
.form-group-modal label { display: block; margin-bottom: 6px; font-size: 13px; color: #000; font-weight: 600; }
.form-control-modal { width: 100%; height: 46px; border: 1px solid #D9D9D9; border-radius: 8px; background: #fff; padding: 0 14px; font-size: 13.5px; outline: none; }
.login-options-modal { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; font-size: 13px; }
.remember-modal { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.forgot-link-modal { color: #000; text-decoration: none; }
.submit-btn-modal { width: 100%; height: 46px; border: none; border-radius: 8px; background: #2C3E50; color: #fff; font-size: 14.5px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
.submit-btn-modal:hover { opacity: 0.9; }
.register-text-modal { margin-top: 18px; text-align: center; font-size: 13.5px; color: #525252; }
.register-text-modal a { color: #000; font-weight: 600; text-decoration: none; }
.login-visual-modal {
    position: relative;
    background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url("frontend/assets/image/login.png");
    background-size: cover;
    background-position: center;
    border-radius: 0 16px 16px 0;
}
.visual-brand-modal { position: absolute; top: 25px; left: 25px; color: #fff; font-size: 20px; font-weight: 700; }
.visual-caption-modal { position: absolute; left: 50%; bottom: 45px; transform: translateX(-50%); width: 90%; color: #fff; text-align: center; font-size: 20px; font-weight: 500; line-height: 1.4; }
.visual-dots-modal { position: absolute; left: 50%; bottom: 20px; transform: translateX(-50%); display: flex; gap: 8px; }
.visual-dots-modal span { width: 24px; height: 4px; background: #525252; border-radius: 10px; }
.visual-dots-modal span.active { width: 32px; background: #fff; }

@media (max-width: 768px) {
    .login-wrapper-modal { grid-template-columns: 1fr; min-height: auto; }
    .login-visual-modal { display: none; }
    .login-modal { border-radius: 16px; }
    .login-content-modal { padding: 25px 20px; }
}
</style>

<div id="loginOverlay" class="login-overlay">
    <div class="login-modal">
        <button class="login-close" onclick="closeLoginModal()">&times;</button>
        <div class="login-wrapper-modal">
            <div class="login-content-modal">
                <div class="login-box-modal">
                    <h1 class="login-title">Selamat Datang</h1>
                    <p class="login-subtitle">Masukkan email dan kata sandi untuk mengakses akun kost</p>
                    
                    <div id="loginMessage" class="login-message" style="display: none;"></div>
                    
                    <form id="loginFormModal" onsubmit="submitLoginModal(event)">
                        <div class="form-group-modal">
                            <label>Email/No Hp</label>
                            <input class="form-control-modal" type="text" name="email" id="loginEmail" placeholder="Masukan Email atau No Hp" required>
                        </div>
                        <div class="form-group-modal">
                            <label>Kata Sandi</label>
                            <input class="form-control-modal" type="password" name="password" id="loginPassword" placeholder="Masukkan kata sandi" required>
                        </div>
                        <div class="form-group-modal">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label id="captcha-question-modal" style="margin-bottom: 0;">Berapa hasil dari <?php echo $_SESSION['captcha_num1'] ?? 0; ?> + <?php echo $_SESSION['captcha_num2'] ?? 0; ?>?</label>
                                <button type="button" onclick="refreshCaptchaModal()" style="background: none; border: none; color: #a58145; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw" style="vertical-align: middle;"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg> Ubah Soal
                                </button>
                            </div>
                            <input class="form-control-modal" type="number" name="captcha" id="loginCaptcha" placeholder="Masukkan jawaban angka" required>
                        </div>
                        
                        <div class="login-options-modal">
                            <label class="remember-modal">
                                <input type="checkbox" name="remember" checked>
                                <span>Ingat saya</span>
                            </label>
                            <a href="backend/api/auth/lupa_password.php" class="forgot-link-modal">Lupa kata sandi?</a>
                        </div>
                        
                        <button type="submit" class="submit-btn-modal" id="loginBtnSubmit">Masuk</button>
                    </form>
                    
                    <div class="register-text-modal">
                        Belum punya akun? <a href="#" onclick="switchModal('register')">Daftar</a>
                    </div>
                </div>
            </div>
            <div class="login-visual-modal">
                <div class="visual-brand-modal">Elmi Sarah</div>
                <div class="visual-caption-modal">Hunian Nyaman untuk<br>Tinggal dan Beraktivitas</div>
                <div class="visual-dots-modal">
                    <span></span><span></span><span class="active"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- REGISTER MODAL OVERLAY -->
<style>
.register-wrapper-modal {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 460px;
}
.register-content-modal {
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.register-box-modal {
    width: 100%;
    max-width: 500px;
}
.register-box-modal .login-title {
    font-size: 26px;
    margin: 0 0 4px;
}
.register-box-modal .login-subtitle {
    margin: 0 0 12px;
}
.register-box-modal .form-control-modal {
    height: 42px;
    font-size: 13px;
}
.register-visual-modal {
    position: relative;
    background-image: linear-gradient(rgba(0,0,0,0.32), rgba(0,0,0,0.32)), url("frontend/assets/image/register.png");
    background-size: cover;
    background-position: center;
    border-radius: 16px 0 0 16px;
}
.form-grid-modal {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}
.form-control-modal.full {
    grid-column: span 2;
}
.terms-modal {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 12px;
    color: #000;
}
@media (max-width: 768px) {
    .register-wrapper-modal { grid-template-columns: 1fr; min-height: auto; }
    .register-visual-modal { display: none; }
    .register-content-modal { padding: 20px 20px; }
    .form-grid-modal { grid-template-columns: 1fr; }
    .form-control-modal.full { grid-column: span 1; }
}
</style>

<div id="registerOverlay" class="login-overlay">
    <div class="login-modal" style="max-width: 1100px;">
        <button class="login-close" onclick="closeRegisterModal()">&times;</button>
        <div class="register-wrapper-modal">
            <div class="register-visual-modal">
                <div class="visual-brand-modal">Elmi Sarah</div>
                <div class="visual-caption-modal">Hunian Nyaman untuk<br>Tinggal dan Beraktivitas</div>
                <div class="visual-dots-modal">
                    <span></span><span></span><span class="active"></span>
                </div>
            </div>
            <div class="register-content-modal">
                <div class="register-box-modal">
                    <h1 class="login-title">Daftar Akun</h1>
                    <p class="login-subtitle">Sudah punya akun? <a href="#" onclick="switchModal('login')" style="color: #2F80FF; text-decoration: none;">Masuk</a></p>
                    
                    <div id="registerMessage" class="login-message" style="display: none;"></div>
                    
                    <form id="registerFormModal" onsubmit="submitRegisterModal(event)">
                        <div class="form-grid-modal">
                            <input class="form-control-modal" type="text" name="nama_depan" id="regNamaDepan" placeholder="Nama Depan" required>
                            <input class="form-control-modal" type="text" name="nama_belakang" id="regNamaBelakang" placeholder="Nama Belakang" required>
                            <input class="form-control-modal full" type="email" name="email" id="regEmail" placeholder="Email" required>
                            <input class="form-control-modal full" type="text" name="no_hp" id="regNoHp" placeholder="No Hp" required>
                            <input class="form-control-modal" type="password" name="password" id="regPassword" placeholder="Password" required>
                            <input class="form-control-modal" type="password" name="confirm_password" id="regConfirmPassword" placeholder="Konfirmasi Password" required>
                            <div class="pw-checker-container full" style="grid-column: span 2; margin-top: -6px; background: rgba(0,0,0,0.02); padding: 8px 12px; border-radius: 8px; border: 1px solid #D9D9D9;">
                                <div style="font-size: 12px; font-weight: 600; color: #000; margin-bottom: 6px;">Kriteria Kata Sandi:</div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px;">
                                    <div class="pw-rule" id="rule-length" style="font-size: 11px; color: #666; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Min. 8 karakter
                                    </div>
                                    <div class="pw-rule" id="rule-upper" style="font-size: 11px; color: #666; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Huruf besar (A-Z)
                                    </div>
                                    <div class="pw-rule" id="rule-lower" style="font-size: 11px; color: #666; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Huruf kecil (a-z)
                                    </div>
                                    <div class="pw-rule" id="rule-number" style="font-size: 11px; color: #666; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Angka (0-9)
                                    </div>
                                    <div class="pw-rule" id="rule-symbol" style="font-size: 11px; color: #666; display: flex; align-items: center; gap: 6px; transition: all 0.2s; grid-column: span 2;">
                                        <span class="rule-icon" style="display: inline-block; width: 14px; text-align: center; font-weight: bold;">○</span> Simbol (!@#$%^&*)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <label class="terms-modal">
                            <input type="checkbox" name="terms" id="regTerms" checked>
                            <span>Saya setuju dengan <a href="#" onclick="openTermsModal(event)" style="color: #2F80FF;">Syarat & Ketentuan</a></span>
                        </label>
                        
                        <button type="submit" class="submit-btn-modal" id="registerBtnSubmit">Daftar Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openLoginModal(e) {
    if (e) e.preventDefault();
    closeRegisterModal();
    document.getElementById('loginOverlay').classList.add('active');
    
    // Close mobile menu if it's open
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu && mobileMenu.classList.contains('active')) {
        mobileMenu.classList.remove('active');
    }
}

function closeLoginModal() {
    document.getElementById('loginOverlay').classList.remove('active');
}

function openRegisterModal(e) {
    if (e) e.preventDefault();
    closeLoginModal();
    document.getElementById('registerOverlay').classList.add('active');
    
    // Close mobile menu if it's open
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu && mobileMenu.classList.contains('active')) {
        mobileMenu.classList.remove('active');
    }
}

function closeRegisterModal() {
    document.getElementById('registerOverlay').classList.remove('active');
}

function switchModal(type) {
    if (type === 'login') {
        openLoginModal();
    } else {
        openRegisterModal();
    }
}

// Close when clicking outside modal content
document.getElementById('loginOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});

document.getElementById('registerOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRegisterModal();
    }
});

async function submitLoginModal(e) {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const captcha = document.getElementById('loginCaptcha').value;
    const msgDiv = document.getElementById('loginMessage');
    const btn = document.getElementById('loginBtnSubmit');
    
    msgDiv.style.display = 'none';
    msgDiv.className = 'login-message error';
    
    if (!email || !password || !captcha) {
        msgDiv.textContent = 'Email/No Hp, kata sandi, dan CAPTCHA wajib diisi';
        msgDiv.style.display = 'block';
        return;
    }
    
    btn.textContent = 'Memproses...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('captcha', captcha);
        formData.append('is_ajax', '1');
        
        const response = await fetch('backend/api/auth/login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            msgDiv.className = 'login-message';
            msgDiv.style.backgroundColor = '#e7f8ec';
            msgDiv.style.color = '#198754';
            msgDiv.textContent = 'Berhasil masuk! Mengalihkan...';
            msgDiv.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 500);
        } else {
            msgDiv.textContent = data.message || 'Terjadi kesalahan saat masuk';
            msgDiv.style.display = 'block';
            btn.textContent = 'Masuk';
            btn.disabled = false;
            
            // Update captcha numbers if returned by server
            if (data.captcha_num1 !== undefined) {
                document.getElementById('captcha-question-modal').textContent = `Berapa hasil dari ${data.captcha_num1} + ${data.captcha_num2}?`;
                document.getElementById('loginCaptcha').value = '';
            }
        }
    } catch (error) {
        console.error(error);
        msgDiv.textContent = 'Terjadi kesalahan jaringan.';
        msgDiv.style.display = 'block';
        btn.textContent = 'Masuk';
        btn.disabled = false;
    }
}

async function refreshCaptchaModal() {
    try {
        const response = await fetch('backend/api/auth/refresh_captcha.php');
        const data = await response.json();
        if (data.status === 'success') {
            document.getElementById('captcha-question-modal').textContent = `Berapa hasil dari ${data.captcha_num1} + ${data.captcha_num2}?`;
            const inputEl = document.getElementById('loginCaptcha');
            if (inputEl) inputEl.value = '';
        }
    } catch (e) {
        console.error("Gagal memuat CAPTCHA baru", e);
    }
}

async function submitRegisterModal(e) {
    e.preventDefault();
    
    const namaDepan = document.getElementById('regNamaDepan').value;
    const namaBelakang = document.getElementById('regNamaBelakang').value;
    const email = document.getElementById('regEmail').value;
    const noHp = document.getElementById('regNoHp').value;
    const password = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;
    const terms = document.getElementById('regTerms').checked;
    
    const msgDiv = document.getElementById('registerMessage');
    const btn = document.getElementById('registerBtnSubmit');
    
    msgDiv.style.display = 'none';
    msgDiv.className = 'login-message error';
    
    if (!namaDepan || !email || !noHp || !password || !confirmPassword) {
        msgDiv.textContent = 'Semua field wajib diisi';
        msgDiv.style.display = 'block';
        return;
    }
    
    if (password.length < 8) {
        msgDiv.textContent = 'Kata sandi minimal harus 8 karakter';
        msgDiv.style.display = 'block';
        return;
    }
    
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSymbol = /[^A-Za-z0-9]/.test(password);
    
    if (!hasUpperCase || !hasLowerCase || !hasNumber || !hasSymbol) {
        msgDiv.textContent = 'Kata sandi harus mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol';
        msgDiv.style.display = 'block';
        return;
    }
    
    if (password !== confirmPassword) {
        msgDiv.textContent = 'Konfirmasi password tidak sesuai';
        msgDiv.style.display = 'block';
        return;
    }
    
    if (!terms) {
        msgDiv.textContent = 'Anda harus menyetujui syarat dan ketentuan';
        msgDiv.style.display = 'block';
        return;
    }
    
    btn.textContent = 'Memproses...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('nama_depan', namaDepan);
        formData.append('nama_belakang', namaBelakang);
        formData.append('email', email);
        formData.append('no_hp', noHp);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);
        formData.append('terms', terms ? 'on' : '');
        formData.append('is_ajax', '1');
        
        const response = await fetch('backend/api/auth/register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            msgDiv.className = 'login-message';
            msgDiv.style.backgroundColor = '#e7f8ec';
            msgDiv.style.color = '#198754';
            msgDiv.textContent = 'Pendaftaran berhasil! Mengalihkan ke login...';
            msgDiv.style.display = 'block';
            
            setTimeout(() => {
                switchModal('login');
                const loginMsg = document.getElementById('loginMessage');
                loginMsg.className = 'login-message';
                loginMsg.style.backgroundColor = '#e7f8ec';
                loginMsg.style.color = '#198754';
                loginMsg.textContent = 'Pendaftaran berhasil, silakan masuk';
                loginMsg.style.display = 'block';
                btn.textContent = 'Daftar Sekarang';
                btn.disabled = false;
            }, 1000);
        } else {
            msgDiv.textContent = data.message || 'Terjadi kesalahan saat pendaftaran';
            msgDiv.style.display = 'block';
            btn.textContent = 'Daftar Sekarang';
            btn.disabled = false;
        }
    } catch (error) {
        console.error(error);
        msgDiv.textContent = 'Terjadi kesalahan jaringan.';
        msgDiv.style.display = 'block';
        btn.textContent = 'Daftar Sekarang';
        btn.disabled = false;
    }
}

// Terms Modal Functions
function openTermsModal(e) {
    if (e) e.preventDefault();
    document.getElementById('termsOverlay').classList.add('active');
}
function closeTermsModal() {
    document.getElementById('termsOverlay').classList.remove('active');
}

// Dynamic Password Checker
function initPasswordChecker() {
    const passwordInput = document.getElementById('regPassword');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            
            // Check length
            updateRuleState('rule-length', val.length >= 8);
            
            // Check uppercase
            updateRuleState('rule-upper', /[A-Z]/.test(val));
            
            // Check lowercase
            updateRuleState('rule-lower', /[a-z]/.test(val));
            
            // Check number
            updateRuleState('rule-number', /[0-9]/.test(val));
            
            // Check symbol
            updateRuleState('rule-symbol', /[^A-Za-z0-9]/.test(val));
        });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordChecker);
} else {
    initPasswordChecker();
}

function updateRuleState(ruleId, isMet) {
    const el = document.getElementById(ruleId);
    if (!el) return;
    const icon = el.querySelector('.rule-icon');
    if (isMet) {
        el.style.color = '#198754';
        el.style.fontWeight = '600';
        icon.textContent = '✔';
        icon.style.color = '#198754';
    } else {
        el.style.color = '#666';
        el.style.fontWeight = 'normal';
        icon.textContent = '○';
        icon.style.color = '#666';
    }
}

// Check for URL parameters to show modals
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('login_modal') === '1') {
        openLoginModal();
        if (urlParams.get('msg') === 'register_success') {
            const loginMsg = document.getElementById('loginMessage');
            loginMsg.className = 'login-message';
            loginMsg.style.backgroundColor = '#e7f8ec';
            loginMsg.style.color = '#198754';
            loginMsg.textContent = 'Pendaftaran berhasil, silakan masuk';
            loginMsg.style.display = 'block';
        } else if (urlParams.get('msg') === 'auth_required') {
            const loginMsg = document.getElementById('loginMessage');
            loginMsg.className = 'login-message error';
            loginMsg.textContent = 'Harap login terlebih dahulu untuk melanjutkan';
            loginMsg.style.display = 'block';
        }
    } else if (urlParams.get('register_modal') === '1') {
        openRegisterModal();
    }
});
</script>

<div class="login-overlay" id="termsOverlay" onclick="if(event.target===this) closeTermsModal()">
    <div class="login-modal" style="max-width: 600px; background: #fff; border-radius: 16px; padding: 0; overflow: hidden;">
        <button class="login-close" onclick="closeTermsModal()">&times;</button>
        <div style="padding: 30px;">
            <h2 style="font-weight: 800; font-size: 24px; margin-top: 0; margin-bottom: 20px; color: #000; font-family: 'Poppins', sans-serif;">Syarat & Ketentuan</h2>
            <div style="font-size: 13.5px; line-height: 1.6; color: #333; max-height: 350px; overflow-y: auto; padding-right: 10px; margin-bottom: 24px; font-family: 'Poppins', sans-serif; text-align: left;">
                <p style="margin-top: 0;"><strong>1. Ketentuan Umum</strong><br>Dengan mendaftar dan menggunakan layanan Kost Elmi Sarah, Anda menyetujui seluruh aturan dan ketentuan yang berlaku di lingkungan kost.</p>
                <p><strong>2. Pembayaran & Booking</strong><br>Booking kamar hanya dianggap sah setelah Anda membayar uang muka (DP) sesuai nominal yang ditentukan dan diverifikasi oleh Admin. Pembayaran bulanan jatuh tempo setiap bulan sesuai tanggal mulai sewa.</p>
                <p><strong>3. Tata Tertib Kost</strong><br>Setiap penghuni wajib menjaga ketertiban, keamanan, kebersihan, serta mematuhi aturan jam malam dan larangan membawa tamu menginap tanpa izin pengelola.</p>
                <p><strong>4. Penggunaan Fasilitas</strong><br>Fasilitas kamar dan area bersama harus dirawat dengan baik. Kerusakan akibat kelalaian penghuni akan dikenakan biaya perbaikan.</p>
                <p style="margin-bottom: 0;"><strong>5. Pembatalan Booking</strong><br>Pembatalan sepihak setelah pembayaran DP dapat mengakibatkan hangusnya uang muka sesuai dengan kebijakan pengelola.</p>
            </div>
            <button onclick="closeTermsModal()" class="submit-btn-modal" style="background: #2C3E50; width: 100%; height: 46px; border: none; border-radius: 8px; color: #fff; font-size: 14.5px; font-weight: 600; cursor: pointer;">Saya Mengerti</button>
        </div>
    </div>
</div>

</body>
</html>