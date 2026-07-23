import re

with open("resources/css/app.css", "r") as f:
    css = f.read()

# 1. Add font import at top
css = "@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');\n" + css

# 2. Update body font and background
css = css.replace("font-family: 'Inter', -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;", "font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;")

# 3. Add Glassmorphism vars to :root
glass_vars = """
    --glass-blur: blur(24px);
    --mesh-1: color-mix(in srgb, var(--primary) 22%, transparent);
    --mesh-2: color-mix(in srgb, var(--primary-2) 15%, transparent);
    --bg-grad: radial-gradient(900px circle at 80% -10%, var(--mesh-1), transparent 60%), radial-gradient(1100px circle at -10% 120%, var(--mesh-2), transparent 55%), radial-gradient(800px circle at 50% 50%, color-mix(in srgb, var(--blue) 8%, transparent), transparent 60%);
"""
css = css.replace("--bg-grad: radial-gradient", glass_vars + "    /* ")

# 4. Append bento and glassmorphism styles at the bottom to override base
bento_styles = """

/* ============================================================
   NEW GLASSMORPHISM & BENTO BOX ADDITIONS
   ============================================================ */

body {
    background-image: var(--bg-grad) !important;
}

.app-shell { padding: 12px; gap: 12px; }

.sidebar {
    inset: 12px auto 12px 12px;
    height: calc(100vh - 24px);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.6) !important;
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}
[data-theme="dark"] .sidebar {
    background: rgba(12, 14, 28, 0.55) !important;
}

.main { margin-left: 272px; border-radius: 24px; }
.topbar {
    margin: -12px -12px 24px -12px;
    padding: 0 40px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--bg) 80%, transparent) 0%, transparent 100%) !important;
    backdrop-filter: blur(8px);
    border-bottom: none !important;
}
.topbar .page-title { font-family: 'Outfit'; font-size: 22px; }

.card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: var(--glass-blur);
    border: 1px solid var(--border);
    border-radius: 24px !important;
    transition: transform 0.25s, box-shadow 0.25s;
}
[data-theme="dark"] .card {
    background: rgba(20, 22, 38, 0.65) !important;
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.card h2 { font-family: 'Outfit'; font-size: 18px; }

.bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
}
.bento-col-4 { grid-column: span 4; }
.bento-col-6 { grid-column: span 6; }
.bento-col-8 { grid-column: span 8; }
.bento-col-12 { grid-column: span 12; }
@media (max-width: 1100px) { .bento-col-4, .bento-col-8 { grid-column: span 12; } }
@media (max-width: 768px) { .bento-col-6 { grid-column: span 12; } }

.stat-bento {
    background: var(--card); backdrop-filter: var(--glass-blur); border: 1px solid var(--border);
    border-radius: 24px; padding: 24px; box-shadow: var(--shadow);
    position: relative; overflow: hidden; transition: all 0.3s;
    display: flex; flex-direction: column; justify-content: center;
}
.stat-bento:hover { transform: translateY(-4px) scale(1.02); box-shadow: var(--shadow-lg); }
.stat-icon { width: 50px; height: 50px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
.stat-icon svg { width: 24px; height: 24px; }
.stat-bento .num { font-size: 40px; font-weight: 800; line-height: 1; letter-spacing: -.03em; color: var(--heading); font-family: 'Outfit'; }
.stat-bento .lbl { font-size: 14px; color: var(--muted); font-weight: 600; margin-top: 8px; font-family: 'Inter'; text-transform: uppercase; letter-spacing: 0.05em; }

.animate-enter { animation: bentoEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.animate-delay-1 { animation-delay: 0.05s; }
.animate-delay-2 { animation-delay: 0.1s; }
.animate-delay-3 { animation-delay: 0.15s; }
@keyframes bentoEnter { from { opacity: 0; transform: translateY(20px) scale(0.97); } to { opacity: 1; transform: none; } }

@media (max-width: 900px) {
    .sidebar { transform: translateX(-120%); width: 280px; }
    .sidebar.open { transform: translateX(0); }
    .main { margin-left: 0; }
    .topbar { margin: -12px -12px 24px -12px; }
}
"""

with open("resources/css/app.css", "w") as f:
    f.write(css + bento_styles)

print("CSS updated successfully.")
