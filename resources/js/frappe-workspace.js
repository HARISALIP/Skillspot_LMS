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
import './frappe-lesson-editor.css';

const root = document.querySelector('#frappe-course-workspace');
const lessonRoot = document.querySelector('#frappe-lesson-editor');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const courseId = root?.dataset.courseId;
const api = '/instructor/workspace/courses';

async function request(path, options = {}) {
  let response;
  try {
    response = await fetch(api + path, {
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
      credentials: 'same-origin',
      ...options,
    });
  } catch {
    throw new Error('Connection lost. Your change was not saved. Reconnect and retry.');
  }
  if (!response.ok) {
    const details = await response.json().catch(() => ({}));
    if (response.status === 403) throw new Error('Your editing access has changed. Reload the page or ask an administrator for access.');
    if (response.status === 419) throw new Error('Your session expired. Reload the page, then try again.');
    if (response.status === 422) throw new Error(Object.values(details.errors || {})[0]?.[0] || 'Check the entered values and try again.');
    throw new Error('The change could not be saved. Check your connection and try again.');
  }
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
const Quiz = structuredTool('Quiz', 'Q');
const Assignment = structuredTool('Assignment', 'A');
const Program = structuredTool('Programming exercise', '</>');

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
    header: { class: Header, inlineToolbar: true, config: { placeholder: 'Heading' } },
    list: { class: NestedList, inlineToolbar: true },
    table: Table,
    image: { class: SimpleImage },
    embed: { class: Embed, config: { services: { youtube: true, vimeo: true, codepen: true, github: true, codesandbox: true } } },
    inlineCode: InlineCode,
    upload: { class: UploadBlock, config },
    quiz: Quiz, assignment: Assignment, program: Program,
  };
}

