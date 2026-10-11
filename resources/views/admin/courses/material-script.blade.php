@php($materialOwner = $lesson ?? $course)
const materialNames = @json($materialUploads ?? []);
const savedMaterials = @json(old('resources', $materialOwner->resources ?? []));
let materialRowIndex = 0;
let materialUploadsPending = 0;

function addMaterialRow(resource = {}) {
  const index = materialRowIndex++;
  const row = document.createElement('div');
  row.className = 'rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3';
  row.innerHTML = `
    <div class="flex gap-2 items-center">
      <input name="resources[${index}][title]" required maxlength="120" placeholder="Material title" class="flex-1 min-w-0 px-3 py-2 rounded-lg border border-gray-200 text-sm">
      <button type="button" class="text-red-600 text-sm font-bold" onclick="removeMaterialRow(this)">Remove</button>
    </div>
    <select name="resources[${index}][kind]" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" onchange="this.closest('.rounded-xl').querySelector('.slidePreview').classList.toggle('hidden', this.value !== 'slides');this.closest('.rounded-xl').querySelector('.voiceRecorder').classList.toggle('hidden', this.value !== 'audio')">
      <option value="slides">PowerPoint slides</option><option value="audio">Voice recording / audio</option>
      <option value="image">Image</option><option value="notes">Notes</option>
      <option value="quiz_paper">Quiz PDF</option><option value="question_paper">Question paper PDF</option>
      <option value="study_material">Other study PDF</option>
    </select>
    <div><label class="text-xs font-semibold text-gray-600">Material file</label>
      <input type="file" accept=".ppt,.pptx,.pdf,.doc,.docx,.txt,.mp3,.m4a,.wav,.ogg,.webm,.png,.jpg,.jpeg,.webp" class="block w-full text-sm mt-1" onchange="uploadMaterial(this, 'upload_id')">
      <input type="hidden" name="resources[${index}][upload_id]">
      <p class="fileStatus text-xs text-gray-500 mt-1"></p>
    </div>
    <div class="voiceRecorder hidden"><button type="button" onclick="toggleVoiceRecording(this)" class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm font-bold">🎙 Record voice</button>
      <span class="recordStatus text-xs text-gray-500 ml-2">Your browser will ask to use the microphone.</span></div>
    <div class="slidePreview hidden"><label class="text-xs font-semibold text-gray-600">PDF slide preview (lets students view slides in browser)</label>
      <input type="file" accept=".pdf,application/pdf" class="block w-full text-sm mt-1" onchange="uploadMaterial(this, 'preview_upload_id')">
      <input type="hidden" name="resources[${index}][preview_upload_id]">
      <p class="previewStatus text-xs text-gray-500 mt-1"></p>
    </div>`;
  row.querySelector('[name$="[title]"]').value = resource.title || '';
  row.querySelector('[name$="[kind]"]').value = resource.kind || 'slides';
  row.querySelector('[name$="[upload_id]"]').value = resource.upload_id || '';
  row.querySelector('[name$="[preview_upload_id]"]').value = resource.preview_upload_id || '';
  row.querySelector('.fileStatus').textContent = materialNames[resource.upload_id] || '';
  row.querySelector('.previewStatus').textContent = materialNames[resource.preview_upload_id] || '';
  row.querySelector('.slidePreview').classList.toggle('hidden', (resource.kind || 'slides') !== 'slides');
  row.querySelector('.voiceRecorder').classList.toggle('hidden', resource.kind !== 'audio');
  document.getElementById('materialRows').appendChild(row);
}

function removeMaterialRow(button) {
  const row = button.closest('.rounded-xl');
  if (row.recorder?.state === 'recording') {
    row.recorder.stop();
    materialUploadsPending--;
    document.getElementById('saveBtn').disabled = materialUploadsPending > 0;
  }
  row.stream?.getTracks().forEach(track => track.stop());
  row.remove();
}

async function toggleVoiceRecording(button) {
  const row = button.closest('.rounded-xl');
  const status = row.querySelector('.recordStatus');
  if (row.recorder?.state === 'recording') {
    row.recorder.stop();
    row.stream.getTracks().forEach(track => track.stop());
    button.textContent = '🎙 Record voice';
    status.textContent = 'Preparing recording…';
    return;
  }
  try {
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) throw new Error('Microphone recording is unavailable in this browser.');
    row.stream = await navigator.mediaDevices.getUserMedia({audio:true});
    const mime = ['audio/webm','audio/ogg','audio/mp4'].find(type => MediaRecorder.isTypeSupported(type));
    if (!mime) throw new Error('This browser cannot create a supported audio file.');
    row.recorder = new MediaRecorder(row.stream, {mimeType:mime});
    const chunks = [];
    row.recorder.ondataavailable = event => { if (event.data.size) chunks.push(event.data); };
    row.recorder.onstop = () => {
      if (row.isConnected) {
        const extension = mime === 'audio/mp4' ? 'm4a' : mime === 'audio/ogg' ? 'ogg' : 'webm';
        const file = new File([new Blob(chunks, {type:mime})], `voice-note-${Date.now()}.${extension}`, {type:mime});
        uploadMaterial(row.querySelector('input[type="file"]'), 'upload_id', file);
        materialUploadsPending--;
      }
    };
    row.recorder.start();
    materialUploadsPending++;
    document.getElementById('saveBtn').disabled = true;
    button.textContent = '■ Stop recording';
    status.textContent = 'Recording…';
  } catch (error) {
    status.textContent = error.message;
    row.stream?.getTracks().forEach(track => track.stop());
  }
}

async function uploadMaterial(input, field, recordedFile = null) {
  const file = recordedFile || input.files[0];
  if (!file) return;
  const row = input.closest('.rounded-xl');
  const status = row.querySelector(field === 'upload_id' ? '.fileStatus' : '.previewStatus');
  const hidden = row.querySelector(`[name$="[${field}]"]`);
  hidden.value = '';
  status.textContent = 'Uploading…';
  materialUploadsPending++;
  document.getElementById('saveBtn').disabled = true;
  const body = new FormData();
  body.append('file', file);
  body.append('folder', 'course-materials');
  body.append('is_public', '1');
  body.append('_token', _csrf);
  try {
    const response = await fetch(_uploadUrl, {method:'POST', headers:{Accept:'application/json'}, body});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.error || 'Upload failed');
    hidden.value = result.id;
    status.textContent = 'Ready: ' + result.name;
  } catch (error) {
    status.textContent = 'Upload failed: ' + error.message;
  } finally {
    materialUploadsPending--;
    document.getElementById('saveBtn').disabled = materialUploadsPending > 0;
  }
}

Object.values(savedMaterials).forEach(addMaterialRow);
