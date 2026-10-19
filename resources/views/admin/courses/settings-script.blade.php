<script>
(() => {
  const thumbnail = document.getElementById('settingsThumbnailPreview');
  const thumbnailFile = document.getElementById('settingsThumbnailFile');
  const thumbnailValue = document.getElementById('settingsThumbnailValue');
  const thumbnailRemoved = document.getElementById('removeThumbnailInput');
  const color = document.getElementById('settingsThumbnailColors');
  const colorInput = document.getElementById('settingsThumbnailColorInput');
  const swatches = [...color.querySelectorAll('[data-thumbnail-color]')];
  const thumbnailName = document.getElementById('settingsThumbnailName');
  const thumbnailHelp = document.getElementById('settingsThumbnailHelp');
  const uploadText = document.getElementById('settingsThumbnailUploadText');
  const removeThumbnailButton = document.getElementById('removeThumbnailButton');
  let thumbnailObjectUrl;
  const showThumbnail = (src) => {
    thumbnail.replaceChildren();
    if (src) {
      const image = document.createElement('img');
      image.src = src;
      image.alt = '';
      image.className = 'h-full w-full object-cover';
      thumbnail.append(image);
      color.hidden = true;
    } else {
      thumbnail.replaceChildren();
      color.hidden = false;
      thumbnail.style.backgroundColor = colorInput.value;
    }
    thumbnailName.hidden = !src;
    uploadText.textContent = src ? 'Replace' : 'Upload image instead';
    removeThumbnailButton.hidden = !src;
    thumbnailHelp.textContent = src ? 'Remove the image to pick a color instead.' : 'Upload an image to replace the color.';
  };
  thumbnailFile.addEventListener('change', () => {
    if (thumbnailObjectUrl) URL.revokeObjectURL(thumbnailObjectUrl);
    thumbnailObjectUrl = thumbnailFile.files[0] ? URL.createObjectURL(thumbnailFile.files[0]) : null;
    if (thumbnailObjectUrl) { thumbnailRemoved.value = '0'; thumbnailName.textContent = thumbnailFile.files[0].name; showThumbnail(thumbnailObjectUrl); }
  });
  removeThumbnailButton.addEventListener('click', () => {
    thumbnailFile.value = '';
    thumbnailValue.value = '';
    thumbnailRemoved.value = '1';
    showThumbnail('');
  });
  const selectColor = (value) => {
    colorInput.value = value;
    swatches.forEach(swatch => swatch.setAttribute('aria-pressed', String(swatch.dataset.thumbnailColor.toLowerCase() === value.toLowerCase())));
    if (!thumbnail.querySelector('img')) thumbnail.style.backgroundColor = value;
  };
  swatches.forEach(swatch => swatch.addEventListener('click', () => selectColor(swatch.dataset.thumbnailColor)));
  selectColor(colorInput.value);
  showThumbnail(thumbnail.querySelector('img')?.src || '');

  const relatedPicker = document.getElementById('settingsRelatedPicker');
  const relatedSearch = document.getElementById('settingsRelatedSearch');
  const relatedOptions = [...relatedPicker.querySelectorAll('.settings-related-option')];
  const relatedEmpty = document.getElementById('settingsRelatedEmpty');
  const updateRelated = () => {
    const selected = relatedOptions.filter(option => option.querySelector('input').checked);
    document.getElementById('settingsRelatedSummary').textContent = selected.length ? selected.map(option => option.querySelector('span span').textContent.trim()).join(', ') : 'Select related courses';
    relatedOptions.forEach(option => { option.querySelector('input').disabled = selected.length >= 12 && !option.querySelector('input').checked; });
  };
  relatedOptions.forEach(option => option.querySelector('input').addEventListener('change', updateRelated));
  relatedSearch.addEventListener('input', () => {
    const term = relatedSearch.value.trim().toLowerCase();
    relatedOptions.forEach(option => { option.hidden = !option.dataset.relatedSearch.includes(term); });
    relatedEmpty.hidden = relatedOptions.some(option => !option.hidden);
  });
  document.getElementById('settingsRelatedClear').addEventListener('click', () => { relatedOptions.forEach(option => { option.querySelector('input').checked = false; }); updateRelated(); });
  document.addEventListener('click', event => { if (!relatedPicker.contains(event.target)) relatedPicker.open = false; });
  updateRelated();
  relatedEmpty.hidden = relatedOptions.length > 0;

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

})();
</script>
