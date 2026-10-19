import { Editor, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { TextStyle, Color } from '@tiptap/extension-text-style';
import Highlight from '@tiptap/extension-highlight';
import TextAlign from '@tiptap/extension-text-align';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import Placeholder from '@tiptap/extension-placeholder';

const uploadFolder = 'course-descriptions';
const embedHosts = ['youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'codepen.io', 'codesandbox.io', 'figma.com', 'embed.figma.com', 'docs.google.com', 'drive.google.com', 'notion.so'];
const validUrl = value => {
  try { const url = new URL(/^[a-z][\w+.-]*:/i.test(value) ? value : `https://${value}`); return ['https:', 'http:', 'mailto:', 'tel:'].includes(url.protocol) ? url.href : null; } catch { return null; }
};
const embedUrl = value => {
  const source = new DOMParser().parseFromString(value, 'text/html').querySelector('iframe')?.getAttribute('src') || value.trim();
  try {
    const url = new URL(source);
    if (!['https:', 'http:'].includes(url.protocol) || !embedHosts.some(host => url.hostname === host || url.hostname.endsWith(`.${host}`))) return null;
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
  renderHTML: ({ HTMLAttributes }) => [tag, { src: HTMLAttributes.src, ...(tag === 'video' ? { controls: 'controls', preload: 'metadata' } : { title: 'Embedded question content', loading: 'lazy', allowfullscreen: 'true' }) }],
});

function emitChange(input) {
  input.dispatchEvent(new Event('input', { bubbles: true }));
}

function initializeQuestionEditor(root) {
  if (root.dataset.initialized === 'true') return;
  const row = root.closest('.quiz-question');
  const input = row.querySelector('.question-html');
  const mount = root.querySelector('[data-qe-mount]');
  const find = selector => root.querySelector(selector);
  const buttons = () => root.querySelectorAll('[data-qe-command], [data-qe-action]');
  const popovers = () => root.querySelectorAll('.frappe-rich-editor__popover');
  const closePopovers = () => popovers().forEach(popover => { popover.hidden = true; });
  const closeEmbed = () => { find('[data-qe-embed]').hidden = true; editor.commands.focus(); };
  const editor = new Editor({
    element: mount, content: input.value || '<p></p>',
    extensions: [StarterKit.configure({ heading: { levels: [2, 3, 4] }, link: { openOnClick: false } }), TextStyle, Color, Highlight.configure({ multicolor: true }), TextAlign.configure({ types: ['heading', 'paragraph'] }), Image, TableKit, Placeholder.configure({ placeholder: 'Type your question here' }), mediaNode('questionVideo', 'video'), mediaNode('questionEmbed', 'iframe')],
    onUpdate: ({ editor: current }) => { input.value = current.getHTML(); emitChange(input); updateToolbar(); },
    onSelectionUpdate: () => updateToolbar(),
  });
  root.dataset.initialized = 'true';
  const actions = {
    formatBlock: value => value === 'p' ? editor.chain().focus().setParagraph().run() : value === 'blockquote' ? editor.chain().focus().toggleBlockquote().run() : editor.chain().focus().toggleHeading({ level: Number(value.slice(1)) }).run(),
    bold: () => editor.chain().focus().toggleBold().run(), italic: () => editor.chain().focus().toggleItalic().run(), strikeThrough: () => editor.chain().focus().toggleStrike().run(),
    insertUnorderedList: () => editor.chain().focus().toggleBulletList().run(), insertOrderedList: () => editor.chain().focus().toggleOrderedList().run(),
    justifyLeft: () => editor.chain().focus().setTextAlign('left').run(), justifyCenter: () => editor.chain().focus().setTextAlign('center').run(), justifyRight: () => editor.chain().focus().setTextAlign('right').run(),
    undo: () => editor.chain().focus().undo().run(), redo: () => editor.chain().focus().redo().run(),
  };
  const active = (command, value) => {
    if (command === 'formatBlock') return value === 'p' ? editor.isActive('paragraph') : value === 'blockquote' ? editor.isActive('blockquote') : editor.isActive('heading', { level: Number(value.slice(1)) });
    if (command === 'insertUnorderedList') return editor.isActive('bulletList');
    if (command === 'insertOrderedList') return editor.isActive('orderedList');
    if (command.startsWith('justify')) return editor.isActive({ textAlign: command.slice(7).toLowerCase() });
    return editor.isActive(command === 'strikeThrough' ? 'strike' : command);
  };
  function updateToolbar() {
    root.querySelectorAll('[data-qe-command]').forEach(button => {
      const command = button.dataset.qeCommand, value = button.dataset.qeValue, pressed = active(command, value);
      button.classList.toggle('is-active', pressed); button.setAttribute('aria-pressed', String(pressed));
      if (['undo', 'redo'].includes(command)) button.disabled = !editor.can().chain().focus()[command]().run();
    });
  }
  const position = (popover, button, width) => {
    const open = popover.hidden; closePopovers(); if (!open) return false;
    popover.hidden = false; popover.style.left = `${Math.max(0, Math.min(button.offsetLeft, button.parentElement.clientWidth - width))}px`; popover.style.top = `${button.offsetTop + button.offsetHeight + 4}px`; return true;
  };
  const upload = async (files, type) => {
    for (const file of files) {
      if (file.size > 50 * 1024 * 1024) throw new Error('The file must be 50 MB or smaller.');
      const data = new FormData(); data.append('file', file); data.append('folder', uploadFolder); data.append('is_public', '1');
      const response = await fetch(root.dataset.uploadUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('[name="_token"]')?.value }, body: data });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload?.url) throw new Error(Object.values(payload?.errors || {}).flat().join(' ') || payload?.error || 'Upload failed.');
      if (type === 'image') editor.chain().focus().setImage({ src: payload.url }).run(); else editor.chain().focus().insertContent({ type: 'questionVideo', attrs: { src: payload.url } }).run();
    }
  };
  const colorPopover = find('[data-qe-color]');
  const palette = { text: { red: '#dc2626', orange: '#ea580c', yellow: '#ca8a04', green: '#16a34a', teal: '#0d9488', cyan: '#06b6d4', blue: '#1579d0', indigo: '#5f46c7', purple: '#9333ea', pink: '#db2777', gray: '#6b7280' }, highlight: { red: '#fecaca', orange: '#fed7aa', yellow: '#fef08a', green: '#bbf7d0', teal: '#99f6e4', cyan: '#a5f3fc', blue: '#bfdbfe', indigo: '#dbd5ff', purple: '#e9d5ff', pink: '#fbcfe8', gray: '#e5e7eb' } };
  colorPopover.innerHTML = Object.entries(palette).map(([type, colors]) => `<div class="frappe-rich-editor__palette-label">${type === 'text' ? 'Text Color' : 'Background Color'}</div><div class="frappe-rich-editor__swatches"><button type="button" class="frappe-rich-editor__swatch" data-qe-color="default" data-qe-color-type="${type}">A</button>${Object.entries(colors).map(([name, color]) => `<button type="button" class="frappe-rich-editor__swatch" data-qe-color="${color}" data-qe-color-type="${type}" aria-label="${name} ${type}" style="${type === 'text' ? `color:${color}` : `background:${color}`}">A</button>`).join('')}</div>`).join('');
  colorPopover.addEventListener('click', event => { const swatch = event.target.closest('[data-qe-color]'); if (!swatch) return; const { qeColor: color, qeColorType: type } = swatch.dataset; if (type === 'text') editor.chain().focus()[color === 'default' ? 'unsetColor' : 'setColor'](...(color === 'default' ? [] : [color])).run(); else editor.chain().focus()[color === 'default' ? 'unsetHighlight' : 'setHighlight'](...(color === 'default' ? [] : [{ color }])).run(); closePopovers(); });
  const tablePopover = find('[data-qe-table]'), grid = tablePopover.querySelector('.frappe-rich-editor__table-grid'), tableSize = tablePopover.querySelector('.frappe-rich-editor__table-size');
  const setTable = (rows, columns) => { tableSize.textContent = `${rows} × ${columns}`; grid.querySelectorAll('button').forEach(cell => cell.classList.toggle('is-selected', Number(cell.dataset.row) <= rows && Number(cell.dataset.column) <= columns)); };
  for (let rowNumber = 1; rowNumber <= 6; rowNumber++) for (let column = 1; column <= 8; column++) { const cell = document.createElement('button'); cell.type = 'button'; cell.className = 'frappe-rich-editor__table-cell'; cell.dataset.row = rowNumber; cell.dataset.column = column; cell.setAttribute('aria-label', `${rowNumber} × ${column}`); cell.addEventListener('mouseenter', () => setTable(rowNumber, column)); cell.addEventListener('click', () => { closePopovers(); editor.chain().focus().insertTable({ rows: rowNumber, cols: column, withHeaderRow: true }).run(); }); grid.append(cell); }
  setTable(3, 3);
  buttons().forEach(button => {
    button.setAttribute('aria-label', button.title);
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', () => {
      const command = button.dataset.qeCommand, action = button.dataset.qeAction;
      if (command) return actions[command](button.dataset.qeValue);
      if (action === 'link' && position(find('[data-qe-link]'), button, 248)) find('[data-qe-link] input').focus();
      else if (action === 'color') position(colorPopover, button, 214);
      else if (action === 'image' || action === 'video') find(action === 'image' ? '[data-qe-image]' : '[data-qe-video]').click();
      else if (action === 'code') editor.chain().focus().toggleCode().run();
      else if (action === 'rule') editor.chain().focus().setHorizontalRule().run();
      else if (action === 'table') { setTable(3, 3); position(tablePopover, button, 186); }
      else if (action === 'embed') { const modal = find('[data-qe-embed]'); modal.hidden = false; find('[data-qe-embed-input]').focus(); }
    });
  });
  const linkInput = find('[data-qe-link] input');
  find('[data-qe-apply-link]').addEventListener('click', () => { const url = validUrl(linkInput.value.trim()); if (!url) return linkInput.reportValidity(); editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run(); closePopovers(); });
  for (const type of ['image', 'video']) find(`[data-qe-${type}]`).addEventListener('change', async event => { try { await upload([...event.target.files], type); } catch (error) { window.alert(error.message); } event.target.value = ''; });
  const embedModal = find('[data-qe-embed]'), embedInput = find('[data-qe-embed-input]'), applyEmbed = find('[data-qe-apply-embed]'), embedError = find('[data-qe-embed-error]');
  embedInput.addEventListener('input', () => { const valid = Boolean(embedUrl(embedInput.value)); applyEmbed.disabled = !valid; embedError.classList.toggle('hidden', !embedInput.value || valid); });
  applyEmbed.addEventListener('click', () => { const src = embedUrl(embedInput.value); if (!src) return embedError.classList.remove('hidden'); embedModal.hidden = true; editor.chain().focus().insertContent({ type: 'questionEmbed', attrs: { src } }).run(); });
  find('[data-qe-close-embed]').addEventListener('click', closeEmbed); find('[data-qe-cancel-embed]').addEventListener('click', closeEmbed);
  document.addEventListener('click', event => { if (!root.contains(event.target)) closePopovers(); });
  updateToolbar();
}

