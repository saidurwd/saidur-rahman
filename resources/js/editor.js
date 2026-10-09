/* Rich-text editor (TinyMCE, self-hosted, GPL). Loaded on demand by admin.js when a [data-editor] field exists. */
import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';

import 'tinymce/plugins/autolink';
import 'tinymce/plugins/code';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';
import 'tinymce/plugins/wordcount';

import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide-dark/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/ui/oxide-dark/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/skins/content/dark/content.js';

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

export function initEditors() {
    const dark = isDark();

    tinymce.init({
        selector: 'textarea[data-editor]',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        skin: dark ? 'oxide-dark' : 'oxide',
        content_css: dark ? 'dark' : 'default',
        height: 380,
        menubar: false,
        plugins: 'autolink code image link lists table wordcount',
        toolbar:
            'blocks | bold italic | bullist numlist | link image table | blockquote | removeformat | code',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre',
        // Images are added by URL only; uploads go through the media fields on the form.
        image_advtab: false,
        relative_urls: false,
        convert_urls: false,
        setup(editor) {
            // Keep the textarea in sync and tell the unsaved-changes guard that something changed.
            editor.on('change input undo redo', () => {
                editor.save();
                editor.getElement().dispatchEvent(new Event('input', { bubbles: true }));
            });
        },
    });
}