const BlockEditor = {
  props: { lesson: Object }, emits: ['save'],
  template: '<div class="block-editor"><p v-if="lesson.legacy_content" class="legacy-banner">Legacy lesson — its original content stays intact until you save this editor.</p><div :id="holderId" class="editorjs-holder"></div><p class="editor-hint">Type <kbd>/</kbd> or use the plus button to add text, tables, assessments and private file uploads.</p></div>',
  setup(props, { emit }) {
    const holderId = 'editor-' + props.lesson.id;
    let editor; let timer;
    const save = async () => { const current = editor; const lessonId = props.lesson.id; emit('save', lessonId, await current.save()); };
    const mount = async () => {
      await nextTick(); editor?.destroy();
      editor = new EditorJS({
        holder: holderId, tools: editorTools({ courseId: Number(lessonRoot?.dataset.courseId || courseId) }),
        data: props.lesson.blocks || { time: Date.now(), blocks: [], version: '2.29.0' },
        autofocus: true, placeholder: 'Type / to choose a block',
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
  components: { BlockEditor, ArrowLeft, BookOpen, Check, Eye, Plus, Users },
  template: `
    <div class="frappe-workspace">
      <main v-if="loading" class="course-list" role="status" aria-live="polite"><div class="workspace-loading-line"></div><div class="workspace-loading-line"></div><span class="sr-only">Loading course workspace</span></main>
      <main v-else-if="loadError" class="course-list"><div class="workspace-feedback error" role="alert"><span>{{ loadError }}</span><button type="button" class="secondary" @click="load">Retry</button></div></main>
      <template v-else-if="!course">
        <header class="workspace-top"><a href="/studio" class="brand"><BookOpen :size="20"/> Skillspot Studio</a><button class="primary" @click="createOpen = true"><Plus :size="16"/> New Course</button></header>
        <main class="course-list"><div class="list-heading"><div><h1>Courses</h1><p>Create and manage your learning content.</p></div><input v-model="search" placeholder="Search courses"></div>
          <div class="course-grid"><button class="new-course-card" @click="createOpen = true"><Plus :size="22"/><strong>Create a new course</strong><span>Start with Skillspot's guided course form.</span></button>
            <a v-for="item in filteredCourses" :key="item.id" :href="'/instructor/courses/'+item.id" class="course-card"><div class="course-image"><span>{{ item.category || 'Course' }}</span><img v-if="item.thumbnail" :src="item.thumbnail" alt="" @error="$event.target.remove()"></div><div><small>{{ item.category || 'General' }}</small><h2>{{ item.title }}</h2><p>{{ item.short_introduction || 'No introduction yet.' }}</p><footer>{{ item.enrollments_count }} learners · {{ item.marketplace_status || 'Draft' }}</footer></div></a>
          </div><p v-if="search && !filteredCourses.length" class="workspace-empty" role="status">No courses match “{{ search }}”. <button type="button" @click="search = ''">Clear search</button></p></main><dialog v-if="createOpen" ref="createModal" class="course-modal" aria-label="New course" @close="createOpen=false"><form @submit.prevent="createCourse"><header><h2>New Course</h2><button type="button" aria-label="Close new course" @click="createOpen=false">×</button></header><p v-if="createError" class="workspace-feedback error" role="alert">{{ createError }}</p><label>Course title <span aria-hidden="true">*</span><input v-model="createDraft.title" required autofocus></label><label>Category<input v-model="createDraft.category" placeholder="General"></label><label>Short introduction<textarea v-model="createDraft.short_introduction" placeholder="What will students learn?"></textarea></label><footer><button type="button" class="secondary" @click="createOpen=false">Cancel</button><button class="primary" :disabled="creating">{{ creating ? 'Creating…' : 'Create Course' }}</button></footer></form></dialog>
      </template>
      <template v-else>
        <header class="workspace-top editor-top"><a href="/instructor/courses" class="icon-button" aria-label="Back to course list"><ArrowLeft :size="18"/></a><div class="course-title"><small>Course editor</small><strong>{{ course.title }}</strong></div><div class="top-actions"><button v-if="tab === 'editor'" ref="outlineButton" class="outline-mobile secondary" aria-label="Show chapters" :aria-expanded="mobileOutline" @click="mobileOutline = !mobileOutline"><BookOpen :size="16"/><span class="action-label">Chapters</span></button><a :href="'/instructor/courses/'+course.id+'/preview?from=instructor'" target="_blank" rel="noopener" class="secondary" aria-label="Preview as learner"><Eye :size="16"/><span class="action-label">Preview as learner</span></a></div></header>
        <nav class="course-tabs" aria-label="Course editor sections"><button v-for="item in ['Overview','Dashboard','Editor','Settings']" :class="{active: tab === item.toLowerCase()}" :aria-current="tab === item.toLowerCase() ? 'page' : null" @click="tab = item.toLowerCase(); mobileOutline = false">{{ item }}</button></nav>
        <div class="workspace-feedback" :class="{error: saveError}" :role="saveError ? 'alert' : 'status'" aria-live="polite"><span>{{ saveError || saveStatus }}</span><button v-if="saveError" type="button" class="secondary" @click="retrySave">Retry save</button></div>
        <main v-if="tab === 'overview'" class="tab-panel overview"><section><label>Course title<input v-model="draft.title" @change="saveCourse"></label><label>Category<input v-model="draft.category" @change="saveCourse"></label><label>Short introduction<textarea v-model="draft.short_introduction" @change="saveCourse"></textarea></label><label>Description<textarea v-model="draft.description" @change="saveCourse"></textarea></label></section><aside><h2>Instructors</h2><p v-for="teacher in course.teachers" :key="teacher.id">{{ teacher.name }}<small>{{ teacher.email }}</small></p><h2>Course image</h2><p>Set the course image URL in Settings.</p></aside></main>
        <main v-else-if="tab === 'dashboard'" class="tab-panel dashboard"><div><Users :size="21"/><strong>{{ course.enrollments_count }}</strong><span>Learners</span></div><div><BookOpen :size="21"/><strong>{{ lessonCount }}</strong><span>Lessons</span></div><div><Check :size="21"/><strong>{{ course.marketplace_status || 'Draft' }}</strong><span>Marketplace status</span></div></main>
        <main v-else-if="tab === 'settings'" class="tab-panel settings"><h1>Course settings</h1><p>Marketplace publication requires separate review. Organization publication is controlled below.</p><label>Free course <input type="checkbox" v-model="draft.is_free" @change="saveCourse"></label><label v-if="!draft.is_free">Price in INR <input type="number" min="1" step="0.01" v-model.number="draft.price" @change="saveCourse"></label><label>Course image URL <input type="url" v-model="draft.thumbnail" @change="saveCourse"></label><section v-if="course.vendor_id"><h2>Organization access</h2><label>Publish in organization <input type="checkbox" v-model="draft.organization_published" @change="saveCourse"></label><label>Allow enrollment <input type="checkbox" v-model="draft.registration_open" @change="saveCourse"></label><label>Audience <select v-model="draft.organization_audience" @change="saveCourse"><option value="all">All organization students</option><option value="classes">Selected classes</option></select></label><div v-if="draft.organization_audience === 'classes'"><p>Select one or more classes:</p><label v-for="item in course.organization_classes" :key="item.id"><input type="checkbox" :value="item.id" v-model="draft.class_ids" @change="saveCourse"> {{ item.name }}</label><a v-if="!course.organization_classes?.length" href="/vendor/organization">Create a class first</a></div></section><p>Marketplace status: {{ course.marketplace_status || 'Draft' }}.</p><a class="primary" :href="'/courses/'+course.id+'/teaching'">Assignments and announcements</a></main>
        <main v-else class="editor-layout"><section class="lesson-pane"><template v-if="selectedLesson"><input class="lesson-name" v-model="selectedLesson.title" @change="saveLesson" placeholder="Lesson title"><label class="preview-toggle"><input type="checkbox" v-model="selectedLesson.is_preview" @change="saveLesson"> Preview lesson</label><div v-if="!['text','video','pdf'].includes(selectedLesson.type)" class="p-4"><p>This lesson uses specialist tools.</p><a :href="selectedLesson.specialist_url || '/courses/'+course.id+'/teaching'">Open specialist editor →</a></div><template v-else><div class="grid gap-2 p-3"><label>Lesson type <select v-model="selectedLesson.type" @change="saveLessonMeta"><option value="text">Reading</option><option value="video">Video</option><option value="pdf">Document</option></select></label><label v-if="selectedLesson.type === 'video'">Video URL <input v-model="selectedLesson.video_url" @change="saveLessonMeta" placeholder="https://… or upload a video below"></label><label>Duration in minutes <input type="number" min="0" max="1440" v-model.number="selectedLesson.duration" @change="saveLessonMeta"></label><label>Upload a private course file <input type="file" @change="uploadLessonFile"></label><p role="status" v-if="uploadState">{{ uploadState }}</p><ul v-if="selectedLesson.resources?.length"><li v-for="file in selectedLesson.resources" :key="file.upload_id">{{ file.title }}</li></ul></div><BlockEditor :key="selectedLesson.id" :lesson="selectedLesson" @save="saveBlocks"/></template></template><div v-else class="empty-editor"><BookOpen :size="28"/><h2>Choose a lesson</h2><p>Add a chapter, then a lesson, to begin authoring.</p></div></section>
          <aside class="outline" :class="{open: mobileOutline}" aria-label="Chapters" @keydown.esc="mobileOutline=false"><div class="outline-head"><strong>Chapters</strong><button type="button" ref="outlineClose" class="outline-close" aria-label="Close chapters" @click="mobileOutline=false">×</button><button @click="newSectionOpen=true"><Plus :size="16"/> Add Chapter</button></div><form v-if="newSectionOpen" class="outline-create" @submit.prevent="addSection"><input v-model="newSectionTitle" placeholder="Chapter title" aria-label="Chapter title" :disabled="addingSection" autofocus><button class="primary" :disabled="addingSection">{{ addingSection ? 'Adding…' : 'Add' }}</button></form><div v-for="section in course.sections" :key="section.id" class="chapter" @dragover.prevent @drop="dropLesson(section, section.lessons.length)"><div class="chapter-title"><input :value="section.title" @change="renameSection(section, $event.target.value)"><button @click="newLessonSection=section.id" title="Add lesson"><Plus :size="15"/></button></div><a v-if="section.specialist_create_url" :href="section.specialist_create_url" class="text-sm">Add live or graded lesson →</a><form v-if="newLessonSection===section.id" class="outline-create" @submit.prevent="addLesson(section)"><input v-model="newLessonTitle" placeholder="Lesson title" aria-label="Lesson title" :disabled="addingLesson" autofocus><button class="primary" :disabled="addingLesson">{{ addingLesson ? 'Adding…' : 'Add' }}</button></form><button v-for="(lesson, index) in section.lessons" :key="lesson.id" draggable="true" @dragstart="dragging = lesson" @dragover.prevent @drop.stop="dropLesson(section, index)" class="lesson-row" :class="{selected: selectedLesson?.id === lesson.id}" @click="selectLesson(lesson)"><span class="lesson-dot"></span>{{ lesson.title }}<small v-if="lesson.is_preview">Preview</small></button></div></aside>
        </main>
      </template>
    </div>`,
  setup() {
    const courses = ref([]); const course = ref(null); const draft = ref({}); const selectedLesson = ref(null);
    const tab = ref('overview'); const loading = ref(true); const loadError = ref(''); const saveStatus = ref('Changes save automatically'); const saveError = ref(''); const uploadState = ref(''); const search = ref(''); const mobileOutline = ref(false); const outlineButton = ref(null); const outlineClose = ref(null); const dragging = ref(null); const createOpen = ref(false); const createModal = ref(null); const creating = ref(false); const createError = ref(''); const createDraft = ref({ title: '', category: '', short_introduction: '', vendor_id: root?.dataset.vendorId || null }); const newSectionOpen = ref(false); const newSectionTitle = ref(''); const addingSection = ref(false); const newLessonSection = ref(null); const newLessonTitle = ref(''); const addingLesson = ref(false);
    watch(createOpen, async open => { if (open) { await nextTick(); createModal.value?.showModal(); } });
    watch(mobileOutline, async open => { await nextTick(); (open ? outlineClose.value : outlineButton.value)?.focus(); });
    let courseSaving = false; let courseSaveRequested = false; let lastFailedSave = null;
    const blockSaving = new Set(); const blockSaveRequested = new Set();
    const markSaved = () => { saveStatus.value = 'Saved'; saveError.value = ''; };
    const saveAction = async (work, retry) => {
      saveStatus.value = 'Saving…'; saveError.value = '';
      try { const result = await work(); markSaved(); lastFailedSave = null; return result; }
      catch (error) { saveStatus.value = ''; saveError.value = error.message || 'Your change was not saved.'; lastFailedSave = retry; return null; }
    };
    const retrySave = () => lastFailedSave?.();
    const load = async () => {
      loading.value = true; loadError.value = '';
      try {
        if (courseId) { course.value = await request('/' + courseId); draft.value = { ...course.value }; selectedLesson.value = course.value.sections.flatMap(section => section.lessons)[0] || null; }
        else courses.value = await request('');
      } catch (error) { loadError.value = error.message; }
      finally { loading.value = false; }
    };
    const createCourse = async () => {
      if (creating.value) return;
      if (!createDraft.value.title.trim()) { createError.value = 'Enter a course title.'; return; }
      creating.value = true; createError.value = '';
      try { const item = await request('', { method: 'POST', body: JSON.stringify(createDraft.value) }); window.location.assign('/instructor/courses/' + item.id); }
      catch (error) { createError.value = error.message; creating.value = false; }
    };
    const filteredCourses = computed(() => courses.value.filter(item => (item.title + ' ' + (item.category || '')).toLowerCase().includes(search.value.toLowerCase())));
    const lessonCount = computed(() => course.value?.sections?.reduce((count, section) => count + section.lessons.length, 0) || 0);
    const saveCourse = async () => {
      courseSaveRequested = true;
      if (courseSaving) return;
      courseSaving = true;
      try {
        while (courseSaveRequested) {
          courseSaveRequested = false;
          const fields = ['title','category','short_introduction','description','thumbnail','price','is_free'];
          if (course.value.vendor_id) fields.push('organization_published','organization_audience','registration_open','class_ids');
          const data = Object.fromEntries(fields.map(key => [key, draft.value[key]]));
          if (data.organization_audience === 'classes' && !data.class_ids?.length) {
            saveStatus.value = ''; saveError.value = 'Select at least one class before saving.'; lastFailedSave = saveCourse; break;
          }
          const updated = await saveAction(() => request('/' + course.value.id, { method: 'PATCH', body: JSON.stringify(data) }), saveCourse);
          if (!updated) break;
          Object.assign(course.value, updated);
        }
      } finally { courseSaving = false; }
    };
    const sectionFor = lesson => course.value.sections.find(section => section.lessons.some(item => item.id === lesson.id));
    const selectLesson = lesson => { selectedLesson.value = lesson; mobileOutline.value = false; };
    const lessonPath = () => '/' + course.value.id + '/sections/' + sectionFor(selectedLesson.value).id + '/lessons/' + selectedLesson.value.id;
    const saveLesson = async () => { const lesson = selectedLesson.value; const path = lessonPath(); const save = async () => { const updated = await saveAction(() => request(path, { method: 'PATCH', body: JSON.stringify({ title: lesson.title, is_preview: lesson.is_preview }) }), save); if (updated) Object.assign(lesson, updated); }; await save(); };
    const saveLessonMeta = async () => { const lesson = selectedLesson.value; const path = lessonPath(); const save = async () => { const updated = await saveAction(() => request(path, { method: 'PATCH', body: JSON.stringify({ type: lesson.type, video_url: lesson.video_url, duration: lesson.duration || 0 }) }), save); if (updated) Object.assign(lesson, updated); }; await save(); };
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
          const saveUpload = async () => {
            const saved = await saveAction(() => request(path, { method: 'PATCH', body: JSON.stringify({ type: lesson.type, video_url: lesson.video_url, resources: lesson.resources }) }), saveUpload);
            if (saved) { Object.assign(lesson, saved); uploadState.value = 'Uploaded and saved'; }
            else uploadState.value = 'File uploaded, but it could not be attached to this lesson. Use Retry save above.';
          };
          await saveUpload();
        } catch (error) { uploadState.value = error.message; }
      };
      xhr.onerror = () => { uploadState.value = 'Upload failed. Check your connection and try again.'; };
      uploadState.value = 'Uploading…'; xhr.send(body); event.target.value = '';
    };
    const saveBlocks = async (lessonId, blocks) => {
      const lesson = course.value.sections.flatMap(section => section.lessons).find(item => item.id === lessonId);
      if (!lesson) return;
      lesson.blocks = blocks; blockSaveRequested.add(lessonId);
      if (blockSaving.has(lessonId)) return;
      blockSaving.add(lessonId);
      try {
        while (blockSaveRequested.delete(lessonId)) {
          const section = sectionFor(lesson);
          const path = '/' + course.value.id + '/sections/' + section.id + '/lessons/' + lessonId;
          const saved = await saveAction(() => request(path, { method: 'PATCH', body: JSON.stringify({ block_document: lesson.blocks }) }), () => saveBlocks(lessonId, lesson.blocks));
          if (!saved) break;
        }
      } finally { blockSaving.delete(lessonId); }
    };
    const addSection = async () => {
      if (addingSection.value) return;
      const title = newSectionTitle.value.trim();
      if (!title) { saveError.value = 'Enter a chapter title.'; return; }
      addingSection.value = true;
      try {
        const section = await saveAction(() => request('/' + course.value.id + '/sections', { method: 'POST', body: JSON.stringify({ title }) }), addSection);
        if (section) { course.value.sections.push(section); newSectionTitle.value = ''; newSectionOpen.value = false; }
      } finally { addingSection.value = false; }
    };
    const addLesson = async section => {
      if (addingLesson.value) return;
      const title = newLessonTitle.value.trim();
      if (!title) { saveError.value = 'Enter a lesson title.'; return; }
      addingLesson.value = true;
      try {
        const lesson = await saveAction(() => request('/' + course.value.id + '/sections/' + section.id + '/lessons', { method: 'POST', body: JSON.stringify({ title }) }), () => addLesson(section));
        if (lesson) { section.lessons.push(lesson); newLessonTitle.value = ''; newLessonSection.value = null; selectLesson(lesson); }
      } finally { addingLesson.value = false; }
    };
    const renameSection = async (section, title) => { if (!title.trim() || title === section.title) return; const updated = await saveAction(() => request('/' + course.value.id + '/sections/' + section.id, { method: 'PATCH', body: JSON.stringify({ title }) }), () => renameSection(section, title)); if (updated) Object.assign(section, updated); };
    const saveOutline = () => saveAction(() => request('/' + course.value.id + '/outline', { method: 'POST', body: JSON.stringify({ sections: course.value.sections.map(section => ({ id: section.id, lessons: section.lessons.map(lesson => lesson.id) })) }) }), saveOutline);
    const dropLesson = async (target, index) => {
      if (!dragging.value) return;
      const source = sectionFor(dragging.value); const oldIndex = source.lessons.indexOf(dragging.value);
      source.lessons.splice(oldIndex, 1); target.lessons.splice(index, 0, dragging.value); dragging.value = null;
      await saveOutline();
    };
    const warnOnLeave = event => {
      if (saveError.value || saveStatus.value === 'Saving…' || creating.value || (createOpen.value && createDraft.value.title.trim())) {
        event.preventDefault(); event.returnValue = '';
      }
    };
    window.addEventListener('beforeunload', warnOnLeave);
    onBeforeUnmount(() => window.removeEventListener('beforeunload', warnOnLeave));
    load();
    return { courses, course, draft, selectedLesson, tab, loading, loadError, load, saveStatus, saveError, retrySave, uploadState, search, mobileOutline, outlineButton, outlineClose, dragging, createOpen, createModal, creating, createError, createDraft, newSectionOpen, newSectionTitle, addingSection, newLessonSection, newLessonTitle, addingLesson, filteredCourses, lessonCount, createCourse, saveCourse, selectLesson, saveLesson, saveLessonMeta, uploadLessonFile, saveBlocks, addSection, addLesson, renameSection, dropLesson };
  },
};
if (root) createApp(App).mount(root);
if (lessonRoot) createApp(LessonEditor).mount(lessonRoot);
