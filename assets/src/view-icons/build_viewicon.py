import re
import json

import os

current_dir = os.path.dirname(os.path.abspath(__file__))
# Plugin root is 3 levels up from assets/src/view-icons/
base_dir = os.path.abspath(os.path.join(current_dir, "../../../"))
scss_file = os.path.join(base_dir, "assets/src/scss/_lp-icon-font.scss")
admin_css_file = os.path.join(base_dir, "assets/css/admin/admin.css")
dest_file = os.path.join(current_dir, "index.html")

# Read SCSS first for cleanest class list
with open(scss_file, "r", encoding="utf-8") as f:
    scss_content = f.read()

# Also read admin.css to ensure we don't miss anything
with open(admin_css_file, "r", encoding="utf-8") as f:
    admin_css_content = f.read()

# Parse rules from both
rules_scss = re.findall(r"(\.[a-zA-Z0-9_\-,\s\.\:\>\+]+)\s*\{([^}]+)\}", scss_content)
rules_admin = re.findall(r"(\.[a-zA-Z0-9_\-,\s\.\:\>\+]+)\s*\{([^}]+)\}", admin_css_content)

combined_rules = rules_scss + rules_admin

icons_data = []
seen = set()

for sel, body in combined_rules:
    if "lp-icon" not in sel:
        continue
    if "class^=" in sel or "class*=" in sel:
        continue
    
    classes = [s.strip().replace(":before", "").replace(".", "") for s in sel.split(",")]
    content_match = re.search(r'content:\s*"([^"]+)"', body) or re.search(r"content:\s*'([^']+)'", body)
    unicode_val = content_match.group(1) if content_match else ""
    hex_code = unicode_val.replace("\\", "").lower() if unicode_val else ""
    
    for cls in classes:
        if cls.startswith("lp-icon-") and cls not in seen:
            seen.add(cls)
            name = cls.replace("lp-icon-", "")
            icons_data.append({
                "class": cls,
                "name": name,
                "unicode": unicode_val,
                "hex": hex_code
            })

# Sort icons alphabetically by name
icons_data.sort(key=lambda x: x["name"])

# Pre-render static HTML cards for initial load
cards_html_list = []
for icon in icons_data:
    hex_badge = f'<span class="icon-meta">\\{icon["hex"]}</span>' if icon["hex"] else ""
    card = f"""      <div class="icon-card" data-name="{icon['name']}" data-class="{icon['class']}" data-hex="{icon['hex']}">
        <div class="card-actions">
          <button class="action-btn" title="Copy Class" onclick="copyText('{icon['class']}', 'Copied {icon['class']}!')">
            <i class="lp-icon-copy"></i>
          </button>
        </div>
        <div class="icon-preview-box">
          <i class="{icon['class']}"></i>
        </div>
        <div class="icon-name">{icon['name']}</div>
        {hex_badge}
      </div>"""
    cards_html_list.append(card)

cards_html = "\n".join(cards_html_list)
icons_json = json.dumps(icons_data)
total_count = len(icons_data)

