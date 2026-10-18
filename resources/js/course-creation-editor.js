import { Editor, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { TextStyle, Color } from '@tiptap/extension-text-style';
import Highlight from '@tiptap/extension-highlight';
import TextAlign from '@tiptap/extension-text-align';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import Placeholder from '@tiptap/extension-placeholder';

const mount = document.getElementById('courseEditor');
const form = document.getElementById('newCourseForm');

if (mount && form) {
  const el = (id) => document.getElementById(id);
  const popovers = [...document.querySelectorAll('.frappe-rich-editor__popover')];
  const embedModal = el('courseEditorEmbedModal');
  const closePopovers = () => popovers.forEach((popover) => { popover.hidden = true; });
  const closeEmbed = () => { embedModal.hidden = true; editor.commands.focus(); };
  const validUrl = (value) => {
    try {
      const url = new URL(/^[a-z][\w+.-]*:/i.test(value) ? value : `https://${value}`);
      return ['https:', 'http:', 'mailto:', 'tel:'].includes(url.protocol) ? url.href : null;
    } catch { return null; }
  };
  const embedHosts = ['youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'codepen.io', 'codesandbox.io', 'figma.com', 'embed.figma.com', 'docs.google.com', 'drive.google.com', 'notion.so'];
  const embedUrl = (input) => {
    const source = new DOMParser().parseFromString(input, 'text/html').querySelector('iframe')?.getAttribute('src') || input.trim();
    try {
      const url = new URL(source);
      if (!['https:', 'http:'].includes(url.protocol) || !embedHosts.some((host) => url.hostname === host || url.hostname.endsWith(`.${host}`))) return null;
      if (url.hostname.endsWith('youtube.com') && url.pathname === '/watch' && url.searchParams.get('v')) return `https://www.youtube.com/embed/${encodeURIComponent(url.searchParams.get('v'))}`;
      if (url.hostname === 'youtu.be') return `https://www.youtube.com/embed/${encodeURIComponent(url.pathname.slice(1).split('/')[0])}`;
      if (url.hostname === 'vimeo.com' && /^\/\d+$/.test(url.pathname)) return `https://player.vimeo.com/video${url.pathname}`;
      return url.href;
    } catch { return null; }
  };
  const mediaNode = (name, tag) => Node.create({
    name, group: 'block', atom: true, draggable: true,
    addAttributes: () => ({ src: { default: null } }),
    parseHTML: () => [{ tag: `${tag}[src]` }],
    renderHTML: ({ HTMLAttributes }) => [tag, { src: HTMLAttributes.src, ...(tag === 'video' ? { controls: 'controls', preload: 'metadata' } : { title: 'Embedded course content', loading: 'lazy', allowfullscreen: 'true' }) }],
  });
  const editor = new Editor({
    element: mount,
    content: el('courseDescription').value || '<p></p>',
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3, 4] }, link: { openOnClick: false } }),
      TextStyle, Color, Highlight.configure({ multicolor: true }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }), Image, TableKit,
      Placeholder.configure({ placeholder: 'Write a detailed description of your course' }),
      mediaNode('courseVideo', 'video'), mediaNode('courseEmbed', 'iframe'),
    ],
    onUpdate: ({ editor: current }) => { el('courseDescription').value = current.getHTML(); updateToolbar(); },
    onSelectionUpdate: () => updateToolbar(),
  });

  const actions = {
    formatBlock: (value) => value === 'p' ? editor.chain().focus().setParagraph().run() : value === 'blockquote' ? editor.chain().focus().toggleBlockquote().run() : editor.chain().focus().toggleHeading({ level: Number(value.slice(1)) }).run(),
    bold: () => editor.chain().focus().toggleBold().run(),
    italic: () => editor.chain().focus().toggleItalic().run(),
    strikeThrough: () => editor.chain().focus().toggleStrike().run(),
    insertUnorderedList: () => editor.chain().focus().toggleBulletList().run(),
    insertOrderedList: () => editor.chain().focus().toggleOrderedList().run(),
    justifyLeft: () => editor.chain().focus().setTextAlign('left').run(),
    justifyCenter: () => editor.chain().focus().setTextAlign('center').run(),
    justifyRight: () => editor.chain().focus().setTextAlign('right').run(),
    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),
  };
  const active = (command, value) => {
    if (command === 'formatBlock') return value === 'p' ? editor.isActive('paragraph') : value === 'blockquote' ? editor.isActive('blockquote') : editor.isActive('heading', { level: Number(value.slice(1)) });
    if (command === 'insertUnorderedList') return editor.isActive('bulletList');
    if (command === 'insertOrderedList') return editor.isActive('orderedList');
    if (command.startsWith('justify')) return editor.isActive({ textAlign: command.slice(7).toLowerCase() });
    return editor.isActive(command === 'strikeThrough' ? 'strike' : command);
  };
  function updateToolbar() {
    document.querySelectorAll('[data-editor-command]').forEach((button) => {
      const pressed = active(button.dataset.editorCommand, button.dataset.editorValue);
      button.classList.toggle('is-active', pressed);
      button.setAttribute('aria-pressed', String(pressed));
      if ((button.dataset.editorCommand === 'formatBlock' && /^h[234]$/.test(button.dataset.editorValue)) || button.dataset.editorValue === 'blockquote') button.disabled = editor.isActive('listItem');
      if (['undo', 'redo'].includes(button.dataset.editorCommand)) button.disabled = !editor.can().chain().focus()[button.dataset.editorCommand]().run();
    });
  }
  const positionPopover = (popover, button, width) => {
    const open = popover.hidden;
    closePopovers();
    if (!open) return false;
    popover.hidden = false;
    popover.style.left = `${Math.max(0, Math.min(button.offsetLeft, button.parentElement.clientWidth - width))}px`;
    popover.style.top = `${button.offsetTop + button.offsetHeight + 4}px`;
    return true;
  };
  const upload = async (files, type) => {
    for (const file of files) {
      const data = new FormData();
      data.append('file', file);
      data.append('folder', 'course-descriptions');
      data.append('is_public', '1');
      const response = await fetch(mount.dataset.uploadUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value }, body: data });
      const payload = await response.json();
      if (!response.ok || !payload.url) throw new Error(Object.values(payload.errors || {}).flat().join(' ') || payload.error || 'Upload failed.');
      if (type === 'image') editor.chain().focus().setImage({ src: payload.url }).run();
      else editor.chain().focus().insertContent({ type: 'courseVideo', attrs: { src: payload.url } }).run();
      editor.commands.setTextSelection(editor.state.selection.to);
    }
  };

  document.querySelectorAll('[data-editor-command], [data-editor-action]').forEach((button) => {
    button.setAttribute('aria-label', button.title);
    button.addEventListener('mousedown', (event) => event.preventDefault());
    button.addEventListener('click', () => {
      const { editorCommand: command, editorAction: action } = button.dataset;
      if (command) return actions[command](button.dataset.editorValue);
      if (action === 'link') {
        if (positionPopover(el('courseEditorUrlPopover'), button, 248)) {
          const href = editor.getAttributes('link').href;
          el('courseEditorLinkPreview').hidden = !href;
          el('courseEditorLinkFields').hidden = Boolean(href);
          el('courseEditorUrl').value = href || '';
          el('courseEditorUrl').setCustomValidity('');
          if (href) {
            el('courseEditorLinkTarget').href = href;
            el('courseEditorLinkTarget').textContent = href;
          } else el('courseEditorUrl').focus();
        }
      } else if (action === 'color') { updateColorSelection(); positionPopover(el('courseEditorColorPopover'), button, 214); }
      else if (action === 'image' || action === 'video') el(action === 'image' ? 'courseEditorImage' : 'courseEditorVideo').click();
      else if (action === 'code') editor.chain().focus().toggleCode().run();
      else if (action === 'rule') editor.chain().focus().setHorizontalRule().run();
      else if (action === 'table') { setTableSelection(3, 3); if (positionPopover(el('courseEditorTablePopover'), button, 186)) el('courseEditorTableGrid').querySelector('[data-row="3"][data-column="3"]').focus(); }
      else if (action === 'embed') {
        el('courseEditorEmbedInput').value = '';
        el('courseEditorEmbedApply').disabled = true;
        el('courseEditorEmbedError').classList.add('hidden');
        embedModal.hidden = false;
        el('courseEditorEmbedInput').focus();
      }
    });
  });

  const urlInput = el('courseEditorUrl');
  const applyLink = () => {
    const url = validUrl(urlInput.value.trim());
    if (!url) { urlInput.setCustomValidity('Enter a valid URL.'); urlInput.reportValidity(); return; }
    urlInput.setCustomValidity('');
    editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    closePopovers();
  };
  el('courseEditorUrlApply').addEventListener('click', applyLink);
  urlInput.addEventListener('input', () => urlInput.setCustomValidity(''));
  urlInput.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); applyLink(); } });
  el('courseEditorLinkEdit').addEventListener('click', () => {
    el('courseEditorLinkPreview').hidden = true;
    el('courseEditorLinkFields').hidden = false;
    urlInput.focus();
  });
  el('courseEditorLinkCopy').addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(el('courseEditorLinkTarget').href); }
    catch { window.alert('Could not copy the link.'); }
  });

  const palette = {
    text: { red: '#dc2626', orange: '#ea580c', yellow: '#ca8a04', green: '#16a34a', teal: '#0d9488', cyan: '#06b6d4', blue: '#1579d0', indigo: '#5f46c7', purple: '#9333ea', pink: '#db2777', gray: '#6b7280' },
    highlight: { red: '#fecaca', orange: '#fed7aa', yellow: '#fef08a', green: '#bbf7d0', teal: '#99f6e4', cyan: '#a5f3fc', blue: '#bfdbfe', indigo: '#dbd5ff', purple: '#e9d5ff', pink: '#fbcfe8', gray: '#e5e7eb' },
  };
  const colorSection = (label, type) => `<div class="frappe-rich-editor__palette-label${type === 'highlight' ? ' mt-4' : ''}">${label}</div><div class="frappe-rich-editor__swatches"><button type="button" class="frappe-rich-editor__swatch" data-editor-color="default" data-color-type="${type}" data-default aria-label="Default ${label}">A</button>${Object.entries(palette[type]).map(([name, color]) => `<button type="button" class="frappe-rich-editor__swatch" data-editor-color="${color}" data-color-type="${type}" aria-label="${name} ${label}" style="${type === 'text' ? `color:${color}` : `background:${color};color:#1f2937`}">A</button>`).join('')}</div>`;
  const colorPopover = el('courseEditorColorPopover');
  colorPopover.innerHTML = colorSection('Text Color', 'text') + colorSection('Background Color', 'highlight');
  function updateColorSelection() {
    const selected = { text: editor.getAttributes('textStyle').color, highlight: editor.getAttributes('highlight').color };
    colorPopover.querySelectorAll('[data-editor-color]').forEach((button) => {
      const active = button.dataset.editorColor === (selected[button.dataset.colorType] || 'default');
      button.setAttribute('aria-pressed', String(active));
      button.classList.toggle('is-active', active);
    });
  }
  colorPopover.addEventListener('mousedown', (event) => event.preventDefault());
  colorPopover.addEventListener('click', (event) => {
    const swatch = event.target.closest('[data-editor-color]');
    if (!swatch) return;
    const { editorColor: color, colorType: type } = swatch.dataset;
    if (type === 'text') editor.chain().focus()[color === 'default' ? 'unsetColor' : 'setColor'](...(color === 'default' ? [] : [color])).run();
    else editor.chain().focus()[color === 'default' ? 'unsetHighlight' : 'setHighlight'](...(color === 'default' ? [] : [{ color }])).run();
    closePopovers();
  });

  const grid = el('courseEditorTableGrid');
  function setTableSelection(rows, columns) {
    const popover = el('courseEditorTablePopover');
    popover.dataset.rows = rows;
    popover.dataset.columns = columns;
    popover.setAttribute('aria-label', `Table size, ${rows} rows by ${columns} columns`);
    el('courseEditorTableSize').textContent = `${rows} × ${columns}`;
    grid.querySelectorAll('button').forEach((cell) => cell.classList.toggle('is-selected', Number(cell.dataset.row) <= rows && Number(cell.dataset.column) <= columns));
  }
  for (let row = 1; row <= 6; row++) for (let column = 1; column <= 8; column++) {
    const cell = document.createElement('button');
    cell.type = 'button';
    cell.className = 'frappe-rich-editor__table-cell';
    cell.dataset.row = row;
    cell.dataset.column = column;
    cell.setAttribute('aria-label', `${row} × ${column}`);
    cell.addEventListener('mouseenter', () => setTableSelection(row, column));
    cell.addEventListener('focus', () => setTableSelection(row, column));
    cell.addEventListener('click', () => {
      closePopovers();
      editor.chain().focus().insertTable({ rows: row, cols: column, withHeaderRow: true }).run();
    });
    grid.append(cell);
  }
  setTableSelection(3, 3);
  grid.addEventListener('keydown', (event) => {
    if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(event.key)) return;
    event.preventDefault();
    if (event.key === 'Enter') return document.activeElement.click();
    const row = Math.max(1, Math.min(6, Number(el('courseEditorTablePopover').dataset.rows) + (event.key === 'ArrowDown' ? 1 : event.key === 'ArrowUp' ? -1 : 0)));
    const column = Math.max(1, Math.min(8, Number(el('courseEditorTablePopover').dataset.columns) + (event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0)));
    grid.querySelector(`[data-row="${row}"][data-column="${column}"]`).focus();
  });

  const embedInput = el('courseEditorEmbedInput');
  embedInput.addEventListener('input', () => {
    const valid = Boolean(embedUrl(embedInput.value));
    el('courseEditorEmbedApply').disabled = !valid;
    el('courseEditorEmbedError').classList.toggle('hidden', !embedInput.value || valid);
  });
  el('courseEditorEmbedApply').addEventListener('click', () => {
    const src = embedUrl(embedInput.value);
    if (!src) { el('courseEditorEmbedError').classList.remove('hidden'); return; }
    embedModal.hidden = true;
    editor.chain().focus().insertContent({ type: 'courseEmbed', attrs: { src } }).run();
  });
  el('courseEditorEmbedCancel').addEventListener('click', closeEmbed);
  el('courseEditorEmbedClose').addEventListener('click', closeEmbed);
  embedModal.addEventListener('click', (event) => { if (event.target === embedModal) closeEmbed(); });
  for (const type of ['Image', 'Video']) {
    const input = el(`courseEditor${type}`);
    input.addEventListener('change', async () => {
      try { await upload([...input.files], type.toLowerCase()); } catch (error) { window.alert(error.message); }
      input.value = '';
    });
  }
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.frappe-rich-editor__popover, [data-editor-action="link"], [data-editor-action="color"], [data-editor-action="table"]')) closePopovers();
  });
  form.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); form.requestSubmit(); }
  });
  window.skillspotCourseEditor = {
    getHTML: () => editor.getHTML(),
    hasContent: () => {
      if (editor.getText().trim()) return true;
      let media = false;
      editor.state.doc.descendants((node) => { if (['image', 'courseVideo', 'courseEmbed'].includes(node.type.name)) media = true; });
      return media;
    },
    focus: () => editor.commands.focus(),
    closeUi: () => { closePopovers(); embedModal.hidden = true; },
    handleEscape: () => {
      if (!embedModal.hidden) { closeEmbed(); return true; }
      if (popovers.some((popover) => !popover.hidden)) { closePopovers(); editor.commands.focus(); return true; }
      return false;
    },
  };
  updateToolbar();
}
