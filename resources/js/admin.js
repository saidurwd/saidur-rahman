import * as bootstrap from 'bootstrap';
import 'admin-lte/dist/js/adminlte.esm.js';

window.bootstrap = bootstrap;

// Flash toasts
document.querySelectorAll('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el).show());

// Confirm before destructive submits (x-admin.delete-button)
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Image upload preview (x-admin.form.image-upload)
document.addEventListener('change', (event) => {
    const input = event.target;
    if (!input.matches?.('input[type="file"][data-preview]')) {
        return;
    }
    const preview = document.getElementById(input.dataset.preview);
    const file = input.files?.[0];
    if (preview && file?.type.startsWith('image/')) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
});

// Unsaved-changes warning on forms marked data-unsaved-warning
document.querySelectorAll('form[data-unsaved-warning]').forEach((form) => {
    let dirty = false;
    form.addEventListener('input', () => (dirty = true));
    form.addEventListener('submit', () => (dirty = false));
    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
        }
    });
});