function optionRow(index, questionIndex) {
  return `<label class="flex items-start gap-3" data-option-row><input type="radio" name="questions[${questionIndex}][correct_answer]" value="${index}" class="mt-2"><div class="min-w-0 flex-1 space-y-1"><input name="questions[${questionIndex}][options][${index}]" placeholder="Option ${index + 1}" class="w-full rounded-lg border border-gray-300 px-3 py-1 text-sm"><input name="questions[${questionIndex}][explanations][${index}]" placeholder="Explanation for option ${index + 1}, shown as feedback (optional)" class="option-explanation w-full rounded-lg border border-gray-300 px-3 py-1 text-sm text-gray-600"></div><input type="checkbox" name="questions[${questionIndex}][correct_answers][]" value="${index}" class="multiple-correct mt-2"><span class="sr-only">Correct multiple choice answer</span></label>`;
}
function syncQuestionType(row) {
  const type = row.querySelector('.question-type').value, choice = row.querySelector('.choice-fields'), radios = choice.querySelectorAll('input[type="radio"]'), checks = choice.querySelectorAll('.multiple-correct');
  const isChoice = ['single', 'multiple'].includes(type);
  choice.classList.toggle('hidden', !isChoice); row.querySelector('.user-input-fields').classList.toggle('hidden', type !== 'user_input');
  choice.querySelectorAll('input[name*="[options]"], input[name*="[explanations]"]').forEach(input => input.disabled = !isChoice);
  radios.forEach(input => { input.classList.toggle('hidden', type !== 'single'); input.disabled = type !== 'single'; });
  checks.forEach(input => { input.classList.toggle('hidden', type !== 'multiple'); input.disabled = type !== 'multiple'; });
  row.querySelectorAll('.user-input-fields textarea').forEach(input => input.disabled = type !== 'user_input');
}
function setupQuestion(row) {
  row.querySelectorAll('[data-question-editor]').forEach(initializeQuestionEditor); syncQuestionType(row);
}
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.quiz-question').forEach(setupQuestion));
document.addEventListener('quiz-question-added', event => setupQuestion(event.detail));
document.addEventListener('change', event => { if (event.target.matches('.question-type')) syncQuestionType(event.target.closest('.quiz-question')); });
document.addEventListener('click', event => {
  const add = event.target.closest('.add-option'); if (!add) return;
  const choice = add.closest('[data-choice-options]'), row = choice.closest('.quiz-question'), count = choice.querySelectorAll('[data-option-row]').length; if (count >= 10) return;
  const questionIndex = row.querySelector('.question-html').name.match(/questions\[(\d+)]/)[1]; add.insertAdjacentHTML('beforebegin', optionRow(count, questionIndex)); choice.querySelector('[data-option-count]').textContent = `${count + 1}/10`; syncQuestionType(row); add.dispatchEvent(new Event('change', { bubbles: true }));
});
