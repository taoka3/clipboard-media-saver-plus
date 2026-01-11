(function () {
  document.addEventListener('paste', function (e) {
    if (!document.body.classList.contains('upload-php')) {
      return;
    }

    const items = e.clipboardData?.items || [];

    for (const item of items) {
      if (item.kind !== 'file') continue;

      const file = item.getAsFile();
      if (!file) continue;

      let ext = '';
      if (file.type.startsWith('image/')) ext = '.png';
      else if (file.type.startsWith('image/')) ext = '.jpg';
      else if (file.type.startsWith('image/')) ext = '.gif';
      else if (file.type.startsWith('image/')) ext = '.webp';
      else if (file.type.startsWith('audio/')) ext = '.mp3';
      else if (file.type.startsWith('video/')) ext = '.mp4';
      else continue;

      const filename = file.name && file.name.includes('.')
        ? file.name
        : `clipboard-${Date.now()}${ext}`;

      const formData = new FormData();
      formData.append('action', 'clipboard_media_upload');
      formData.append('nonce', CMSP.nonce);
      formData.append('file', file, filename);

      fetch(CMSP.ajax, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      })
        .then(r => r.json())
        .then(r => {
          if (!r.success) {
            alert(r.data || 'Upload failed');
          } else {
            alert('保存しました.リロードします.');
            console.log('Saved:', r.data.url);
            this.location.reload();
          }
        })
        .catch(err => console.error(err));
    }
  });
})();
