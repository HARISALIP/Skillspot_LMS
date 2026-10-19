/*
 * Frappe-inspired Skillspot authoring workspace.
 * EditorJS document JSON is the persisted contract.
 */
// The workspace uses runtime component templates, so it needs Vue's compiler build.
import { createApp, computed, nextTick, onBeforeUnmount, ref, watch } from 'vue/dist/vue.esm-bundler.js';
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import NestedList from '@editorjs/nested-list';
import Table from '@editorjs/table';
import Paragraph from '@editorjs/paragraph';
import SimpleImage from '@editorjs/simple-image';
import Embed from '@editorjs/embed';
import InlineCode from '@editorjs/inline-code';
import DragDrop from 'editorjs-drag-drop';
import { ArrowLeft, BookOpen, Check, ChevronRight, CircleHelp, Eye, NotebookPen, Plus, Users } from 'lucide-vue-next';
import './frappe-workspace.css';
import './frappe-course-list.css';
import './frappe-lesson-editor.css';

const root = document.querySelector('#frappe-course-workspace');
const lessonRoot = document.querySelector('#frappe-lesson-editor');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const courseId = root?.dataset.courseId;
const api = '/instructor/workspace/courses';

async function request(path, options = {}) {
  const response = await fetch(api + path, {
    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
    credentials: 'same-origin',
    ...options,
  });
  if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || 'The workspace could not save your change.');
  return response.status === 204 ? null : response.json();
}

class StructuredBlock {
  static get toolbox() { return { title: this.label, icon: this.icon }; }
  constructor({ data }) { this.data = data || {}; }
  render() {
    const node = document.createElement('div');
    node.className = 'frappe-structured-block';
    const label = document.createElement('strong');
    const input = document.createElement('input');
    label.textContent = this.constructor.label;
    input.placeholder = 'Add instructions';
    input.value = this.data.text || '';
    node.append(label, input);
    this.input = input;
    return node;
  }
  save() { return { text: this.input?.value || '' }; }
}
const structuredTool = (label, icon) => class extends StructuredBlock { static label = label; static icon = icon; };
const Assignment = structuredTool('Assignment', 'A');
const Program = structuredTool('Programming Exercise', '</>');

function openQuizPicker({ quizUrl, selectedId, onSave }) {
  const modalDocument = (() => { try { return window.top?.document || document; } catch { return document; } })();
  const backdrop = document.createElement('div');
  backdrop.className = 'frappe-quiz-modal-backdrop';
  backdrop.innerHTML = `<section class="frappe-quiz-modal" role="dialog" aria-modal="true" aria-labelledby="quizPickerTitle">
    <header><h2 id="quizPickerTitle">Add a quiz to your lesson</h2><button type="button" class="frappe-quiz-close" aria-label="Close">×</button></header>
    <div class="frappe-quiz-label"><span>Select a quiz</span><div class="frappe-quiz-select"><button type="button" class="frappe-quiz-select-button" aria-expanded="false">Select a quiz</button><div class="frappe-quiz-options" hidden><div class="frappe-quiz-option-list"></div><footer><button type="button" class="frappe-quiz-clear">Clear</button><button type="button" class="frappe-quiz-create">＋ Create New</button></footer></div></div></div>
    <p class="frappe-quiz-error" role="alert"></p>
    <footer><button type="button" class="frappe-quiz-save">Save</button></footer>
  </section>`;
  const dialog = backdrop.firstElementChild;
  const selectButton = dialog.querySelector('.frappe-quiz-select-button');
  const options = dialog.querySelector('.frappe-quiz-options');
  const optionList = dialog.querySelector('.frappe-quiz-option-list');
  const error = dialog.querySelector('.frappe-quiz-error');
  const close = () => { modalDocument.removeEventListener('keydown', onKeyDown); backdrop.remove(); };
  const onKeyDown = event => { if (event.key === 'Escape') close(); };
  let selectedQuiz = null;
  const setOpen = open => { options.hidden = !open; selectButton.setAttribute('aria-expanded', String(open)); };
  const choose = quiz => { selectedQuiz = quiz; selectButton.textContent = quiz?.title || 'Select a quiz'; error.textContent = ''; setOpen(false); };

  dialog.querySelector('.frappe-quiz-close').addEventListener('click', close);
  backdrop.addEventListener('click', event => { if (event.target === backdrop) close(); });
  selectButton.addEventListener('click', () => setOpen(options.hidden));
  dialog.querySelector('.frappe-quiz-clear').addEventListener('click', () => choose(null));
  dialog.querySelector('.frappe-quiz-create').addEventListener('click', () => { setOpen(false); window.location.assign('/admin/quizzes/create'); });
  dialog.querySelector('.frappe-quiz-save').addEventListener('click', () => {
    onSave(selectedQuiz);
    close();
  });
  modalDocument.addEventListener('keydown', onKeyDown);
  modalDocument.body.append(backdrop);
  selectButton.focus();

  fetch(quizUrl, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, credentials: 'same-origin' })
    .then(response => { if (!response.ok) throw new Error(); return response.json(); })
    .then(quizzes => {
      quizzes.forEach(quiz => {
        const option = document.createElement('button'); option.type = 'button'; option.className = 'frappe-quiz-option';
        option.textContent = `${quiz.title} · ${quiz.questions_count} questions`;
        option.addEventListener('click', () => choose(quiz));
        optionList.append(option);
      });
      choose(quizzes.find(quiz => Number(quiz.id) === Number(selectedId)) || null);
    })
    .catch(() => { error.textContent = 'Could not load quizzes. Refresh and try again.'; });
}

