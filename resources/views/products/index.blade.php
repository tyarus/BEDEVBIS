<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - BeDevbis</title>
    <style>
        :root {
            --bg: #f6f8fc;
            --card: #ffffff;
            --text: #1e2536;
            --muted: #6b7690;
            --primary: #0f6ae6;
            --danger: #b43030;
            --border: #d9e2f1;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
        }

        header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }

        h1 {
            margin: 0;
            font-size: 20px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            border: 1px solid var(--border);
            background: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 16px 20px;
        }

        .status {
            margin-bottom: 14px;
            color: var(--muted);
            font-size: 14px;
        }

        .status.error {
            color: var(--danger);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            min-height: 130px;
        }

        .title {
            margin: 0 0 6px;
            font-size: 16px;
            line-height: 1.3;
        }

        .desc {
            margin: 0 0 10px;
            font-size: 13px;
            color: var(--muted);
            min-height: 32px;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .price {
            font-weight: 700;
        }
    </style>
</head>
<body>
<header>
    <h1>Daftar Produk</h1>
    <div class="actions">
        <button class="btn" id="refresh-btn">Refresh</button>
        <button class="btn primary" id="logout-btn">Logout</button>
    </div>
</header>

<main class="container">
    <div id="status" class="status">Memuat produk...</div>
    <section id="products" class="grid"></section>
</main>

<script>
    const TOKEN_KEY = "bedevbis_token";
    const statusEl = document.getElementById("status");
    const productsEl = document.getElementById("products");
    const refreshBtn = document.getElementById("refresh-btn");
    const logoutBtn = document.getElementById("logout-btn");

    const formatRupiah = (value) => {
        if (typeof value !== "number") return "Rp 0";
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(value);
    };

    const getToken = () => localStorage.getItem(TOKEN_KEY);

    const setStatus = (text, isError = false) => {
        statusEl.textContent = text;
        statusEl.className = "status" + (isError ? " error" : "");
    };

    const goToLogin = () => {
        window.location.href = "/login";
    };

    const renderProducts = (products) => {
        productsEl.innerHTML = "";
        if (!Array.isArray(products) || products.length === 0) {
            setStatus("Belum ada produk aktif.");
            return;
        }

        const fragment = document.createDocumentFragment();

        products.forEach((product) => {
            const card = document.createElement("article");
            card.className = "card";
            card.innerHTML = `
                <h2 class="title">${product.name ?? "-"}</h2>
                <p class="desc">${product.description ?? "-"}</p>
                <div class="meta">
                    <span class="price">${formatRupiah(product.price)}</span>
                    <span>Stok: ${product.stock ?? 0}</span>
                </div>
            `;
            fragment.appendChild(card);
        });

        productsEl.appendChild(fragment);
        setStatus(`Menampilkan ${products.length} produk.`);
    };

    const loadProducts = async () => {
        const token = getToken();
        if (!token) {
            goToLogin();
            return;
        }

        setStatus("Memuat produk...");

        try {
            const response = await fetch("/api/products", {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            const payload = await response.json().catch(() => null);

            if (response.status === 401) {
                localStorage.removeItem(TOKEN_KEY);
                goToLogin();
                return;
            }

            if (!response.ok) {
                setStatus(payload?.message || "Gagal mengambil data produk.", true);
                return;
            }

            renderProducts(payload?.data ?? []);
        } catch (error) {
            setStatus("Tidak bisa terhubung ke API.", true);
        }
    };

    const logout = async () => {
        const token = getToken();

        try {
            if (token) {
                await fetch("/api/auth/logout", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`
                    }
                });
            }
        } catch (error) {
            // ignore network error on logout
        } finally {
            localStorage.removeItem(TOKEN_KEY);
            goToLogin();
        }
    };

    refreshBtn.addEventListener("click", loadProducts);
    logoutBtn.addEventListener("click", logout);
    loadProducts();
</script>
</body>
</html>
