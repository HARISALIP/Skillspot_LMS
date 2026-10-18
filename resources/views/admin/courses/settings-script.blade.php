<script>
(() => {
  const thumbnail = document.getElementById('settingsThumbnailPreview');
  const thumbnailFile = document.getElementById('settingsThumbnailFile');
  const thumbnailValue = document.getElementById('settingsThumbnailValue');
  const thumbnailRemoved = document.getElementById('removeThumbnailInput');
  const color = document.getElementById('settingsThumbnailColor');
  const colorInput = color.querySelector('input');
  let thumbnailObjectUrl;
  const showThumbnail = (src) => {
    thumbnail.replaceChildren();
    if (src) {
      const image = document.createElement('img');
      image.src = src;
      image.alt = '';
      image.className = 'h-full w-full object-cover';
      thumbnail.append(image);
      color.classList.add('hidden');
    } else {
      thumbnail.innerHTML = '<i class="fa-regular fa-image text-gray-400"></i>';
      color.classList.remove('hidden');
      thumbnail.style.backgroundColor = colorInput.value;
    }
  };
  thumbnailFile.addEventListener('change', () => {
    if (thumbnailObjectUrl) URL.revokeObjectURL(thumbnailObjectUrl);
    thumbnailObjectUrl = thumbnailFile.files[0] ? URL.createObjectURL(thumbnailFile.files[0]) : null;
    if (thumbnailObjectUrl) { thumbnailRemoved.value = '0'; showThumbnail(thumbnailObjectUrl); }
  });
  document.getElementById('removeThumbnailButton')?.addEventListener('click', () => {
    thumbnailFile.value = '';
    thumbnailValue.value = '';
    thumbnailRemoved.value = '1';
    showThumbnail('');
  });
  colorInput.addEventListener('input', () => { if (!thumbnail.querySelector('img')) thumbnail.style.backgroundColor = colorInput.value; });
  showThumbnail(thumbnail.querySelector('img')?.src || '');

  const video = document.getElementById('settingsVideoPreview');
  const videoUrl = document.getElementById('settingsVideoUrl');
  const videoFile = document.getElementById('settingsVideoFile');
  let videoObjectUrl;
  const previewVideo = (src) => {
    video.replaceChildren();
    let element;
    try {
      const url = new URL(src, location.origin);
      const host = url.hostname.replace(/^www\./, '');
      const youtubeId = host === 'youtube.com' || host === 'm.youtube.com' ? url.searchParams.get('v') : host === 'youtu.be' ? url.pathname.slice(1) : null;
      if (youtubeId && /^[A-Za-z0-9_-]{11}$/.test(youtubeId)) {
        element = document.createElement('iframe');
        element.src = 'https://www.youtube-nocookie.com/embed/' + youtubeId;
        element.title = 'Video preview';
        element.allowFullscreen = true;
      } else if (src && (url.origin === location.origin || src.startsWith('blob:'))) {
        element = document.createElement('video');
        element.src = src;
        element.controls = true;
      }
    } catch (_) { /* Empty or invalid links retain the placeholder. */ }
    if (element) { element.className = 'h-full w-full object-contain'; video.append(element); }
    else video.innerHTML = '<i class="fa-solid fa-video text-gray-400"></i>';
  };
  videoUrl.addEventListener('input', () => { videoFile.value = ''; previewVideo(videoUrl.value.trim()); });
  videoFile.addEventListener('change', () => {
    if (videoObjectUrl) URL.revokeObjectURL(videoObjectUrl);
    videoObjectUrl = videoFile.files[0] ? URL.createObjectURL(videoFile.files[0]) : null;
    if (videoObjectUrl) { videoUrl.value = ''; previewVideo(videoObjectUrl); }
  });
  document.getElementById('removePreviewVideo').addEventListener('click', () => { videoFile.value = ''; videoUrl.value = ''; previewVideo(''); });
  previewVideo(videoUrl.value.trim());

  const editor = document.getElementById('descriptionEditor');
  const uploadError = document.getElementById('settingsEditorUploadError');
  let editorRange;
  document.querySelectorAll('[data-settings-media]').forEach(button => button.addEventListener('click', () => {
    const selection = window.getSelection();
    if (selection?.rangeCount && editor.contains(selection.anchorNode)) editorRange = selection.getRangeAt(0).cloneRange();
    document.getElementById(button.dataset.settingsMedia === 'image' ? 'settingsEditorImageFile' : 'settingsEditorVideoFile').click();
  }));
  for (const [kind, inputId] of [['image', 'settingsEditorImageFile'], ['video', 'settingsEditorVideoFile']]) {
    document.getElementById(inputId).addEventListener('change', async event => {
      const input = event.currentTarget;
      const file = input.files[0];
      if (!file) return;
      uploadError.classList.add('hidden');
      const maxBytes = kind === 'image' ? 5 * 1048576 : 50 * 1048576;
      if (file.size > maxBytes) {
        uploadError.textContent = `${kind === 'image' ? 'Image' : 'Video'} must be ${kind === 'image' ? '5' : '50'} MB or less.`;
        uploadError.classList.remove('hidden');
        input.value = '';
        return;
      }
      const body = new FormData();
      body.append('file', file);
      body.append('is_public', '1');
      try {
        const response = await fetch('{{ route('admin.media.upload') }}', {
          method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
          credentials: 'same-origin', body,
        });
        const result = await response.json();
        if (!response.ok || !result.success || !result.url) throw new Error(result.error || 'Upload failed.');
        editor.focus();
        if (editorRange) {
          const selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(editorRange);
        }
        const media = document.createElement(kind === 'image' ? 'img' : 'video');
        media.src = result.url;
        if (kind === 'image') media.alt = file.name;
        else media.controls = true;
        document.execCommand('insertHTML', false, media.outerHTML + '<p><br></p>');
      } catch (error) {
        uploadError.textContent = error.message || 'The file could not be uploaded.';
        uploadError.classList.remove('hidden');
      } finally { input.value = ''; }
    });
  }
})();
</script>