class QuizBlock {
  static get toolbox() { return { title: 'Quiz', icon: 'Q' }; }
  constructor({ data, config, readOnly, block }) { this.data = data || {}; this.config = config || {}; this.readOnly = readOnly; this.block = block; }
  render() {
    const wrapper = document.createElement('div'); wrapper.className = 'frappe-structured-block frappe-quiz-block';
    const label = document.createElement('strong'); label.textContent = 'Quiz';
    const selected = document.createElement('span');
    const button = document.createElement('button'); button.type = 'button';
    const redraw = () => {
      selected.textContent = this.data.quiz_id ? this.data.quiz_title || 'Selected quiz' : 'No quiz selected';
      button.textContent = this.data.quiz_id ? 'Change quiz' : 'Select quiz';
    };
    const chooseQuiz = () => openQuizPicker({
      quizUrl: this.config.quizUrl,
      selectedId: this.data.quiz_id,
      onSave: quiz => { this.data.quiz_id = quiz?.id || null; this.data.quiz_title = quiz?.title || null; redraw(); this.block?.dispatchChange(); },
    });
    button.disabled = this.readOnly;
    button.addEventListener('click', chooseQuiz);
    wrapper.append(label, selected, button); redraw();
    if (!this.readOnly && !this.data.quiz_id) queueMicrotask(chooseQuiz);
    return wrapper;
  }
  save() { return { quiz_id: this.data.quiz_id || null, quiz_title: this.data.quiz_title || null }; }
  validate(data) { return Boolean(data.quiz_id); }
}