html_template = f"""<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LearnPress Icon Font Viewer</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- LearnPress Admin CSS -->
  <link rel="stylesheet" href="../../css/admin/admin.css">
  <style>
    :root {{
      --bg-primary: #0b0f19;
      --bg-secondary: #161f30;
      --bg-card: rgba(22, 31, 48, 0.75);
      --bg-card-hover: rgba(30, 43, 66, 0.95);
      --border-color: rgba(255, 255, 255, 0.08);
      --border-hover: rgba(99, 102, 241, 0.5);
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --text-muted: #64748b;
      --accent: #6366f1;
      --accent-rgb: 99, 102, 241;
      --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
      --success: #10b981;
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --shadow-card: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
      --shadow-hover: 0 10px 30px -4px rgba(99, 102, 241, 0.25);
      --icon-color: #f8fafc;
      --icon-size: 32px;
    }}

    [data-theme="light"] {{
      --bg-primary: #f8fafc;
      --bg-secondary: #ffffff;
      --bg-card: rgba(255, 255, 255, 0.85);
      --bg-card-hover: #ffffff;
      --border-color: rgba(0, 0, 0, 0.08);
      --border-hover: rgba(99, 102, 241, 0.5);
      --text-primary: #0f172a;
      --text-secondary: #475569;
      --text-muted: #94a3b8;
      --shadow-card: 0 4px 20px -2px rgba(0, 0, 0, 0.06);
      --shadow-hover: 0 10px 25px -4px rgba(99, 102, 241, 0.2);
      --icon-color: #0f172a;
    }}

    * {{
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }}

    body {{
      font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      transition: background-color 0.3s ease, color 0.3s ease;
      line-height: 1.5;
    }}

    .container {{
      max-width: 1440px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem 5rem;
    }}

    .header {{
      text-align: center;
      margin-bottom: 2.5rem;
      position: relative;
    }}

    .badge {{
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 1rem;
      background: rgba(99, 102, 241, 0.12);
      color: #818cf8;
      border: 1px solid rgba(99, 102, 241, 0.3);
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }}

    .title {{
      font-size: 3rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.75rem;
    }}

    .subtitle {{
      color: var(--text-secondary);
      font-size: 1.1rem;
      max-width: 680px;
      margin: 0 auto;
    }}

    .controls-panel {{
      background: var(--bg-card);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-card);
      position: sticky;
      top: 1rem;
      z-index: 100;
    }}

    .controls-row {{
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.25rem;
      flex-wrap: wrap;
    }}

    .search-box {{
      flex: 1;
      min-width: 280px;
      position: relative;
    }}

    .search-icon {{
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      pointer-events: none;
      font-size: 1.1rem;
    }}

    .search-input {{
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 2.75rem;
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      color: var(--text-primary);
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s ease;
    }}

    .search-input:focus {{
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.25);
    }}

    .control-group {{
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }}

    .control-label {{
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }}

    .slider-container {{
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--bg-secondary);
      padding: 0.4rem 0.75rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
    }}

    input[type="range"] {{
      accent-color: var(--accent);
      cursor: pointer;
      width: 100px;
    }}

    .size-val {{
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--accent);
      min-width: 34px;
    }}

    .color-picker-wrap {{
      display: flex;
      align-items: center;
      gap: 0.35rem;
      background: var(--bg-secondary);
      padding: 0.35rem 0.6rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
    }}

    .swatch-btn {{
      width: 22px;
      height: 22px;
      border-radius: 50%;
      border: 2px solid transparent;
      cursor: pointer;
      transition: transform 0.15s ease, border-color 0.15s ease;
    }}

    .swatch-btn:hover {{
      transform: scale(1.15);
    }}

    .swatch-btn.active {{
      border-color: var(--text-primary);
      box-shadow: 0 0 0 2px var(--bg-secondary);
    }}

    .btn-group {{
      display: inline-flex;
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 0.25rem;
      gap: 0.25rem;
    }}

    .btn-icon {{
      background: transparent;
      border: none;
      color: var(--text-secondary);
      padding: 0.45rem 0.65rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 0.9rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }}

    .btn-icon:hover {{
      color: var(--text-primary);
      background: rgba(255, 255, 255, 0.05);
    }}

    .btn-icon.active {{
      background: var(--accent);
      color: #ffffff;
    }}

    .results-meta {{
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.25rem;
      color: var(--text-secondary);
      font-size: 0.9rem;
      padding: 0 0.5rem;
    }}

    .icon-grid {{
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1.25rem;
    }}

    .icon-grid.layout-compact {{
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 0.75rem;
    }}

    .icon-grid.layout-list {{
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }}

    .icon-card {{
      background: var(--bg-card);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 1.25rem 1rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      cursor: pointer;
      position: relative;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      overflow: hidden;
    }}

    .icon-card:hover {{
      transform: translateY(-4px);
      border-color: var(--border-hover);
      background: var(--bg-card-hover);
      box-shadow: var(--shadow-hover);
    }}

    .icon-preview-box {{
      min-height: 70px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 0.75rem;
      color: var(--icon-color);
      transition: transform 0.2s ease;
    }}

    .icon-card:hover .icon-preview-box {{
      transform: scale(1.15);
    }}

    .icon-preview-box i {{
      font-size: var(--icon-size);
    }}

    .icon-name {{
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-primary);
      word-break: break-word;
      margin-bottom: 0.35rem;
      max-width: 100%;
    }}

    .icon-meta {{
      display: inline-flex;
      align-items: center;
      font-family: monospace;
      font-size: 0.75rem;
      color: var(--text-muted);
      background: var(--bg-secondary);
      padding: 0.15rem 0.5rem;
      border-radius: 4px;
    }}

    .card-actions {{
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      opacity: 0;
      transition: opacity 0.2s ease;
      display: flex;
      gap: 0.25rem;
    }}

    .icon-card:hover .card-actions {{
      opacity: 1;
    }}

    .action-btn {{
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      color: var(--text-secondary);
      width: 28px;
      height: 28px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 0.8rem;
      transition: all 0.15s ease;
    }}

    .action-btn:hover {{
      background: var(--accent);
      color: white;
      border-color: var(--accent);
    }}

    .layout-list .icon-card {{
      flex-direction: row;
      justify-content: space-between;
      padding: 0.75rem 1.5rem;
    }}

    .layout-list .icon-preview-box {{
      min-height: auto;
      margin-bottom: 0;
      width: 60px;
    }}

    .layout-list .icon-name {{
      flex: 1;
      text-align: left;
      margin-bottom: 0;
      padding-left: 1.25rem;
      font-size: 0.95rem;
    }}

    .layout-list .icon-meta {{
      margin-right: 1.25rem;
    }}

    .layout-list .card-actions {{
      position: static;
      opacity: 1;
    }}

    .toast {{
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: #10b981;
      color: #ffffff;
      padding: 0.85rem 1.5rem;
      border-radius: var(--radius-md);
      box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-weight: 600;
      font-size: 0.95rem;
      z-index: 1000;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      pointer-events: none;
    }}

    .toast.show {{
      transform: translateY(0);
      opacity: 1;
    }}

    .modal-overlay {{
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
      padding: 1.5rem;
    }}

    .modal-overlay.open {{
      opacity: 1;
      pointer-events: auto;
    }}

    .modal {{
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      max-width: 540px;
      width: 100%;
      padding: 2rem;
      position: relative;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
      transform: scale(0.95);
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }}

    .modal-overlay.open .modal {{
      transform: scale(1);
    }}

    .modal-close {{
      position: absolute;
      top: 1.25rem;
      right: 1.25rem;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-size: 1.5rem;
      line-height: 1;
      cursor: pointer;
      transition: color 0.15s ease;
    }}

    .modal-close:hover {{
      color: var(--text-primary);
    }}

    .modal-preview {{
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-primary);
      border-radius: var(--radius-md);
      padding: 2.5rem;
      margin-bottom: 1.5rem;
      border: 1px solid var(--border-color);
    }}

    .modal-preview i {{
      font-size: 72px;
      color: var(--accent);
    }}

    .modal-title {{
      font-size: 1.35rem;
      font-weight: 700;
      margin-bottom: 1.25rem;
      text-align: center;
    }}

    .snippet-group {{
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
    }}

    .snippet-item {{
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }}

    .snippet-label {{
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-secondary);
    }}

    .snippet-code {{
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--bg-primary);
      border: 1px solid var(--border-color);
      padding: 0.65rem 0.85rem;
      border-radius: var(--radius-sm);
      font-family: monospace;
      font-size: 0.85rem;
      color: var(--text-primary);
    }}

    .snippet-copy-btn {{
      background: transparent;
      border: none;
      color: var(--accent);
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      padding: 0.25rem 0.6rem;
      border-radius: 4px;
      transition: background 0.15s ease;
    }}

    .snippet-copy-btn:hover {{
      background: rgba(99, 102, 241, 0.15);
    }}

    .empty-state {{
      grid-column: 1 / -1;
      text-align: center;
      padding: 4rem 1rem;
      color: var(--text-muted);
    }}
  </style>
</head>
<body>

  <div class="container">
    <!-- Header -->
    <header class="header">
      <div class="badge">
        <i class="lp-icon-check-circle"></i> LearnPress Icon Font Preview
      </div>
      <h1 class="title">LearnPress Icons</h1>
      <p class="subtitle">Toàn bộ {total_count} icon font từ CSS <code>assets/css/admin/admin.css</code>. Click vào icon để copy class name, mã HTML hoặc mã Unicode.</p>
    </header>

    <!-- Controls Panel -->
    <div class="controls-panel">
      <div class="controls-row">
        <!-- Search Input -->
        <div class="search-box">
          <i class="lp-icon-search search-icon"></i>
          <input type="text" id="searchInput" class="search-input" placeholder="Tìm kiếm icon theo tên hoặc mã hex (vd: search, f002, book, user)..." autofocus>
        </div>

        <!-- Size Slider -->
        <div class="control-group">
          <span class="control-label"><i class="lp-icon-expand"></i> Size:</span>
          <div class="slider-container">
            <input type="range" id="sizeSlider" min="16" max="72" value="32" step="2">
            <span class="size-val" id="sizeVal">32px</span>
          </div>
        </div>

        <!-- Color Palette -->
        <div class="control-group">
          <span class="control-label"><i class="lp-icon-art-and-design"></i> Color:</span>
          <div class="color-picker-wrap">
            <button class="swatch-btn active" style="background: #f8fafc;" data-color="#f8fafc" title="Default"></button>
            <button class="swatch-btn" style="background: #6366f1;" data-color="#6366f1" title="Indigo"></button>
            <button class="swatch-btn" style="background: #10b981;" data-color="#10b981" title="Emerald"></button>
            <button class="swatch-btn" style="background: #f59e0b;" data-color="#f59e0b" title="Amber"></button>
            <button class="swatch-btn" style="background: #ef4444;" data-color="#ef4444" title="Red"></button>
            <button class="swatch-btn" style="background: #a855f7;" data-color="#a855f7" title="Purple"></button>
            <button class="swatch-btn" style="background: #ec4899;" data-color="#ec4899" title="Pink"></button>
          </div>
        </div>

        <!-- Layout & Theme Toggles -->
        <div class="control-group">
          <div class="btn-group">
            <button id="layoutGrid" class="btn-icon active" title="Grid Layout">
              <i class="lp-icon-th-large"></i>
            </button>
            <button id="layoutCompact" class="btn-icon" title="Compact Layout">
              <i class="lp-icon-th"></i>
            </button>
            <button id="layoutList" class="btn-icon" title="List Layout">
              <i class="lp-icon-th-list"></i>
            </button>
          </div>

          <button id="themeToggle" class="btn-icon" title="Toggle Light / Dark Mode" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
            <i class="lp-icon-certificate" id="themeIcon"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Metadata / Stats -->
    <div class="results-meta">
      <span id="resultsCount">Hiển thị {total_count} icons</span>
      <span>Click vào thẻ để xem chi tiết & copy mã</span>
    </div>

    <!-- Icon Grid Container -->
    <div class="icon-grid" id="iconGrid">
{cards_html}
    </div>

  </div>

  <!-- Detail Modal -->
  <div class="modal-overlay" id="detailModal">
    <div class="modal">
      <button class="modal-close" id="modalClose">&times;</button>
      <div class="modal-preview">
        <i id="modalIconPreview" class=""></i>
      </div>
      <h3 class="modal-title" id="modalIconName">lp-icon-name</h3>

      <div class="snippet-group">
        <div class="snippet-item">
          <span class="snippet-label">HTML Tag</span>
          <div class="snippet-code">
            <span id="snippetHtml">&lt;i class="lp-icon-plus"&gt;&lt;/i&gt;</span>
            <button class="snippet-copy-btn" onclick="copySnippet('snippetHtml')">Copy</button>
          </div>
        </div>

        <div class="snippet-item">
          <span class="snippet-label">Class Name</span>
          <div class="snippet-code">
            <span id="snippetClass">lp-icon-plus</span>
            <button class="snippet-copy-btn" onclick="copySnippet('snippetClass')">Copy</button>
          </div>
        </div>

        <div class="snippet-item">
          <span class="snippet-label">CSS Unicode / Content</span>
          <div class="snippet-code">
            <span id="snippetUnicode">\\f067</span>
            <button class="snippet-copy-btn" onclick="copySnippet('snippetUnicode')">Copy</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div class="toast" id="toast">
    <i class="lp-icon-check"></i> <span id="toastMsg">Copied to clipboard!</span>
  </div>

  <!-- Script -->
  <script>
    const icons = {icons_json};

    const grid = document.getElementById("iconGrid");
    const searchInput = document.getElementById("searchInput");
    const resultsCount = document.getElementById("resultsCount");
    const sizeSlider = document.getElementById("sizeSlider");
    const sizeVal = document.getElementById("sizeVal");
    const toast = document.getElementById("toast");
    const toastMsg = document.getElementById("toastMsg");
    const themeToggle = document.getElementById("themeToggle");
    const detailModal = document.getElementById("detailModal");
    const modalClose = document.getElementById("modalClose");

    const layoutGrid = document.getElementById("layoutGrid");
    const layoutCompact = document.getElementById("layoutCompact");
    const layoutList = document.getElementById("layoutList");

    // Attach click events on static cards
    function attachCardEvents() {{
      document.querySelectorAll(".icon-card").forEach(card => {{
        card.onclick = (e) => {{
          if (e.target.closest(".action-btn")) return;
          const cls = card.getAttribute("data-class");
          const hex = card.getAttribute("data-hex");
          openModal({{ class: cls, hex: hex }});
        }};
      }});
    }}

    attachCardEvents();

    function renderIcons(filtered) {{
      grid.innerHTML = "";
      if (filtered.length === 0) {{
        grid.innerHTML = `
          <div class="empty-state">
            <i class="lp-icon-search" style="font-size: 48px; opacity: 0.4; margin-bottom: 1rem;"></i>
            <h3>Không tìm thấy icon phù hợp</h3>
            <p>Thử tìm với từ khóa hoặc mã hex khác</p>
          </div>
        `;
        resultsCount.innerText = "Không tìm thấy icon";
        return;
      }}

      resultsCount.innerText = `Hiển thị ${{filtered.length}} / ${{icons.length}} icons`;

      filtered.forEach(icon => {{
        const card = document.createElement("div");
        card.className = "icon-card";
        card.setAttribute("data-name", icon.name);
        card.setAttribute("data-class", icon.class);
        card.setAttribute("data-hex", icon.hex);
        card.onclick = (e) => {{
          if (e.target.closest(".action-btn")) return;
          openModal(icon);
        }};

        const hexBadge = icon.hex ? `<span class="icon-meta">\\\\${{icon.hex}}</span>` : "";

        card.innerHTML = `
          <div class="card-actions">
            <button class="action-btn" title="Copy Class" onclick="copyText('${{icon.class}}', 'Copied class ${{icon.class}}!')">
              <i class="lp-icon-copy"></i>
            </button>
          </div>
          <div class="icon-preview-box">
            <i class="${{icon.class}}"></i>
          </div>
          <div class="icon-name">${{icon.name}}</div>
          ${{hexBadge}}
        `;

        grid.appendChild(card);
      }});
    }}

    searchInput.addEventListener("input", (e) => {{
      const q = e.target.value.toLowerCase().trim();
      const filtered = icons.filter(item => 
        item.name.toLowerCase().includes(q) ||
        item.class.toLowerCase().includes(q) ||
        (item.hex && item.hex.toLowerCase().includes(q))
      );
      renderIcons(filtered);
    }});

    sizeSlider.addEventListener("input", (e) => {{
      const sz = e.target.value + "px";
      document.documentElement.style.setProperty("--icon-size", sz);
      sizeVal.innerText = sz;
    }});

    document.querySelectorAll(".swatch-btn").forEach(btn => {{
      btn.addEventListener("click", () => {{
        document.querySelectorAll(".swatch-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const color = btn.getAttribute("data-color");
        document.documentElement.style.setProperty("--icon-color", color);
      }});
    }});

    layoutGrid.addEventListener("click", () => {{
      grid.className = "icon-grid";
      layoutGrid.classList.add("active");
      layoutCompact.classList.remove("active");
      layoutList.classList.remove("active");
    }});

    layoutCompact.addEventListener("click", () => {{
      grid.className = "icon-grid layout-compact";
      layoutCompact.classList.add("active");
      layoutGrid.classList.remove("active");
      layoutList.classList.remove("active");
    }});

    layoutList.addEventListener("click", () => {{
      grid.className = "icon-grid layout-list";
      layoutList.classList.add("active");
      layoutGrid.classList.remove("active");
      layoutCompact.classList.remove("active");
    }});

    themeToggle.addEventListener("click", () => {{
      const current = document.documentElement.getAttribute("data-theme");
      const next = current === "dark" ? "light" : "dark";
      document.documentElement.setAttribute("data-theme", next);
    }});

    function copyText(text, msg = "Copied to clipboard!") {{
      navigator.clipboard.writeText(text).then(() => {{
        showToast(msg);
      }}).catch(() => {{
        const temp = document.createElement("textarea");
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand("copy");
        document.body.removeChild(temp);
        showToast(msg);
      }});
    }}

    function copySnippet(elementId) {{
      const text = document.getElementById(elementId).innerText;
      copyText(text, `Copied: ${{text}}`);
    }}

    let toastTimer = null;
    function showToast(msg) {{
      toastMsg.innerText = msg;
      toast.classList.add("show");
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => {{
        toast.classList.remove("show");
      }}, 2200);
    }}

    function openModal(icon) {{
      document.getElementById("modalIconPreview").className = icon.class;
      document.getElementById("modalIconName").innerText = icon.class;
      document.getElementById("snippetHtml").innerText = `<i class="${{icon.class}}"></i>`;
      document.getElementById("snippetClass").innerText = icon.class;
      document.getElementById("snippetUnicode").innerText = icon.hex ? ("\\\\" + icon.hex) : "N/A";
      detailModal.classList.add("open");
    }}

    modalClose.addEventListener("click", () => {{
      detailModal.classList.remove("open");
    }});

    detailModal.addEventListener("click", (e) => {{
      if (e.target === detailModal) {{
        detailModal.classList.remove("open");
      }}
    }});
  </script>
</body>
</html>
"""

with open(dest_file, "w", encoding="utf-8") as f:
    f.write(html_template)

print(f"Generated {dest_file} with {total_count} icons.")
