import assert from 'node:assert/strict';
import { test } from 'node:test';
import accountActivation from '../../resources/js/account-activation.js';

function browser(t, fetchHandler) {
    const originalDocument = globalThis.document;
    globalThis.document = { querySelector: () => ({ getAttribute: () => 'csrf-token' }) };
    t.after(() => {
        if (originalDocument === undefined) delete globalThis.document;
        else globalThis.document = originalDocument;
    });
    t.mock.method(globalThis, 'fetch', fetchHandler);
    const state = accountActivation('/check-activation', 'activation', '+6281234567890');
    state.activationCode = '12345678';
    state.$nextTick = (callback) => callback();
    state.$refs = { loginPassword: { focus: t.mock.fn() } };
    return state;
}

test('an active account automatically opens login with its phone, notice and password focus', async (t) => {
    const state = browser(t, async () => ({ ok: true, json: async () => ({
        status: 'already_active', phone: '081234567890', message: 'Akun sudah aktif. Silakan masuk, Bozz.',
    }) }));
    state.activationCode = '';

    await state.checkPhone();

    assert.equal(state.tab, 'login');
    assert.equal(state.phone, '081234567890');
    assert.equal(state.activationNotice, 'Akun sudah aktif. Silakan masuk, Bozz.');
    assert.equal(state.checkError, '');
    assert.equal(state.checking, false);
    assert.equal(state.$refs.loginPassword.focus.mock.callCount(), 1);
});

test('an inactive account proceeds to password creation with the normalized member details', async (t) => {
    const member = { name: 'Anggota Bozz', phone: '081234567890', handicap: '3B' };
    const state = browser(t, async (url, options) => {
        assert.equal(url, '/check-activation');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf-token');
        assert.deepEqual(JSON.parse(options.body), { phone: '+6281234567890', activation_code: '12345678' });
        return { ok: true, json: async () => ({ status: 'ready', user: member }) };
    });

    await state.checkPhone();

    assert.equal(state.tab, 'activation');
    assert.equal(state.step, 2);
    assert.equal(state.phone, '081234567890');
    assert.deepEqual(state.memberInfo, member);
    assert.equal(state.checking, false);
});

test('checking errors keep activation open and allow another attempt', async (t) => {
    const state = browser(t, async () => ({ ok: false, json: async () => ({ message: 'Nomor belum terdaftar.' }) }));
    await state.checkPhone();
    assert.equal(state.checkError, 'Nomor belum terdaftar.');
    assert.equal(state.tab, 'activation');
    assert.equal(state.step, 1);
    assert.equal(state.checking, false);

    t.mock.method(globalThis, 'fetch', async () => { throw new Error('network failure'); });
    await state.checkPhone();
    assert.match(state.checkError, /Periksa koneksi internet/);
    assert.equal(state.checking, false);
});

test('short phone input is rejected before contacting the server', async (t) => {
    const state = browser(t, t.mock.fn());
    state.phone = '081';
    await state.checkPhone();
    assert.match(state.checkError, /minimal 8 digit/);
    assert.equal(globalThis.fetch.mock.callCount(), 0);
});

test('pending phone checks cannot duplicate requests or change a tab the user has left', async (t) => {
    let complete;
    const state = browser(t, () => new Promise((resolve) => { complete = resolve; }));
    const pending = state.checkPhone();
    await state.checkPhone();
    assert.equal(globalThis.fetch.mock.callCount(), 1);
    state.tab = 'login';
    complete({ ok: true, json: async () => ({ status: 'ready', user: { phone: '081234567890' } }) });
    await pending;
    assert.equal(state.tab, 'login');
    assert.equal(state.step, 1);
    assert.equal(state.memberInfo, null);
    assert.equal(state.checking, false);
});

test('phone check results are ignored when the phone has changed during the request', async (t) => {
    let complete;
    const state = browser(t, () => new Promise((resolve) => { complete = resolve; }));
    const pending = state.checkPhone();
    state.phone = '089876543210';
    complete({ ok: true, json: async () => ({ status: 'already_active', phone: '081234567890' }) });
    await pending;
    assert.equal(state.phone, '089876543210');
    assert.equal(state.tab, 'activation');
    assert.equal(state.activationNotice, '');
});

test('activation submits once and can be submitted again after returning to the page', () => {
    const state = accountActivation('/check-activation');
    let prevented = 0;
    const event = { preventDefault: () => prevented++ };
    state.submitActivation(event);
    assert.equal(state.activating, true);
    assert.equal(prevented, 0);
    state.submitActivation(event);
    assert.equal(prevented, 1);
    state.resetSubmission();
    assert.equal(state.activating, false);
    state.submitActivation(event);
    assert.equal(state.activating, true);
    assert.equal(prevented, 1);
});

test('an activation code must contain exactly eight digits before contacting the server', async (t) => {
    const state = browser(t, t.mock.fn());
    state.activationCode = '1234';

    await state.checkPhone();

    assert.match(state.checkError, /8 digit/);
    assert.equal(globalThis.fetch.mock.callCount(), 0);
});

test('changing a code during verification discards the old verification result', async (t) => {
    let complete;
    const state = browser(t, () => new Promise((resolve) => { complete = resolve; }));
    const pending = state.checkPhone();
    state.activationCode = '87654321';
    complete({ ok: true, json: async () => ({ status: 'ready', user: { phone: '081234567890' } }) });

    await pending;

    assert.equal(state.step, 1);
    assert.equal(state.memberInfo, null);
});
