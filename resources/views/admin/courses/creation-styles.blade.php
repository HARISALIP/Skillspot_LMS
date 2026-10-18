  .frappe-rich-editor { --editor-bg:#fff; --editor-border:#d1d5db; --editor-text:#374151; --editor-muted:#6b7280; --editor-hover:#f3f4f6; --editor-focus:#2563eb; overflow:visible; border:1px solid var(--editor-border); border-radius:12px; background:var(--editor-bg); color:var(--editor-text); }
  body[data-theme="dark"] .frappe-rich-editor { --editor-bg:#171717; --editor-border:#444; --editor-text:#d4d4d4; --editor-muted:#a3a3a3; --editor-hover:#292929; --editor-focus:#f5f5f5; }
  .frappe-rich-editor:focus-within { border-color:var(--editor-focus); box-shadow:0 0 0 1px var(--editor-focus); }
  .frappe-rich-editor__toolbar { position:relative; display:flex; min-height:60px; flex-wrap:wrap; align-items:center; gap:2px; border-bottom:1px solid var(--editor-border); padding:5px 8px; }
  .frappe-rich-editor__tool { display:inline-flex; height:24px; min-width:24px; align-items:center; justify-content:center; border-radius:4px; padding:0 5px; color:var(--editor-muted); font-size:12px; line-height:1; }
  .frappe-rich-editor__tool:hover, .frappe-rich-editor__tool:focus-visible, .frappe-rich-editor__tool.is-active { background:var(--editor-hover); color:var(--editor-focus); outline:none; }
  .frappe-rich-editor__heading { min-width:auto; font-size:13px; font-weight:600; }
  .frappe-rich-editor__divider { width:1px; height:16px; margin:0 3px; background:var(--editor-border); }
  .frappe-rich-editor__history { display:flex; flex-basis:100%; gap:2px; }
  .frappe-rich-editor__popover { position:absolute; z-index:20; display:flex; width:240px; gap:4px; border:1px solid var(--editor-border); border-radius:8px; background:var(--editor-bg); padding:4px; box-shadow:0 8px 24px rgb(0 0 0 / .22); }
  .frappe-rich-editor__popover[hidden] { display:none; }
  .frappe-rich-editor__popover input { min-width:0; flex:1; border:1px solid var(--editor-border); border-radius:6px; background:var(--editor-bg); color:var(--editor-text); padding:5px 8px; font-size:12px; outline:none; }
  .frappe-rich-editor__popover input:focus { border-color:var(--editor-focus); }
  .frappe-rich-editor__palette { display:block; width:206px; padding:10px; }
  .frappe-rich-editor__palette-label { margin-bottom:8px; color:var(--editor-muted); font-size:12px; font-weight:400; }
  .frappe-rich-editor__swatches { display:grid; grid-template-columns:repeat(6, 24px); gap:8px; }
  .frappe-rich-editor__swatch { display:flex; width:24px; height:24px; align-items:center; justify-content:center; border:1px solid transparent; border-radius:4px; font-size:14px; }
  .frappe-rich-editor__swatch:hover, .frappe-rich-editor__swatch:focus-visible { outline:2px solid var(--editor-muted); outline-offset:1px; }
  .frappe-rich-editor__swatch[data-default] { border-color:var(--editor-border); color:var(--editor-text); }
  .frappe-rich-editor__table-picker { display:block; width:178px; padding:10px; }
  .frappe-rich-editor__table-grid { display:grid; grid-template-columns:repeat(8, 16px); gap:4px; }
  .frappe-rich-editor__table-cell { width:16px; height:16px; border:1px solid var(--editor-border); border-radius:3px; background:var(--editor-bg); }
  .frappe-rich-editor__table-cell.is-selected { border-color:var(--editor-muted); background:var(--editor-muted); }
  .frappe-rich-editor__table-size { margin-top:10px; text-align:center; color:var(--editor-muted); font-size:12px; }
  .frappe-rich-editor__content { color:var(--editor-text); font-size:14px; line-height:1.5; }
  .frappe-rich-editor__content .tiptap { min-height:160px; max-height:272px; overflow-y:auto; padding:12px; outline:none; }
  .frappe-rich-editor__content .tiptap p.is-editor-empty:first-child::before { content:attr(data-placeholder); float:left; height:0; color:var(--editor-muted); pointer-events:none; }
  .frappe-rich-editor__content :where(img, video, iframe) { max-width:100%; margin:.75rem 0; }
  .frappe-rich-editor__content iframe { min-height:220px; width:100%; border:0; }
  .frappe-rich-editor__content :where(ul, ol) { margin:.5rem 0; padding-left:1.5rem; }
  .frappe-rich-editor__content ul { list-style:disc; }
  .frappe-rich-editor__content ol { list-style:decimal; }
  .frappe-rich-editor__content blockquote { margin:.75rem 0; border-left:2px solid var(--editor-border); padding-left:1rem; font-style:italic; }
  .frappe-rich-editor__content code { border-radius:3px; background:var(--editor-hover); padding:2px 4px; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
  .frappe-rich-editor__content hr { margin:1rem 0; border:0; border-top:1px solid var(--editor-border); }
  .frappe-rich-editor__content table { width:100%; table-layout:fixed; border-collapse:collapse; }
  .frappe-rich-editor__content :where(th, td) { min-width:4rem; border:1px solid var(--editor-border); padding:.5rem; text-align:left; }
  .frappe-rich-editor__content th { background:var(--editor-hover); font-weight:600; }
  .frappe-embed-modal[hidden] { display:none; }
