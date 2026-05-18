<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BeDevbis</title>
    <style>
        :root {
            --bg: #f4f6fb;
            --card: #ffffff;
            --text: #1c2333;
            --muted: #62708a;
            --primary: #0f6ae6;
            --primary-hover: #0b56bd;
            --danger: #bf2f2f;
            --border: #dce3ef;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(155deg, #e9eefc 0%, #f8fafc 45%, #eef3ff 100%);
            color: var(--text);
        }

        .card {
            width: min(460px, 92vw);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(16, 31, 70, 0.08);
            padding: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        p {
            margin: 0 0 18px;
            color: var(--muted);
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 14px;
            margin-bottom: 14px;
        }

        button {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            background: var(--primary);
            cursor: pointer;
        }

        button:hover {
            background: var(--primary-hover);
        }

        button:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .status {
            margin-top: 12px;
            font-size: 14px;
            min-height: 18px;
        }

        .status.error {
            color: var(--danger);
        }

        .status.success {
            color: #207547;
        }

        .hint {
            margin-top: 16px;
            font-size: 13px;
            color: var(--muted);
            border-top: 1px solid var(--border);
            padding-top: 14px;
        }
    </style>
</head>
<body>
<main class="card">
    <h1>Login</h1>
    <p>Masuk untuk lanjut ke halaman produk.</p>

    <form id="login-form">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="contoh: tyar@example.com" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Masukkan password" required>

        <button id="submit-btn" type="submit">Masuk</button>
        <div id="status" class="status"></div>
    </form>

    <div class="hint">
        Akun test seed:
        <br>Buyer: <b>tyar@example.com</b> / <b>password123</b>
        <br>Seller: <b>tyars@example.com</b> / <b>password123</b>
    </div>
</main>

<script>
    const TOKEN_KEY = "bedevbis_token";
    const form = document.getElementById("login-form");
    const statusEl = document.getElementById("status");
    const submitBtn = document.getElementById("submit-btn");

    const setStatus = (text, type) => {
        statusEl.textContent = text || "";
        statusEl.className = "status" + (type ? " " + type : "");
    };

    const extractError = (payload) => {
        if (!payload) return "Terjadi kesalahan. Coba lagi.";
        if (payload.message) return payload.message;

        if (payload.errors) {
            if (Array.isArray(payload.errors) && payload.errors.length > 0) {
                return payload.errors[0];
            }

            const firstKey = Object.keys(payload.errors)[0];
            if (firstKey && Array.isArray(payload.errors[firstKey]) && payload.errors[firstKey][0]) {
                return payload.errors[firstKey][0];
            }
        }

        return "Terjadi kesalahan. Coba lagi.";
    };

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        setStatus("");
        submitBtn.disabled = true;

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;

        try {
            const response = await fetch("/api/auth/login", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email, password })
            });

            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                const message = extractError(payload);
                setStatus(message, "error");
                return;
            }

            const token = payload?.data?.token;
            if (!token) {
                setStatus("Token login tidak ditemukan di response API.", "error");
                return;
            }

            localStorage.setItem(TOKEN_KEY, token);
            setStatus("Login berhasil. Mengarahkan ke halaman produk...", "success");
            window.location.href = "/products";
        } catch (error) {
            setStatus("Gagal terhubung ke server API.", "error");
        } finally {
            submitBtn.disabled = false;
        }
    });
</script>
</body>
</html>
