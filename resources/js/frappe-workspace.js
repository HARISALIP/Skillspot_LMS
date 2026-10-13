/*
 * Frappe Learning authoring workspace adapted for Laravel.
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
import { ArrowLeft, BookOpen, Check, Plus, Users } from 'lucide-vue-next';
import './frappe-workspace.css';

const root = document.querySelector('#frappe-course-workspace');
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
const Quiz = structuredTool('Quiz', 'Q');
const Assignment = structuredTool('Assignment', 'A');
const Program = structuredTool('Programming exercise', '</>');
const Media = structuredTool('Video / audio / PDF', '▶');
const InstructorNote = structuredTool('Instructor-only note', 'N');

function editorTools() {
  return {
    paragraph: { class: Paragraph, inlineToolbar: true },
    header: { class: Header, inlineToolbar: true, config: { placeholder: 'Heading' } },
    list: { class: NestedList, inlineToolbar: true },
    table: Table,
    image: { class: SimpleImage },
    embed: { class: Embed, config: { services: { youtube: true, vimeo: true, codepen: true, github: true, codesandbox: true } } },
    inlineCode: InlineCode,
    quiz: Quiz, assignment: Assignment, program: Program, media: Media, instructorNote: InstructorNote,
  };
}

const BlockEditor = {
  props: { lesson: Object }, emits: ['save'],
  template: '<div class="block-editor"><p v-if="lesson.legacy_content" class="legacy-banner">Legacy lesson — its original content stays intact until you save this editor.</p><div :id="holderId" class="editorjs-holder"></div><p class="editor-hint">Type <kbd>/</kbd> for EditorJS tools. Use the plus button for Frappe blocks: quiz, assignment, programming exercise, media and instructor notes.</p></div>',
  setup(props, { emit }) {
    const holderId = 'editor-' + props.lesson.id;
    let editor; let timer;
    const save = async () => emit('save', await editor.save());
    const mount = async () => {
      await nextTick(); editor?.destroy();
      editor = new EditorJS({
        holder: holderId, tools: editorTools(),
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

const App = {
  components: { BlockEditor, ArrowLeft, BookOpen, Check, Plus, Users },
  template: `
    <div class="frappe-workspace">
      <template v-if="!course">
        <header class="workspace-top"><a href="/admin" class="brand"><BookOpen :size="20"/> Skillspot Learning</a><button class="primary" @click="createOpen = true"><Plus :size="16"/> New Course</button></header>
        <main class="course-list"><div class="list-heading"><div><h1>Courses</h1><p>Create, import and manage your learning content.</p></div><input v-model="search" placeholder="Search courses"></div>
          <div class="course-grid"><button class="new-course-card" @click="createOpen = true"><Plus :size="22"/><strong>Create a new course</strong><span>Start with Frappe's guided course form.</span></button>
            <a v-for="item in filteredCourses" :key="item.id" :href="'/instructor/courses/'+item.id" class="course-card"><div class="course-image" :style="item.thumbnail ? {backgroundImage: 'url('+item.thumbnail+')'} : {}"><span v-if="!item.thumbnail">{{ item.category || 'Course' }}</span></div><div><small>{{ item.category || 'General' }}</small><h2>{{ item.title }}</h2><p>{{ item.short_introduction || 'No introduction yet.' }}</p><footer>{{ item.enrollments_count }} learners · {{ item.marketplace_status || 'Draft' }}</footer></div></a>
          </div></main><div v-if="createOpen" class="course-modal"><form @submit.prevent="createCourse"><header><h2>New Course</h2><button type="button" @click="createOpen=false">×</button></header><label>Course title<input v-model="createDraft.title" required autofocus></label><label>Category<input v-model="createDraft.category" placeholder="General"></label><label>Short introduction<textarea v-model="createDraft.short_introduction" placeholder="What will students learn?"></textarea></label><footer><button type="button" class="secondary" @click="createOpen=false">Cancel</button><button class="primary">Create Course</button></footer></form></div>
      </template>
      <template v-else>
        <header class="workspace-top editor-top"><a href="/instructor/courses" class="icon-button"><ArrowLeft :size="18"/></a><div class="course-title"><small>Course editor</small><strong>{{ course.title }}</strong></div><div class="top-actions"><span class="saved" v-if="saved"><Check :size="15"/> Saved</span><button class="outline-mobile secondary" @click="mobileOutline = !mobileOutline"><BookOpen :size="16"/> Chapters</button><a :href="'/course/'+course.slug" target="_blank" class="secondary">Student View</a></div></header>
        <nav class="course-tabs"><button v-for="item in ['Overview','Dashboard','Editor','Settings']" :class="{active: tab === item.toLowerCase()}" @click="tab = item.toLowerCase()">{{ item }}</button></nav>
        <main v-if="tab === 'overview'" class="tab-panel overview"><section><label>Course title<input v-model="draft.title" @change="saveCourse"></label><label>Category<input v-model="draft.category" @change="saveCourse"></label><label>Short introduction<textarea v-model="draft.short_introduction" @change="saveCourse"></textarea></label><label>Description<textarea v-model="draft.description" @change="saveCourse"></textarea></label></section><aside><h2>Instructors</h2><p v-for="teacher in course.teachers" :key="teacher.id">{{ teacher.name }}<small>{{ teacher.email }}</small></p><h2>Course image</h2><p>Add thumbnails through the existing secure Media workspace. This editor retains its URL.</p></aside></main>
        <main v-else-if="tab === 'dashboard'" class="tab-panel dashboard"><div><Users :size="21"/><strong>{{ course.enrollments_count }}</strong><span>Learners</span></div><div><BookOpen :size="21"/><strong>{{ lessonCount }}</strong><span>Lessons</span></div><div><Check :size="21"/><strong>{{ course.marketplace_status || 'Draft' }}</strong><span>Marketplace status</span></div></main>
        <main v-else-if="tab === 'settings'" class="tab-panel settings"><h1>Course settings</h1><p>Pricing, approval, publication, certificates, SCORM and marketplace controls remain in the existing Skillspot settings during local UI work.</p><a class="primary" :href="'/admin/courses/'+course.id+'/edit#settings'">Open marketplace settings</a></main>
        <main v-else class="editor-layout"><section class="lesson-pane"><template v-if="selectedLesson"><input class="lesson-name" v-model="selectedLesson.title" @change="saveLesson" placeholder="Lesson title"><label class="preview-toggle"><input type="checkbox" v-model="selectedLesson.is_preview" @change="saveLesson"> Preview lesson</label><BlockEditor :key="selectedLesson.id" :lesson="selectedLesson" @save="saveBlocks"/></template><div v-else class="empty-editor"><BookOpen :size="28"/><h2>Choose a lesson</h2><p>Add a chapter, then a lesson, to begin authoring.</p></div></section>
          <aside class="outline" :class="{open: mobileOutline}"><div class="outline-head"><strong>Chapters</strong><button @click="newSectionOpen=true"><Plus :size="16"/> Add Chapter</button></div><form v-if="newSectionOpen" class="outline-create" @submit.prevent="addSection"><input v-model="newSectionTitle" placeholder="Chapter title" autofocus><button class="primary">Add</button></form><div v-for="section in course.sections" :key="section.id" class="chapter" @dragover.prevent @drop="dropLesson(section, section.lessons.length)"><div class="chapter-title"><input :value="section.title" @change="renameSection(section, $event.target.value)"><button @click="newLessonSection=section.id" title="Add lesson"><Plus :size="15"/></button></div><form v-if="newLessonSection===section.id" class="outline-create" @submit.prevent="addLesson(section)"><input v-model="newLessonTitle" placeholder="Lesson title" autofocus><button class="primary">Add</button></form><button v-for="(lesson, index) in section.lessons" :key="lesson.id" draggable="true" @dragstart="dragging = lesson" @dragover.prevent @drop.stop="dropLesson(section, index)" class="lesson-row" :class="{selected: selectedLesson?.id === lesson.id}" @click="selectLesson(lesson)"><span class="lesson-dot"></span>{{ lesson.title }}<small v-if="lesson.is_preview">Preview</small></button></div></aside>
        </main>
      </template>
    </div>`,
  setup() {
    const courses = ref([]); const course = ref(null); const draft = ref({}); const selectedLesson = ref(null);
    const tab = ref('overview'); const saved = ref(false); const search = ref(''); const mobileOutline = ref(false); const dragging = ref(null); const createOpen = ref(false); const createDraft = ref({ title: '', category: '', short_introduction: '' }); const newSectionOpen = ref(false); const newSectionTitle = ref(''); const newLessonSection = ref(null); const newLessonTitle = ref(''); let saveNotice;
    const markSaved = () => { saved.value = true; clearTimeout(saveNotice); saveNotice = setTimeout(() => saved.value = false, 1800); };
    const load = async () => {
      if (courseId) { course.value = await request('/' + courseId); draft.value = { ...course.value }; selectedLesson.value = course.value.sections.flatMap(section => section.lessons)[0] || null; }
      else courses.value = await request('');
    };
    const createCourse = async () => { if (!createDraft.value.title.trim()) return; const item = await request('', { method: 'POST', body: JSON.stringify(createDraft.value) }); window.location.assign('/instructor/courses/' + item.id); };
    const filteredCourses = computed(() => courses.value.filter(item => (item.title + ' ' + (item.category || '')).toLowerCase().includes(search.value.toLowerCase())));
    const lessonCount = computed(() => course.value?.sections?.reduce((count, section) => count + section.lessons.length, 0) || 0);
    const saveCourse = async () => { Object.assign(course.value, await request('/' + course.value.id, { method: 'PATCH', body: JSON.stringify(draft.value) })); markSaved(); };
    const sectionFor = lesson => course.value.sections.find(section => section.lessons.some(item => item.id === lesson.id));
    const selectLesson = lesson => { selectedLesson.value = lesson; mobileOutline.value = false; };
    const lessonPath = () => '/' + course.value.id + '/sections/' + sectionFor(selectedLesson.value).id + '/lessons/' + selectedLesson.value.id;
    const saveLesson = async () => { Object.assign(selectedLesson.value, await request(lessonPath(), { method: 'PATCH', body: JSON.stringify({ title: selectedLesson.value.title, is_preview: selectedLesson.value.is_preview }) })); markSaved(); };
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
    return { courses, course, draft, selectedLesson, tab, saved, search, mobileOutline, dragging, createOpen, createDraft, newSectionOpen, newSectionTitle, newLessonSection, newLessonTitle, filteredCourses, lessonCount, createCourse, saveCourse, selectLesson, saveLesson, saveBlocks, addSection, addLesson, renameSection, dropLesson };
  },
};
if (root) createApp(App).mount(root);
