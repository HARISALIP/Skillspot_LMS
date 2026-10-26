<script>
(() => {
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
  const newCourseModal = document.getElementById('newCourseModal');
  const openNewCourseModal = document.getElementById('openNewCourseModal');
  let courseModalReturnFocus = null;
  const closeNewCourseModal = () => {
    window.skillspotCourseEditor?.closeUi();
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
      if (window.skillspotCourseEditor?.handleEscape()) return;
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

  document.getElementById('newCourseForm')?.addEventListener('submit', (event) => {
    const editor = window.skillspotCourseEditor;
    const hasInstructor = document.querySelector('input[name="instructor_ids[]"]:checked');
    if (!editor || !editor.hasContent() || !hasInstructor) {
      event.preventDefault();
      if (!hasInstructor) instructorPickerButton.focus();
      else if (editor) editor.focus();
      else window.alert('The course editor is still loading. Please try again.');
      return;
    }
    document.getElementById('courseDescription').value = editor.getHTML();
    event.currentTarget.querySelector('button[type="submit"]').disabled = true;
  });
})();
</script>