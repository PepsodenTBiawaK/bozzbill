import { test } from 'node:test';
import assert from 'node:assert/strict';
import avatarEditor, { avatarCropRect } from '../../resources/js/avatar-editor.js';

function browser(t, { width = 1200, height = 800, invalidImage = false, deferLoad = false, deferExport = false, failedExport = false } = {}) {
    const images = [];
    const drawings = [];
    const urls = [];
    const revoked = [];
    let finishExport;
    const originalGlobals = Object.fromEntries(['Image', 'DataTransfer', 'document'].map((name) => [name, globalThis[name]]));
    t.after(() => {
        for (const [name, value] of Object.entries(originalGlobals)) {
            if (value === undefined) delete globalThis[name];
            else globalThis[name] = value;
        }
    });
    t.mock.method(URL, 'createObjectURL', (blob) => {
        const url = `blob:photo-${urls.length}`;
        urls.push({ url, blob });
        return url;
    });
    t.mock.method(URL, 'revokeObjectURL', (url) => revoked.push(url));
    globalThis.Image = class {
        naturalWidth = width;
        naturalHeight = height;
        set src(value) {
            this.url = value;
            images.push(this);
            if (!deferLoad) queueMicrotask(() => invalidImage ? this.onerror() : this.onload());
        }
    };
    globalThis.DataTransfer = class {
        files = [];
        items = { add: (file) => this.files.push(file) };
    };
    const context = { fillRect() {}, drawImage: (...args) => drawings.push(args) };
    const canvas = {
        getContext: () => context,
        toBlob(callback, type) {
            finishExport = () => callback(failedExport ? null : new Blob(['cropped photo'], { type }));
            if (!deferExport) finishExport();
        },
    };
    globalThis.document = { createElement: () => canvas };
    return { images, drawings, urls, revoked, canvas, finishExport: () => finishExport() };
}

function editorWithInput(hasAvatar = false, removeAvatar = false) {
    const editor = avatarEditor(hasAvatar, removeAvatar);
    let files = [];
    editor.$refs = {
        avatarInput: {
            get files() { return files; },
            set files(value) { files = value; },
            set value(value) { if (value === '') files = []; },
        },
    };
    return editor;
}

function choose(editor, file = new File(['original photo'], 'photo.jpg', { type: 'image/jpeg' })) {
    return editor.chooseAvatar({ target: { files: [file], value: 'photo.jpg' } });
}

test('landscape and portrait photos start centered with a square crop covering the full circle', () => {
    assert.deepEqual(avatarCropRect(1200, 800), { x: 200, y: 0, size: 800 });
    assert.deepEqual(avatarCropRect(800, 1200), { x: 0, y: 200, size: 800 });
    assert.deepEqual(avatarCropRect(800, 800), { x: 0, y: 0, size: 800 });
});

test('zoom narrows the chosen area and dragging cannot reveal blank edges', () => {
    assert.deepEqual(avatarCropRect(1200, 800, 2), { x: 400, y: 200, size: 400 });
    assert.deepEqual(avatarCropRect(1200, 800, 2, -100, 1000), { x: 0, y: 400, size: 400 });
    assert.deepEqual(avatarCropRect(1200, 800, 0), { x: 200, y: 0, size: 800 });
    assert.deepEqual(avatarCropRect(900, 600, 4), { x: 350, y: 200, size: 200 });
});

test('choosing a photo opens the editor without uploading the uncropped original', async (t) => {
    browser(t);
    const editor = editorWithInput(true);

    await choose(editor);

    assert.equal(editor.editing, true);
    assert.equal(editor.loading, false);
    assert.equal(editor.previewUrl, null);
    assert.equal(editor.$refs.avatarInput.files.length, 0);
    assert.deepEqual(editor.imageStyle(), { width: '150%', height: '100%', left: '-25%', top: '0%' });
});

test('pointer dragging uses the responsive frame width and captures the active finger only', async (t) => {
    browser(t);
    const editor = editorWithInput();
    await choose(editor);
    let capture = null;
    const frame = {
        setPointerCapture: (id) => { capture = id; },
        hasPointerCapture: (id) => capture === id,
        releasePointerCapture: () => { capture = null; },
        getBoundingClientRect: () => ({ width: 200 }),
    };
    const pointer = (id, x) => ({ pointerId: id, clientX: x, clientY: 0, button: 0, currentTarget: frame });

    editor.startDrag(pointer(1, 0));
    editor.startDrag(pointer(2, 0));
    editor.drag(pointer(2, 100));
    assert.deepEqual(editor.cropRect(), { x: 200, y: 0, size: 800 });
    editor.drag(pointer(1, 50));
    assert.deepEqual(editor.cropRect(), { x: 0, y: 0, size: 800 });
    editor.endDrag(pointer(1, 50));
    assert.equal(capture, null);
    editor.drag(pointer(1, -50));
    assert.equal(editor.cropRect().x, 0);
});

test('keyboard panning and zooming keep the photo inside bounds and reset restores the center', async (t) => {
    browser(t);
    const editor = editorWithInput();
    await choose(editor);

    editor.setZoom(2);
    editor.movePhoto(-0.25, 0.25);
    assert.deepEqual(editor.cropRect(), { x: 500, y: 100, size: 400 });
    editor.movePhoto(-10, -10);
    assert.deepEqual(editor.cropRect(), { x: 800, y: 400, size: 400 });
    editor.setZoom(1);
    assert.deepEqual(editor.cropRect(), { x: 400, y: 0, size: 800 });
    editor.resetCrop();
    assert.deepEqual(editor.cropRect(), { x: 200, y: 0, size: 800 });
});

