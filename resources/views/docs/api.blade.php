<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Task Manager API</title>
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css" />
    <style>
      html, body { height: 100%; margin: 0; }
      body { display: grid; grid-template-rows: auto 1fr; }
      .site-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-family: sans-serif;
      }
      .brand-title { font-weight: 700; }
      .brand-subtitle { color: #6b7280; font-size: 14px; }
      .docs-shell, elements-api { height: 100%; }
    </style>
  </head>
  <body>
    <header class="site-header">
      <div>
        <div class="brand-title">Task Manager API</div>
        <div class="brand-subtitle">Reference documentation</div>
      </div>
    </header>
    <main class="docs-shell">
      <elements-api
        apiDescriptionUrl="/docs/api.json"
        router="hash"
        layout="sidebar"
      ></elements-api>
    </main>
  </body>
</html>