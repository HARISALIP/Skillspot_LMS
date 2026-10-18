<script>
  const courseCreateMenu = document.getElementById('courseCreateMenu');
  const courseCreateMenuButton = document.getElementById('courseCreateMenuButton');
  const courseCreateMenuItems = document.getElementById('courseCreateMenuItems');
  courseCreateMenuButton?.addEventListener('click', () => {
    const isOpen = !courseCreateMenuItems.classList.toggle('hidden');
    courseCreateMenuButton.setAttribute('aria-expanded', String(isOpen));
  });
  document.addEventListener('click', (event) => {
    if (!courseCreateMenu?.contains(event.target)) {
      courseCreateMenuItems?.classList.add('hidden');
      courseCreateMenuButton?.setAttribute('aria-expanded', 'false');
    }
  });
  const closeEditorPopovers = () => document.querySelectorAll('.frappe-rich-editor__popover').forEach((popover) => { popover.hidden = true; });
  const newCourseModal = document.getElementById('newCourseModal');
  const openNewCourseModal = document.getElementById('openNewCourseModal');
  let courseModalReturnFocus = null;
  const closeNewCourseModal = () => {
    closeEditorPopovers();
    newCourseModal?.classList.add('hidden');
    const url = new URL(window.location.href);
    url.searchParams.delete('new');
    window.history.replaceState(window.history.state, '', url);
    courseModalReturnFocus?.focus();
  };
  const showNewCourseModal = (event) => {
    event?.preventDefault();
    courseModalReturnFocus = document.activeElement;
    courseCreateMenuItems?.classList.add('hidden');
    courseCreateMenuButton?.setAttribute('aria-expanded', 'false');
    newCourseModal?.classList.remove('hidden');
    const url = new URL(window.location.href);
    url.searchParams.set('new', '1');
    window.history.pushState(window.history.state, '', url);
    document.querySelector('#newCourseForm [name="title"]')?.focus();
  };
  openNewCourseModal?.addEventListener('click', showNewCourseModal);
  window.addEventListener('skillspot:new-course', showNewCourseModal);
  if (new URLSearchParams(window.location.search).has('new') || @json((bool) old('modal_create'))) {
    newCourseModal?.classList.remove('hidden');
    document.querySelector('#newCourseForm [name="title"]')?.focus();
  }
  window.addEventListener('popstate', () => {
    if (!new URLSearchParams(window.location.search).has('new')) newCourseModal?.classList.add('hidden');
  });
  document.querySelectorAll('.closeNewCourseModal').forEach((button) => button.addEventListener('click', closeNewCourseModal));
  newCourseModal?.addEventListener('click', (event) => {
    if (event.target === newCourseModal) closeNewCourseModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Tab' && !newCourseModal?.classList.contains('hidden')) {
      const activeDialog = !newInstructorModal?.classList.contains('hidden') ? newInstructorModal : newCourseModal;
      const controls = [...activeDialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select, textarea, [contenteditable="true"]')]
        .filter((element) => element.getClientRects().length);
      if (controls.length && event.shiftKey && document.activeElement === controls[0]) { event.preventDefault(); controls.at(-1).focus(); }
      else if (controls.length && !event.shiftKey && document.activeElement === controls.at(-1)) { event.preventDefault(); controls[0].focus(); }
    }
    if (event.key === 'Escape') {
      if (!courseEditorEmbedModal?.hidden) { closeEmbedModal(); return; }
      if (document.querySelector('.frappe-rich-editor__popover:not([hidden])')) {
        closeEditorPopovers();
        return;
      }
      if (!newInstructorModal?.classList.contains('hidden')) closeNewInstructorModal();
      else closeNewCourseModal();
    }
  });

  const categoryPicker = document.getElementById('categoryPicker');
  const categoryPickerButton = document.getElementById('categoryPickerButton');
  const categoryPickerMenu = document.getElementById('categoryPickerMenu');
  const categoryPickerLabel = document.getElementById('categoryPickerLabel');
  const selectedCategory = document.getElementById('selectedCategory');
  const newCategory = document.getElementById('newCategory');
  const chooseCategory = (name, isNew = false) => {
    selectedCategory.value = name;
    newCategory.value = isNew ? name : '';
    categoryPickerLabel.textContent = name;
    categoryPickerMenu.classList.add('hidden');
    categoryPickerButton.setAttribute('aria-expanded', 'false');
  };
  categoryPickerButton?.addEventListener('click', () => {
    categoryPickerMenu.classList.toggle('hidden');
    categoryPickerButton.setAttribute('aria-expanded', String(!categoryPickerMenu.classList.contains('hidden')));
  });
  document.querySelectorAll('.categoryOption').forEach((option) => option.addEventListener('click', () => chooseCategory(option.dataset.category)));
  document.getElementById('showNewCategory')?.addEventListener('click', () => {
    document.getElementById('newCategoryPanel').classList.remove('hidden');
    document.getElementById('newCategoryPanel').classList.add('flex');
    document.getElementById('newCategoryName').focus();
  });
  document.getElementById('createCategory')?.addEventListener('click', async (event) => {
    const name = document.getElementById('newCategoryName').value.trim();
    if (!name) return document.getElementById('newCategoryName').focus();
    const createButton = event.currentTarget;
    createButton.disabled = true;
    try {
      const response = await fetch('{{ route('courses.create.categories') }}', {
        method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('#newCourseForm input[name="_token"]').value },
        body: JSON.stringify({ name }),
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat().join(' ') || 'Could not create category.');
      const option = document.createElement('button');
      option.type = 'button'; option.className = 'categoryOption block w-full px-3 py-2 text-left text-sm font-normal text-gray-700 hover:bg-gray-50';
      option.dataset.category = payload.name; option.textContent = payload.name;
      option.addEventListener('click', () => chooseCategory(payload.name));
      categoryPickerMenu.querySelector('.max-h-36').append(option);
      chooseCategory(payload.name);
      document.getElementById('newCategoryName').value = '';
    } catch (error) { window.alert(error.message); }
    finally { createButton.disabled = false; }
  });

  const instructorPicker = document.getElementById('instructorPicker');
  const instructorPickerButton = document.getElementById('instructorPickerButton');
  const instructorPickerMenu = document.getElementById('instructorPickerMenu');
  const instructorPickerLabel = document.getElementById('instructorPickerLabel');
  const updateInstructorLabel = () => {
    const chosen = [...document.querySelectorAll('input[name="instructor_ids[]"]:checked')]
      .map((input) => input.closest('label').querySelector('span').textContent);
    instructorPickerLabel.textContent = chosen.length ? chosen.join(', ') : 'Select instructors';
  };
  updateInstructorLabel();
  instructorPickerButton?.addEventListener('click', () => {
    instructorPickerMenu.classList.toggle('hidden');
    instructorPickerButton.setAttribute('aria-expanded', String(!instructorPickerMenu.classList.contains('hidden')));
  });
  document.querySelectorAll('input[name="instructor_ids[]"]').forEach((input) => input.addEventListener('change', updateInstructorLabel));
  document.getElementById('clearInstructors')?.addEventListener('click', () => {
    document.querySelectorAll('input[name="instructor_ids[]"]:checked').forEach((input) => input.checked = false);
    updateInstructorLabel();
  });
  document.getElementById('instructorSearch')?.addEventListener('input', (event) => {
    const query = event.target.value.toLowerCase();
    document.querySelectorAll('.instructorOption').forEach((option) => option.classList.toggle('hidden', !option.textContent.toLowerCase().includes(query)));
  });

  document.addEventListener('click', (event) => {
    if (!categoryPicker?.contains(event.target)) categoryPickerMenu?.classList.add('hidden');
    if (!instructorPicker?.contains(event.target)) instructorPickerMenu?.classList.add('hidden');
  });

  const newInstructorModal = document.getElementById('newInstructorModal');
  const newInstructorForm = document.getElementById('newInstructorForm');
  const newInstructorError = document.getElementById('newInstructorError');
  const closeNewInstructorModal = () => newInstructorModal?.classList.add('hidden');
  document.getElementById('openNewInstructorModal')?.addEventListener('click', () => {
    instructorPickerMenu.classList.add('hidden');
    newInstructorError.classList.add('hidden');
    newInstructorModal.classList.remove('hidden');
    newInstructorForm.elements.email.focus();
  });
  document.querySelectorAll('.closeNewInstructorModal').forEach((button) => button.addEventListener('click', closeNewInstructorModal));
  newInstructorModal?.addEventListener('click', (event) => { if (event.target === newInstructorModal) closeNewInstructorModal(); });
  newInstructorForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    newInstructorError.classList.add('hidden');
    const response = await fetch('{{ route('admin.courses.instructors.store') }}', {
      method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': newInstructorForm.querySelector('input[name="_token"]')?.value || document.querySelector('#newCourseForm input[name="_token"]').value }, body: new FormData(newInstructorForm),
    });
    const payload = await response.json();
    if (!response.ok) {
      newInstructorError.textContent = Object.values(payload.errors || {}).flat().join(' ') || 'Unable to create the instructor.';
      newInstructorError.classList.remove('hidden');
      return;
    }
    const instructor = payload.instructor;
    const option = document.createElement('label');
    option.className = 'instructorOption flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm font-normal text-gray-700 hover:bg-gray-50';
    option.innerHTML = `<input type="checkbox" name="instructor_ids[]" value="${instructor.id}" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"><span class="flex-1 truncate"></span><span class="truncate text-xs text-gray-400"></span>`;
    option.querySelectorAll('span')[0].textContent = instructor.name;
    option.querySelectorAll('span')[1].textContent = instructor.email;
    option.querySelector('input').addEventListener('change', updateInstructorLabel);
    document.getElementById('instructorOptions').append(option);
    updateInstructorLabel();
    newInstructorForm.reset();
    closeNewInstructorModal();
  });

  const thumbnailFile = document.getElementById('thumbnailFile');
  const thumbnailPreview = document.getElementById('thumbnailPreview');
  const removeThumbnail = document.getElementById('removeThumbnail');
  let thumbnailObjectUrl = null;
  thumbnailFile?.addEventListener('change', () => {
    const file = thumbnailFile.files?.[0];
    if (!file) return;
    if (thumbnailObjectUrl) URL.revokeObjectURL(thumbnailObjectUrl);
    thumbnailObjectUrl = URL.createObjectURL(file);
    thumbnailPreview.innerHTML = `<img src="${thumbnailObjectUrl}" alt="Selected course thumbnail" class="h-full w-full object-cover">`;
    removeThumbnail.classList.remove('hidden');
  });
  removeThumbnail?.addEventListener('click', () => {
    thumbnailFile.value = '';
    if (thumbnailObjectUrl) URL.revokeObjectURL(thumbnailObjectUrl);
    thumbnailObjectUrl = null;
    thumbnailPreview.innerHTML = '<i class="fa-regular fa-image text-xl"></i>';
    removeThumbnail.classList.add('hidden');
  });

  const courseEditor = document.getElementById('courseEditor');
  const courseDescription = document.getElementById('courseDescription');
  courseEditor.innerHTML = @json(old('description', ''));
  const courseEditorImage = document.getElementById('courseEditorImage');
  const courseEditorVideo = document.getElementById('courseEditorVideo');
  const courseEditorUrlPopover = document.getElementById('courseEditorUrlPopover');
  const courseEditorUrl = document.getElementById('courseEditorUrl');
  const courseEditorUrlApply = document.getElementById('courseEditorUrlApply');
  const courseEditorColorPopover = document.getElementById('courseEditorColorPopover');
  const courseEditorTablePopover = document.getElementById('courseEditorTablePopover');
  const courseEditorTableGrid = document.getElementById('courseEditorTableGrid');
  const courseEditorTableSize = document.getElementById('courseEditorTableSize');
  const courseEditorEmbedModal = document.getElementById('courseEditorEmbedModal');
  const courseEditorEmbedInput = document.getElementById('courseEditorEmbedInput');
  const courseEditorEmbedApply = document.getElementById('courseEditorEmbedApply');
  const courseEditorEmbedError = document.getElementById('courseEditorEmbedError');
  let editorRange = null;
  const saveEditorRange = () => {
    const selection = window.getSelection();
    if (selection.rangeCount && courseEditor.contains(selection.anchorNode)) editorRange = selection.getRangeAt(0).cloneRange();
  };
  const restoreEditorRange = () => {
    if (!editorRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(editorRange);
  };
  const runEditorCommand = (command, value = null) => {
    restoreEditorRange();
    courseEditor.focus();
    document.execCommand(command, false, value);
    saveEditorRange();
    updateEditorToolbar();
  };
  const editorUrl = (value) => {
    if (!value) return null;
    try {
      const normalized = /^[a-z][a-z\d+.-]*:/i.test(value) ? value : `https://${value}`;
      const url = new URL(normalized);
      if (!['http:', 'https:', 'mailto:', 'tel:'].includes(url.protocol)) return null;
      if (['http:', 'https:'].includes(url.protocol) && url.hostname !== 'localhost' && !url.hostname.includes('.')) return null;
      return url.href;
    } catch (_) {
      return null;
    }
  };
  const positionEditorPopover = (popover, button, width) => {
    const wasOpen = !popover.hidden;
    document.querySelectorAll('.frappe-rich-editor__popover').forEach((item) => { if (item !== popover) item.hidden = true; });
    if (wasOpen) {
      popover.hidden = true;
      return false;
    }
    popover.hidden = false;
    popover.style.left = `${Math.max(0, Math.min(button.offsetLeft, button.parentElement.clientWidth - width))}px`;
    popover.style.top = `${button.offsetTop + button.offsetHeight + 4}px`;
    return true;
  };
  const openEditorUrlPopover = (button) => {
    saveEditorRange();
    if (!positionEditorPopover(courseEditorUrlPopover, button, 248)) return;
    courseEditorUrl.placeholder = 'https://example.com';
    courseEditorUrl.value = '';
    courseEditorUrl.focus();
  };
  const closeEditorUrlPopover = () => {
    courseEditorUrlPopover.hidden = true;
  };
  const embedHosts = ['youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'codepen.io', 'codesandbox.io', 'figma.com', 'embed.figma.com', 'docs.google.com', 'drive.google.com', 'notion.so'];
  const editorEmbedUrl = (input) => {
    const documentFragment = new DOMParser().parseFromString(input, 'text/html');
    const value = documentFragment.querySelector('iframe')?.getAttribute('src') || input.trim();
    try {
      const parsed = new URL(value);
      if (!['http:', 'https:'].includes(parsed.protocol) || !embedHosts.some((host) => parsed.hostname === host || parsed.hostname.endsWith(`.${host}`))) return null;
      if ((parsed.hostname === 'youtube.com' || parsed.hostname.endsWith('.youtube.com')) && parsed.pathname === '/watch' && parsed.searchParams.get('v')) {
        const videoId = parsed.searchParams.get('v');
        const list = parsed.searchParams.get('list');
        const start = (parsed.searchParams.get('t') || parsed.searchParams.get('start') || '').replace(/s$/, '');
        parsed.href = `https://www.youtube.com/embed/${videoId}?rel=0&modestbranding=1${list ? `&list=${encodeURIComponent(list)}` : ''}${start ? `&start=${encodeURIComponent(start)}` : ''}`;
      } else if (parsed.hostname === 'youtu.be' && parsed.pathname.length > 1) {
        parsed.href = `https://www.youtube.com/embed/${parsed.pathname.slice(1).split('/')[0]}?rel=0&modestbranding=1`;
      } else if ((parsed.hostname === 'vimeo.com' || parsed.hostname === 'www.vimeo.com') && parsed.pathname.match(/\d+/)) {
        parsed.href = `https://player.vimeo.com/video/${parsed.pathname.match(/\d+/)[0]}?title=0&byline=0&portrait=0`;
      }
      return parsed.href;
    } catch (_) {
      return null;
    }
  };
  const insertEditorFrame = (url) => {
    const frame = document.createElement('iframe');
    frame.src = url;
    frame.title = 'Embedded course content';
    frame.loading = 'lazy';
    frame.allowFullscreen = true;
    runEditorCommand('insertHTML', frame.outerHTML);
  };
  const escapeEditorText = (value) => value.replace(/[&<>"']/g, (character) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
  const toggleInlineCode = () => {
    restoreEditorRange();
    const selection = window.getSelection();
    const code = selection.anchorNode?.parentElement?.closest('code');
    if (code && courseEditor.contains(code)) {
      code.replaceWith(...code.childNodes);
      courseEditor.focus();
      return;
    }
    const text = selection.toString();
    runEditorCommand('insertHTML', `<code>${text ? escapeEditorText(text) : '&#8203;'}</code>`);
  };
  const courseEditorPalette = {
    text: { red:'#dc2626', orange:'#ea580c', yellow:'#ca8a04', green:'#16a34a', teal:'#0d9488', cyan:'#06b6d4', blue:'#1579d0', indigo:'#5f46c7', purple:'#9333ea', pink:'#db2777', gray:'#6b7280' },
    highlight: { red:'#fecaca', orange:'#fed7aa', yellow:'#fef08a', green:'#bbf7d0', teal:'#99f6e4', cyan:'#a5f3fc', blue:'#bfdbfe', indigo:'#dbd5ff', purple:'#e9d5ff', pink:'#fbcfe8', gray:'#e5e7eb' },
  };
  const colorSection = (label, type, colors) => `<div class="frappe-rich-editor__palette-label${type === 'highlight' ? ' mt-4' : ''}">${label}</div><div class="frappe-rich-editor__swatches"><button type="button" class="frappe-rich-editor__swatch" data-editor-color="default" data-color-type="${type}" data-default aria-label="Default">A</button>${Object.entries(colors).map(([name, value]) => `<button type="button" class="frappe-rich-editor__swatch" data-editor-color="${value}" data-color-type="${type}" aria-label="${name[0].toUpperCase() + name.slice(1)}" style="${type === 'text' ? `color:${value}` : `background:${value};color:#1f2937`}">A</button>`).join('')}</div>`;
  courseEditorColorPopover.innerHTML = colorSection('Text Color', 'text', courseEditorPalette.text) + colorSection('Background Color', 'highlight', courseEditorPalette.highlight);
  const clearEditorColor = (type) => {
    restoreEditorRange();
    const selection = window.getSelection();
    const element = selection.anchorNode?.parentElement?.closest(type === 'text' ? 'font,[style*="color"]' : '[style*="background-color"]');
    if (!element || !courseEditor.contains(element)) return;
    if (type === 'text') element.removeAttribute('color');
    element.style.removeProperty(type === 'text' ? 'color' : 'background-color');
    if (!element.getAttribute('style') && !element.attributes.length) element.replaceWith(...element.childNodes);
  };
  const applyEditorColor = (type, value) => {
    if (value === 'default') clearEditorColor(type);
    else runEditorCommand(type === 'text' ? 'foreColor' : 'hiliteColor', value);
    courseEditorColorPopover.hidden = true;
  };
  const setTableSelection = (rows, columns) => {
    courseEditorTablePopover.dataset.rows = rows;
    courseEditorTablePopover.dataset.columns = columns;
    courseEditorTablePopover.setAttribute('aria-label', `Table size, ${rows} rows by ${columns} columns`);
    courseEditorTableSize.textContent = `${rows} × ${columns}`;
    courseEditorTableGrid.querySelectorAll('button').forEach((cell) => cell.classList.toggle('is-selected', Number(cell.dataset.row) <= rows && Number(cell.dataset.column) <= columns));
  };
  for (let row = 1; row <= 6; row++) for (let column = 1; column <= 8; column++) {
    const cell = document.createElement('button');
    cell.type = 'button';
    cell.className = 'frappe-rich-editor__table-cell';
    cell.dataset.row = row;
    cell.dataset.column = column;
    cell.setAttribute('role', 'gridcell');
    cell.setAttribute('aria-label', `${row} rows by ${column} columns`);
    cell.addEventListener('mouseenter', () => setTableSelection(row, column));
    cell.addEventListener('focus', () => setTableSelection(row, column));
    cell.addEventListener('click', () => {
      const head = `<thead><tr>${'<th><br></th>'.repeat(column)}</tr></thead>`;
      const body = `<tbody>${`<tr>${'<td><br></td>'.repeat(column)}</tr>`.repeat(Math.max(0, row - 1))}</tbody>`;
      courseEditorTablePopover.hidden = true;
      runEditorCommand('insertHTML', `<table>${head}${body}</table><p><br></p>`);
    });
    courseEditorTableGrid.appendChild(cell);
  }
  setTableSelection(3, 3);
  const updateEditorToolbar = () => {
    if (!courseEditor.contains(window.getSelection()?.anchorNode)) return;
    document.querySelectorAll('[data-editor-command]').forEach((button) => {
      const command = button.dataset.editorCommand;
      const active = command === 'formatBlock'
        ? document.queryCommandValue(command).toLowerCase() === button.dataset.editorValue
        : document.queryCommandState(command);
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
  };
  const uploadEditorFiles = async (files, type) => {
    for (const file of files) {
      const data = new FormData();
      data.append('file', file);
      data.append('folder', 'course-descriptions');
      data.append('is_public', '1');
      const response = await fetch('{{ $courseMediaUploadAction ?? route('courses.create.media') }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('#newCourseForm input[name="_token"]').value },
        body: data,
      });
      const payload = await response.json();
      if (!response.ok || !payload.url) throw new Error(Object.values(payload.errors || {}).flat().join(' ') || payload.error || 'Upload failed.');
      if (type === 'image') {
        runEditorCommand('insertImage', payload.url);
        continue;
      }
      const video = document.createElement('video');
      video.src = payload.url;
      video.controls = true;
      video.preload = 'metadata';
      runEditorCommand('insertHTML', video.outerHTML);
    }
  };
  document.querySelectorAll('[data-editor-command], [data-editor-action]').forEach((button) => {
    button.addEventListener('mousedown', (event) => event.preventDefault());
    button.addEventListener('click', () => {
      const command = button.dataset.editorCommand;
      const action = button.dataset.editorAction;
      if (action === 'link') return openEditorUrlPopover(button);
      if (action === 'color') { saveEditorRange(); positionEditorPopover(courseEditorColorPopover, button, 214); return; }
      if (action === 'image') return courseEditorImage.click();
      if (action === 'video') return courseEditorVideo.click();
      if (action === 'code') return toggleInlineCode();
      if (action === 'embed') {
        saveEditorRange();
        courseEditorEmbedInput.value = '';
        courseEditorEmbedApply.disabled = true;
        courseEditorEmbedError.classList.add('hidden');
        courseEditorEmbedModal.hidden = false;
        courseEditorEmbedInput.focus();
        return;
      }
      if (action === 'rule') return runEditorCommand('insertHTML', '<hr>');
      if (action === 'table') { saveEditorRange(); setTableSelection(3, 3); if (positionEditorPopover(courseEditorTablePopover, button, 186)) courseEditorTableGrid.querySelector('button[data-row="3"][data-column="3"]')?.focus(); return; }
      runEditorCommand(command, button.dataset.editorValue || null);
    });
  });
  document.addEventListener('selectionchange', () => { saveEditorRange(); updateEditorToolbar(); });
  courseEditorColorPopover?.addEventListener('mousedown', (event) => event.preventDefault());
  courseEditorColorPopover?.addEventListener('click', (event) => {
    const swatch = event.target.closest('[data-editor-color]');
    if (swatch) applyEditorColor(swatch.dataset.colorType, swatch.dataset.editorColor);
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.frappe-rich-editor__popover, [data-editor-action="link"], [data-editor-action="color"], [data-editor-action="table"]')) closeEditorPopovers();
  });
  courseEditorTableGrid?.addEventListener('keydown', (event) => {
    if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(event.key)) return;
    event.preventDefault();
    if (event.key === 'Enter') return document.activeElement.click();
    const row = Math.max(1, Math.min(6, Number(courseEditorTablePopover.dataset.rows) + (event.key === 'ArrowDown' ? 1 : event.key === 'ArrowUp' ? -1 : 0)));
    const column = Math.max(1, Math.min(8, Number(courseEditorTablePopover.dataset.columns) + (event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0)));
    courseEditorTableGrid.querySelector(`button[data-row="${row}"][data-column="${column}"]`)?.focus();
  });
  const applyEditorUrl = () => {
    const url = editorUrl(courseEditorUrl.value.trim());
    if (!url) {
      courseEditorUrl.setCustomValidity('Enter a valid http or https URL.');
      courseEditorUrl.reportValidity();
      return;
    }
    courseEditorUrl.setCustomValidity('');
    runEditorCommand('createLink', url);
    closeEditorUrlPopover();
  };
  courseEditorUrlApply?.addEventListener('click', applyEditorUrl);
  courseEditorUrl?.addEventListener('input', () => courseEditorUrl.setCustomValidity(''));
  courseEditorUrl?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      applyEditorUrl();
      return;
    }
    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopPropagation();
      closeEditorUrlPopover();
      restoreEditorRange();
      courseEditor.focus();
    }
  });
  const closeEmbedModal = () => { courseEditorEmbedModal.hidden = true; restoreEditorRange(); courseEditor.focus(); };
  courseEditorEmbedInput?.addEventListener('input', () => {
    const valid = Boolean(editorEmbedUrl(courseEditorEmbedInput.value));
    courseEditorEmbedApply.disabled = !valid;
    courseEditorEmbedError.classList.toggle('hidden', !courseEditorEmbedInput.value || valid);
  });
  courseEditorEmbedApply?.addEventListener('click', () => {
    const url = editorEmbedUrl(courseEditorEmbedInput.value);
    if (!url) { courseEditorEmbedError.classList.remove('hidden'); return; }
    courseEditorEmbedModal.hidden = true;
    insertEditorFrame(url);
  });
  document.getElementById('courseEditorEmbedCancel')?.addEventListener('click', closeEmbedModal);
  document.getElementById('courseEditorEmbedClose')?.addEventListener('click', closeEmbedModal);
  courseEditorEmbedModal?.addEventListener('click', (event) => { if (event.target === courseEditorEmbedModal) closeEmbedModal(); });
  courseEditorEmbedInput?.addEventListener('keydown', (event) => { if (event.key === 'Escape') { event.stopPropagation(); closeEmbedModal(); } });
  courseEditorImage?.addEventListener('change', async () => {
    try { await uploadEditorFiles([...courseEditorImage.files], 'image'); } catch (error) { window.alert(error.message); }
    courseEditorImage.value = '';
  });
  courseEditorVideo?.addEventListener('change', async () => {
    try { await uploadEditorFiles([...courseEditorVideo.files], 'video'); } catch (error) { window.alert(error.message); }
    courseEditorVideo.value = '';
  });
  document.getElementById('newCourseForm')?.addEventListener('submit', (event) => {
    courseDescription.value = courseEditor.innerHTML.trim();
    const hasInstructor = document.querySelector('input[name="instructor_ids[]"]:checked');
    if (!courseEditor.textContent.trim() || !hasInstructor) {
      event.preventDefault();
      if (!hasInstructor) instructorPickerButton.focus();
      else courseEditor.focus();
    } else {
      event.currentTarget.querySelector('button[type="submit"]').disabled = true;
    }
  });
</script>