test('apply exports the selected area to a square upload and the accepted crop can be adjusted again', async (t) => {
    const env = browser(t);
    const editor = editorWithInput(true, true);
    await choose(editor);
    editor.setZoom(2);
    editor.movePhoto(-0.25, 0.25);

    await editor.applyCrop();

    assert.deepEqual(env.drawings[0].slice(1), [500, 100, 400, 400, 0, 0, 512, 512]);
    assert.equal(env.canvas.width, 512);
    assert.equal(env.canvas.height, 512);
    assert.equal(editor.$refs.avatarInput.files[0].type, 'image/jpeg');
    assert.equal(editor.$refs.avatarInput.files[0].name, 'avatar.jpg');
    assert.equal(editor.previewUrl, 'blob:photo-1');
    assert.equal(editor.removeAvatar, false);
    assert.equal(editor.editing, false);
    assert.equal(editor.exporting, false);
    editor.editCrop();
    editor.movePhoto(1, 1);
    editor.cancelCrop();
    assert.deepEqual(editor.cropRect(), { x: 500, y: 100, size: 400 });
    assert.equal(editor.previewUrl, 'blob:photo-1');
});

test('cancelling a replacement keeps the already accepted upload and preserves pending removal', async (t) => {
    const env = browser(t);
    const editor = editorWithInput(true);
    await choose(editor);
    await editor.applyCrop();
    const accepted = editor.$refs.avatarInput.files[0];

    await choose(editor);
    editor.cancelCrop();

    assert.equal(editor.$refs.avatarInput.files[0], accepted);
    assert.equal(editor.previewUrl, 'blob:photo-1');
    assert.equal(editor.editing, false);
    assert.deepEqual(env.revoked, ['blob:photo-0', 'blob:photo-2']);
    editor.clearAvatar();
    await choose(editor);
    editor.cancelCrop();
    assert.equal(editor.removeAvatar, true);
    assert.equal(editor.previewUrl, null);
    assert.equal(editor.$refs.avatarInput.files.length, 0);
});

test('unsupported oversized and unreadable photos never become pending uploads', async (t) => {
    browser(t, { invalidImage: true });
    const editor = editorWithInput(true);

    await choose(editor, new File(['svg'], 'photo.svg', { type: 'image/svg+xml' }));
    assert.match(editor.previewError, /JPG, PNG, atau WebP/);
    await choose(editor, new File([new Uint8Array(2 * 1024 * 1024 + 1)], 'photo.jpg', { type: 'image/jpeg' }));
    assert.match(editor.previewError, /maksimal 2 MB/);
    await choose(editor);
    assert.match(editor.previewError, /tidak dapat dibuka/);
    assert.equal(editor.$refs.avatarInput.files.length, 0);
    assert.equal(editor.previewUrl, null);
    assert.equal(editor.sourceUrl, null);
    assert.equal(editor.editing, false);
});

test('saving is blocked until the pending edit or load has been accepted or cancelled', () => {
    const editor = editorWithInput();
    let prevented = 0;
    const event = { preventDefault: () => prevented++ };

    editor.loading = true;
    editor.guardSubmit(event);
    editor.loading = false;
    editor.editing = true;
    editor.guardSubmit(event);
    editor.editing = false;
    editor.exporting = true;
    editor.guardSubmit(event);
    editor.exporting = false;
    editor.guardSubmit(event);

    assert.equal(prevented, 3);
    assert.match(editor.previewError, /Gunakan Foto atau Batal/);
});

test('a failed export leaves the previous accepted photo intact and allows retry', async (t) => {
    browser(t, { failedExport: true });
    const editor = editorWithInput(true);
    await choose(editor);

    await editor.applyCrop();

    assert.match(editor.previewError, /gagal diproses/);
    assert.equal(editor.editing, true);
    assert.equal(editor.exporting, false);
    assert.equal(editor.$refs.avatarInput.files.length, 0);
});

test('cancelling while an image loads cannot reopen the editor when decoding finishes', async (t) => {
    const env = browser(t, { deferLoad: true });
    const editor = editorWithInput();
    const loading = choose(editor);

    editor.cancelCrop();
    env.images[0].onload();
    await loading;

    assert.equal(editor.editing, false);
    assert.equal(editor.loading, false);
    assert.equal(editor.sourceUrl, null);
});

test('clearing during export cannot restore the removed photo and releases object URLs', async (t) => {
    const env = browser(t, { deferExport: true });
    const editor = editorWithInput(true);
    await choose(editor);
    const exporting = editor.applyCrop();

    editor.clearAvatar();
    env.finishExport();
    await exporting;

    assert.equal(editor.removeAvatar, true);
    assert.equal(editor.previewUrl, null);
    assert.equal(editor.$refs.avatarInput.files.length, 0);
    assert.equal(editor.exporting, false);
    assert.deepEqual(env.revoked, ['blob:photo-0']);
});

test('leaving the form releases both the original and cropped photo URLs', async (t) => {
    const env = browser(t);
    const editor = editorWithInput();
    await choose(editor);
    await editor.applyCrop();

    editor.destroy();

    assert.deepEqual(env.revoked, ['blob:photo-0', 'blob:photo-1']);
});