class CodeBox {
  static get toolbox() {
    return { title: 'CodeBox', icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m8 9-3 3 3 3m8-6 3 3-3 3M13.5 5l-3 14"/></svg>' };
  }
  static get isReadOnlySupported() { return true; }
  constructor({ data, readOnly }) { this.data = data || {}; this.readOnly = readOnly; }
  render() {
    const wrapper = document.createElement('div');
    wrapper.className = 'frappe-code-box';
    const language = document.createElement('select');
    language.setAttribute('aria-label', 'Code language');
    ['plaintext', 'javascript', 'html', 'css', 'php', 'python', 'sql'].forEach(value => {
      const option = new Option(value === 'plaintext' ? 'Plain text' : value.toUpperCase(), value);
      option.selected = value === (this.data.language || 'plaintext');
      language.add(option);
    });
    const code = document.createElement('textarea');
    code.placeholder = 'Write code';
    code.spellcheck = false;
    code.value = this.data.code || '';
    code.readOnly = this.readOnly;
    language.disabled = this.readOnly;
    wrapper.append(language, code);
    this.language = language;
    this.code = code;
    return wrapper;
  }
  save() { return { code: this.code?.value || '', language: this.language?.value || 'plaintext' }; }
  validate(data) { return Boolean(data.code?.trim()); }
}

let copiedBlock = null;
const tuneIcon = {
  copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
  cut: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="6" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><path d="m8 7 10 10M8 17 18 7"/></svg>',
  paste: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 4h6v3H9z"/><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 13h6m-6 4h4"/></svg>',
};
const copyCurrentBlock = async block => {
  const saved = await block.save();
  if (saved) copiedBlock = { tool: saved.tool, data: saved.data };
};
class CopyBlockTune {
  static get isTune() { return true; }
  constructor({ block }) { this.block = block; }
  render() { return { icon: tuneIcon.copy, title: 'Copy', name: 'copy-block', onActivate: () => copyCurrentBlock(this.block) }; }
}
class CutBlockTune {
  static get isTune() { return true; }
  constructor({ api, block }) { this.api = api; this.block = block; }
  render() {
    return { icon: tuneIcon.cut, title: 'Cut', name: 'cut-block', onActivate: async () => {
      await copyCurrentBlock(this.block);
      const index = this.api.blocks.getCurrentBlockIndex();
      if (index >= 0) this.api.blocks.delete(index);
    } };
  }
}
class PasteBlockTune {
  static get isTune() { return true; }
  constructor({ api }) { this.api = api; }
  render() {
    return { icon: tuneIcon.paste, title: 'Paste', name: 'paste-block', isDisabled: copiedBlock === null, onActivate: () => {
      if (!copiedBlock) return;
      const index = this.api.blocks.getCurrentBlockIndex();
      this.api.blocks.insert(copiedBlock.tool, copiedBlock.data, {}, index < 0 ? this.api.blocks.getBlocksCount() : index + 1, true);
    } };
  }
}

class UploadBlock {
  static get toolbox() {
    return { title: 'Upload', icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 16V4m0 0-4 4m4-4 4 4M5 14v5h14v-5"/></svg>' };
  }
  static get isReadOnlySupported() { return true; }
  constructor({ data, config, readOnly, block }) {
    this.data = data || {};
    this.config = config || {};
    this.readOnly = readOnly;
    this.block = block;
  }
  render() {
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'frappe-upload-block';
    if (this.data.file_url) this.renderFile();
    else if (!this.readOnly) this.renderUploader();
    return this.wrapper;
  }
  renderUploader() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,video/*,audio/*,.pdf';
    input.hidden = true;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'frappe-upload-button';
    button.textContent = 'Upload File';
    const error = document.createElement('p');
    error.className = 'frappe-upload-error';
    button.addEventListener('click', () => input.click());
    input.addEventListener('change', () => input.files[0] && this.upload(input.files[0], button, error));
    this.wrapper.replaceChildren(button, input, error);
    queueMicrotask(() => input.click());
  }
  upload(file, button, error) {
    const extension = file.name.split('.').pop()?.toLowerCase();
    if (!['jpg', 'jpeg', 'png', 'mp4', 'mov', 'mp3', 'pdf'].includes(extension)) {
      error.textContent = 'Only JPG, PNG, MP4, MOV, MP3 and PDF files are allowed.';
      return;
    }
    const body = new FormData();
    body.append('file', file);
    body.append('course_id', String(this.config.courseId));
    body.append('is_public', '0');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/media/upload');
    xhr.responseType = 'json';
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
    xhr.upload.addEventListener('progress', event => {
      if (event.lengthComputable) button.textContent = `Uploading ${Math.round(event.loaded / event.total * 100)}%`;
    });
    xhr.addEventListener('load', () => {
      if (xhr.status < 200 || xhr.status >= 300) {
        error.textContent = xhr.response?.message || xhr.response?.error || 'The file could not be uploaded.';
        button.textContent = 'Upload File';
        return;
      }
      this.data = { file_url: xhr.response.url, file_type: xhr.response.type, file_name: xhr.response.name };
      this.renderFile();
      this.block?.dispatchChange();
    });
    xhr.addEventListener('error', () => { error.textContent = 'The upload was interrupted. Try again.'; button.textContent = 'Upload File'; });
    button.textContent = 'Uploading 0%';
    error.textContent = '';
    xhr.send(body);
  }
  renderFile() {
    let url;
    try { url = new URL(this.data.file_url, location.origin); } catch { return; }
    if (!['http:', 'https:'].includes(url.protocol)) return;
    let media;
    if (this.data.file_type === 'video') { media = document.createElement('video'); media.controls = true; }
    else if (this.data.file_type === 'audio') { media = document.createElement('audio'); media.controls = true; }
    else if (this.data.file_type === 'pdf') { media = document.createElement('iframe'); media.title = this.data.file_name || 'PDF document'; }
    else { media = document.createElement('img'); media.alt = this.data.file_name || ''; }
    media.src = url.href;
    media.className = `frappe-upload-preview frappe-upload-${this.data.file_type || 'image'}`;
    this.wrapper.replaceChildren(media);
  }
  validate(data) { return Boolean(data.file_url && data.file_type); }
  save() { return this.data; }
}

function editorTools(config = {}) {
  return {
    paragraph: { class: Paragraph, inlineToolbar: true },
    header: { class: Header, inlineToolbar: true, config: { placeholder: 'Heading', levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
    list: { class: NestedList, inlineToolbar: true },
    table: Table,
    image: { class: SimpleImage },
    embed: { class: Embed, config: { services: { youtube: true, vimeo: true, codepen: true, github: true, codesandbox: true } } },
    inlineCode: InlineCode,
    upload: { class: UploadBlock, config },
    quiz: { class: QuizBlock, config }, assignment: Assignment, program: Program, codeBox: CodeBox,
    copyBlock: CopyBlockTune, cutBlock: CutBlockTune, pasteBlock: PasteBlockTune,
  };
}

const BlockEditor = {
  props: { lesson: Object }, emits: ['save'],
  template: '<div class="block-editor"><p v-if="lesson.legacy_content" class="legacy-banner">Legacy lesson — its original content stays intact until you save this editor.</p><div :id="holderId" class="editorjs-holder"></div><p class="editor-hint">Type <kbd>/</kbd> or use the plus button to add text, tables, assessments and private file uploads.</p></div>',
  setup(props, { emit }) {
    const holderId = 'editor-' + props.lesson.id;
    let editor; let timer;
    const save = async () => emit('save', await editor.save());
    const mount = async () => {
      await nextTick(); editor?.destroy();
      editor = new EditorJS({
        holder: holderId, tools: editorTools({ courseId: Number(lessonRoot?.dataset.courseId || courseId), quizUrl: `${api}/${Number(lessonRoot?.dataset.courseId || courseId)}/quizzes` }),
        data: props.lesson.blocks || { time: Date.now(), blocks: [], version: '2.29.0' },
        autofocus: true, placeholder: 'Type / to choose a block',
        tunes: ['copyBlock', 'cutBlock', 'pasteBlock'],
        onReady: () => new DragDrop(editor),
        onChange: () => { clearTimeout(timer); timer = setTimeout(save, 700); },
      });
    };
    watch(() => props.lesson.id, mount, { immediate: true });
    onBeforeUnmount(() => { clearTimeout(timer); editor?.destroy(); });
    return { holderId };
  },
};

const LessonEditor = {
  components: { BlockEditor, ChevronRight, CircleHelp, NotebookPen },
  template: `
    <div v-if="ready" class="frappe-lesson-form">
      <div class="preview-setting">
        <button type="button" class="frappe-switch" :class="{ checked: lesson.is_preview }" role="switch" :aria-checked="String(lesson.is_preview)" aria-labelledby="preview-label" @click="togglePreview"><span></span></button>
        <span id="preview-label">Include in preview</span>
        <span class="help-icon" title="When on, anyone can preview this lesson without enrolling. Otherwise it is visible only to enrolled students."><CircleHelp :size="16" /></span>
      </div>

      <textarea ref="titleInput" v-model="lesson.title" rows="1" class="frappe-lesson-title" placeholder="Lesson title" aria-label="Lesson title" @input="titleChanged" @blur="saveMeta" @keydown.enter.prevent="focusContent"></textarea>

      <details class="instructor-notes" @toggle="notesOpen = $event.target.open">
        <summary>
          <NotebookPen :size="16" />
          <span>Instructor notes</span>
          <small>private</small>
          <ChevronRight :size="16" class="notes-chevron" />
        </summary>
        <div class="notes-editor"><BlockEditor v-if="notesOpen" :lesson="notes" @save="saveNotes" /></div>
      </details>

      <BlockEditor :lesson="lesson" @save="saveContent" />
      <p class="save-state" :class="{ error: state === 'Could not save' }" role="status" aria-live="polite">{{ state }}</p>
    </div>
    <div v-else class="lesson-loading">Loading lesson…</div>
  `,
  setup() {
    const course = Number(lessonRoot.dataset.courseId);
    const section = Number(lessonRoot.dataset.sectionId);
    const initialLesson = Number(lessonRoot.dataset.lessonId) || null;
    const ready = ref(false);
    const notesOpen = ref(false);
    const state = ref('');
    const titleInput = ref(null);
    const lesson = ref({ id: initialLesson, editorKey: `content-${initialLesson || 'draft'}`, title: '', is_preview: false, blocks: null, legacy_content: null });
    const notes = ref({ id: `notes-${initialLesson || 'draft'}`, editorKey: `notes-${initialLesson || 'draft'}`, blocks: null });
    let saveTimer;
    let stateTimer;
    let creating;

    const path = id => `/${course}/sections/${section}/lessons${id ? `/${id}` : ''}`;
    const mark = value => {
      state.value = value;
      clearTimeout(stateTimer);
      if (value === 'Saved') stateTimer = setTimeout(() => { state.value = ''; }, 1200);
    };
    const load = async () => {
      if (initialLesson) {
        const data = await request(`/${course}`);
        const found = data.sections.find(item => item.id === section)?.lessons.find(item => item.id === initialLesson);
        if (!found) throw new Error('Lesson not found.');
        Object.assign(lesson.value, found, { editorKey: `content-${initialLesson}` });
        Object.assign(notes.value, { blocks: found.instructor_notes, editorKey: `notes-${initialLesson}` });
      }
      ready.value = true;
      await nextTick();
      resizeTitle();
      if (!initialLesson) titleInput.value?.focus();
    };
    const ensureLesson = async () => {
      if (lesson.value.id) return lesson.value.id;
      if (!lesson.value.title.trim()) return null;
      creating ??= request(path(), { method: 'POST', body: JSON.stringify({ title: lesson.value.title.trim(), is_preview: lesson.value.is_preview }) });
      try {
        const created = await creating;
        lesson.value.id = created.id;
        notes.value.id = `notes-${created.id}`;
        mark('Saved');
        history.replaceState(null, '', `/admin/courses/${course}/sections/${section}/lessons/${created.id}/edit?embed=1`);
        window.parent.postMessage({ type: 'skillspot:lesson-created', sectionId: section, lessonId: created.id, title: created.title }, location.origin);
        return created.id;
      } finally {
        creating = null;
      }
    };
    const patch = async payload => {
      const id = await ensureLesson();
      if (!id) return;
      mark('Saving…');
      try {
        const saved = await request(path(id), { method: 'PATCH', body: JSON.stringify(payload) });
        lesson.value.title = saved.title;
        lesson.value.is_preview = saved.is_preview;
        mark('Saved');
        window.parent.postMessage({ type: 'skillspot:lesson-saved', lessonId: id, title: saved.title, isPreview: saved.is_preview }, location.origin);
      } catch (error) {
        mark('Could not save');
      }
    };
    const saveMeta = () => patch({ title: lesson.value.title.trim(), is_preview: lesson.value.is_preview });
    const titleChanged = () => {
      resizeTitle();
      clearTimeout(saveTimer);
      saveTimer = setTimeout(saveMeta, 650);
    };
    const togglePreview = () => { lesson.value.is_preview = !lesson.value.is_preview; saveMeta(); };
    const saveContent = blocks => { lesson.value.blocks = blocks; patch({ block_document: blocks }); };
    const saveNotes = blocks => { notes.value.blocks = blocks; patch({ instructor_notes_document: blocks }); };
    const resizeTitle = () => { if (titleInput.value) { titleInput.value.style.height = 'auto'; titleInput.value.style.height = `${titleInput.value.scrollHeight}px`; } };
    const focusContent = () => document.querySelector('.frappe-lesson-form .ce-paragraph')?.focus();

    load().catch(() => { ready.value = true; mark('Could not load lesson'); });
    onBeforeUnmount(() => { clearTimeout(saveTimer); clearTimeout(stateTimer); });
    return { ready, lesson, notes, notesOpen, state, titleInput, titleChanged, togglePreview, saveMeta, saveContent, saveNotes, focusContent };
  },
};

const App = {
  components: { BlockEditor, ArrowLeft, BookOpen, Check, Plus, Users },
  template: `
    <div class="frappe-workspace">
      <template v-if="!course">
        <header class="workspace-top"><a href="/studio" class="brand"><BookOpen :size="20"/> Skillspot Studio</a><button class="primary" @click="openCreate"><Plus :size="16"/> New Course</button></header>
        <main class="course-list"><div class="list-heading"><h1>All Courses</h1><div class="course-list-filters"><div role="group" aria-label="Course status"><button v-for="option in ['All', 'Published', 'In review', 'Drafts']" :key="option" :aria-pressed="statusFilter === option" :class="{active: statusFilter === option}" @click="statusFilter = option">{{ option }}</button></div><label class="sr-only" for="instructor-course-search">Search courses</label><input id="instructor-course-search" v-model="search" type="search" placeholder="Search"></div></div>
          <div v-if="filteredCourses.length" class="course-grid"><a v-for="item in filteredCourses" :key="item.id" :href="'/instructor/courses/'+item.id" class="course-card"><div class="course-image" :style="item.thumbnail ? {backgroundImage: 'url('+item.thumbnail+')'} : {}"><span v-if="!item.thumbnail">{{ item.title }}</span></div><div class="course-card-details"><small>{{ item.enrollments_count }} learners</small><h2 v-if="item.thumbnail">{{ item.title }}</h2><p>{{ item.short_introduction || item.category || 'Course' }}</p><footer>{{ item.marketplace_status || 'Draft' }}</footer></div></a></div><p v-else class="course-list-empty">No courses match these filters.</p><div class="course-list-footer">{{ filteredCourses.length }} of {{ courses.length }}</div></main>
      </template>
      <template v-else>
        <header class="workspace-top editor-top"><a href="/instructor/courses" class="icon-button" aria-label="Back to course list"><ArrowLeft :size="18"/></a><div class="course-title"><small>Course editor</small><strong>{{ course.title }}</strong></div><div class="top-actions"><span class="saved" v-if="saved"><Check :size="15"/> Saved</span><button class="outline-mobile secondary" aria-label="Show chapters" @click="mobileOutline = !mobileOutline"><BookOpen :size="16"/><span class="action-label">Chapters</span></button><a :href="'/instructor/courses/'+course.id+'/preview?from=instructor'" rel="noopener" class="secondary" aria-label="Preview as learner"><Eye :size="16"/><span class="action-label">Preview as learner</span></a></div></header>
        <nav class="course-tabs"><button v-for="item in ['Overview','Dashboard','Editor','Settings']" :class="{active: tab === item.toLowerCase()}" @click="tab = item.toLowerCase()">{{ item }}</button></nav>
        <main v-if="tab === 'overview'" class="tab-panel overview"><section><label>Course title<input v-model="draft.title" @change="saveCourse"></label><label>Category<input v-model="draft.category" @change="saveCourse"></label><label>Short introduction<textarea v-model="draft.short_introduction" @change="saveCourse"></textarea></label><label>Description<textarea v-model="draft.description" @change="saveCourse"></textarea></label></section><aside><h2>Instructors</h2><p v-for="teacher in course.teachers" :key="teacher.id">{{ teacher.name }}<small>{{ teacher.email }}</small></p><h2>Course image</h2><p>Set the course image URL in Settings.</p></aside></main>
        <main v-else-if="tab === 'dashboard'" class="tab-panel dashboard"><div><Users :size="21"/><strong>{{ course.enrollments_count }}</strong><span>Learners</span></div><div><BookOpen :size="21"/><strong>{{ lessonCount }}</strong><span>Lessons</span></div><div><Check :size="21"/><strong>{{ course.marketplace_status || 'Draft' }}</strong><span>Marketplace status</span></div></main>
        <main v-else-if="tab === 'settings'" class="tab-panel settings"><h1>Course settings</h1><p>Choose a price, then save. Only approved creators can submit a saved course for its first publication review.</p><label>Free course <input type="checkbox" v-model="draft.is_free" @change="saveCourse"></label><label v-if="!draft.is_free">Price in INR <input type="number" min="1" step="0.01" v-model.number="draft.price" @change="saveCourse"></label><label>Course image URL <input type="url" v-model="draft.thumbnail" @change="saveCourse"></label><p>Current status: {{ course.marketplace_status || 'Draft' }}. Return to Creator Studio to request review.</p><a class="primary" :href="'/courses/'+course.id+'/teaching'">Assignments and announcements</a></main>
        <main v-else class="editor-layout"><section class="lesson-pane"><template v-if="selectedLesson"><input class="lesson-name" v-model="selectedLesson.title" @change="saveLesson" placeholder="Lesson title"><label class="preview-toggle"><input type="checkbox" v-model="selectedLesson.is_preview" @change="saveLesson"> Preview lesson</label><div class="grid gap-2 p-3"><label>Lesson type <select v-model="selectedLesson.type" @change="saveLessonMeta"><option value="text">Reading</option><option value="video">Video</option><option value="pdf">Document</option></select></label><label v-if="selectedLesson.type === 'video'">Video URL <input v-model="selectedLesson.video_url" @change="saveLessonMeta" placeholder="https://… or upload a video below"></label><label>Duration in minutes <input type="number" min="0" max="1440" v-model.number="selectedLesson.duration" @change="saveLessonMeta"></label><label>Upload a private course file <input type="file" @change="uploadLessonFile"></label><p role="status" v-if="uploadState">{{ uploadState }}</p><ul v-if="selectedLesson.resources?.length"><li v-for="file in selectedLesson.resources" :key="file.upload_id">{{ file.title }}</li></ul></div><BlockEditor :key="selectedLesson.id" :lesson="selectedLesson" @save="saveBlocks"/></template><div v-else class="empty-editor"><BookOpen :size="28"/><h2>Choose a lesson</h2><p>Add a chapter, then a lesson, to begin authoring.</p></div></section>
          <aside class="outline" :class="{open: mobileOutline}"><div class="outline-head"><strong>Chapters</strong><button @click="newSectionOpen=true"><Plus :size="16"/> Add Chapter</button></div><form v-if="newSectionOpen" class="outline-create" @submit.prevent="addSection"><input v-model="newSectionTitle" placeholder="Chapter title" autofocus><button class="primary">Add</button></form><div v-for="section in course.sections" :key="section.id" class="chapter" @dragover.prevent @drop="dropLesson(section, section.lessons.length)"><div class="chapter-title"><input :value="section.title" @change="renameSection(section, $event.target.value)"><button @click="newLessonSection=section.id" title="Add lesson"><Plus :size="15"/></button></div><form v-if="newLessonSection===section.id" class="outline-create" @submit.prevent="addLesson(section)"><input v-model="newLessonTitle" placeholder="Lesson title" autofocus><button class="primary">Add</button></form><button v-for="(lesson, index) in section.lessons" :key="lesson.id" draggable="true" @dragstart="dragging = lesson" @dragover.prevent @drop.stop="dropLesson(section, index)" class="lesson-row" :class="{selected: selectedLesson?.id === lesson.id}" @click="selectLesson(lesson)"><span class="lesson-dot"></span>{{ lesson.title }}<small v-if="lesson.is_preview">Preview</small></button></div></aside>
        </main>
      </template>
    </div>`,
  setup() {
    const courses = ref([]); const course = ref(null); const draft = ref({}); const selectedLesson = ref(null);
    const tab = ref('overview'); const saved = ref(false); const uploadState = ref(''); const search = ref(''); const statusFilter = ref('All'); const mobileOutline = ref(false); const dragging = ref(null); const newSectionOpen = ref(false); const newSectionTitle = ref(''); const newLessonSection = ref(null); const newLessonTitle = ref(''); let saveNotice;
    const markSaved = () => { saved.value = true; clearTimeout(saveNotice); saveNotice = setTimeout(() => saved.value = false, 1800); };
    const load = async () => {
      if (courseId) { course.value = await request('/' + courseId); draft.value = { ...course.value }; selectedLesson.value = course.value.sections.flatMap(section => section.lessons)[0] || null; }
      else courses.value = await request('');
    };
    const openCreate = () => window.dispatchEvent(new Event('skillspot:new-course'));
    const filteredCourses = computed(() => courses.value.filter(item => {
      const status = item.marketplace_status || 'draft';
      return (item.title + ' ' + (item.category || '')).toLowerCase().includes(search.value.toLowerCase()) && (
        statusFilter.value === 'All' ||
        (statusFilter.value === 'Published' && status === 'live') ||
        (statusFilter.value === 'In review' && status === 'submitted') ||
        (statusFilter.value === 'Drafts' && ['draft', 'needs_changes', 'rejected'].includes(status))
      );
    }));
    const lessonCount = computed(() => course.value?.sections?.reduce((count, section) => count + section.lessons.length, 0) || 0);
    const saveCourse = async () => { Object.assign(course.value, await request('/' + course.value.id, { method: 'PATCH', body: JSON.stringify(draft.value) })); markSaved(); };
    const sectionFor = lesson => course.value.sections.find(section => section.lessons.some(item => item.id === lesson.id));
    const selectLesson = lesson => { selectedLesson.value = lesson; mobileOutline.value = false; };
    const lessonPath = () => '/' + course.value.id + '/sections/' + sectionFor(selectedLesson.value).id + '/lessons/' + selectedLesson.value.id;
    const saveLesson = async () => { Object.assign(selectedLesson.value, await request(lessonPath(), { method: 'PATCH', body: JSON.stringify({ title: selectedLesson.value.title, is_preview: selectedLesson.value.is_preview }) })); markSaved(); };
    const saveLessonMeta = async () => { Object.assign(selectedLesson.value, await request(lessonPath(), { method: 'PATCH', body: JSON.stringify({ type: selectedLesson.value.type, video_url: selectedLesson.value.video_url, duration: selectedLesson.value.duration || 0 }) })); markSaved(); };
    const uploadLessonFile = event => {
      const file = event.target.files?.[0]; if (!file || !selectedLesson.value) return;
      const lesson = selectedLesson.value; const path = lessonPath(); const body = new FormData(); body.append('file', file);
      const xhr = new XMLHttpRequest(); xhr.open('POST', `/courses/${course.value.id}/teaching/media`);
      xhr.setRequestHeader('X-CSRF-TOKEN', csrf); xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = progress => { if (progress.lengthComputable) uploadState.value = `Uploading ${Math.round(progress.loaded / progress.total * 100)}%`; };
      xhr.onload = async () => {
        try {
          const uploaded = JSON.parse(xhr.responseText);
          if (xhr.status !== 201) throw new Error(uploaded.message || 'Upload failed.');
          if (uploaded.type === 'video') { lesson.type = 'video'; lesson.video_url = uploaded.url; }
          else lesson.resources = [...(lesson.resources || []), { title: uploaded.name, kind: 'study_material', upload_id: uploaded.id }];
          Object.assign(lesson, await request(path, { method: 'PATCH', body: JSON.stringify({ type: lesson.type, video_url: lesson.video_url, resources: lesson.resources }) }));
          uploadState.value = 'Uploaded and saved'; markSaved();
        } catch (error) { uploadState.value = error.message; }
      };
      xhr.onerror = () => { uploadState.value = 'Upload failed. Check your connection and try again.'; };
      uploadState.value = 'Uploading…'; xhr.send(body); event.target.value = '';
    };
    const saveBlocks = async blocks => { selectedLesson.value.blocks = blocks; await request(lessonPath(), { method: 'PATCH', body: JSON.stringify({ block_document: blocks }) }); markSaved(); };
    const addSection = async () => { if (!newSectionTitle.value.trim()) return; course.value.sections.push(await request('/' + course.value.id + '/sections', { method: 'POST', body: JSON.stringify({ title: newSectionTitle.value }) })); newSectionTitle.value = ''; newSectionOpen.value = false; };
    const addLesson = async section => { if (!newLessonTitle.value.trim()) return; const lesson = await request('/' + course.value.id + '/sections/' + section.id + '/lessons', { method: 'POST', body: JSON.stringify({ title: newLessonTitle.value }) }); section.lessons.push(lesson); newLessonTitle.value = ''; newLessonSection.value = null; selectLesson(lesson); };
    const renameSection = async (section, title) => { if (!title.trim() || title === section.title) return; Object.assign(section, await request('/' + course.value.id + '/sections/' + section.id, { method: 'PATCH', body: JSON.stringify({ title }) })); markSaved(); };
    const dropLesson = async (target, index) => {
      if (!dragging.value) return;
      const source = sectionFor(dragging.value); const oldIndex = source.lessons.indexOf(dragging.value);
      source.lessons.splice(oldIndex, 1); target.lessons.splice(index, 0, dragging.value); dragging.value = null;
      await request('/' + course.value.id + '/outline', { method: 'POST', body: JSON.stringify({ sections: course.value.sections.map(section => ({ id: section.id, lessons: section.lessons.map(lesson => lesson.id) })) }) }); markSaved();
    };
    load().catch(error => window.alert(error.message));
    return { courses, course, draft, selectedLesson, tab, saved, uploadState, search, statusFilter, mobileOutline, dragging, newSectionOpen, newSectionTitle, newLessonSection, newLessonTitle, filteredCourses, lessonCount, openCreate, saveCourse, selectLesson, saveLesson, saveLessonMeta, uploadLessonFile, saveBlocks, addSection, addLesson, renameSection, dropLesson };
  },
};
if (root) createApp(App).mount(root);
if (lessonRoot) createApp(LessonEditor).mount(lessonRoot);
