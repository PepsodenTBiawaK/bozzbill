import assert from 'node:assert/strict';
import { test } from 'node:test';
import headToHeadPicker from '../../resources/js/head-to-head-picker.js';

const players = [
    { id: '1', name: 'Fadil', handicap: '3A' },
    { id: '2', name: 'Pedong', handicap: '3N' },
    { id: '3', name: 'Rakha (Teman Azril)', handicap: '3B' },
    { id: '4', name: 'Rakha (Teman Shandy)', handicap: '3B' },
];

test('typing filters names directly and clears the previous player identifier', () => {
    const picker = headToHeadPicker(players, '1', '2');
    picker.first.query = '  TEMAN  azril ';
    picker.search('first');
    assert.deepEqual(picker.options('first').map((player) => player.id), ['3']);
    assert.equal(picker.first.id, '');
    assert.equal(picker.canCompare, false);
    picker.choose('first', players[2]);
    assert.equal(picker.first.id, '3');
    assert.equal(picker.first.query, 'Rakha (Teman Azril)');
    assert.equal(picker.first.open, false);
    assert.equal(picker.canCompare, true);
});

test('both sides support name and handicap search without listing the opposite player', () => {
    const picker = headToHeadPicker(players, '3', '1');
    picker.second.query = '3b';
    picker.search('second');
    assert.deepEqual(picker.options('second').map((player) => player.id), ['4']);
    picker.choose('second', players[2]);
    assert.equal(picker.second.id, '');
    picker.choose('second', players[3]);
    assert.equal(picker.second.id, '4');
});

test('typing an unmatched name cannot silently submit the previous comparison', () => {
    const picker = headToHeadPicker(players, '1', '2');
    picker.second.query = 'Nama tidak ada';
    picker.search('second');
    picker.move('second', 1);
    picker.chooseActive('second');
    assert.equal(picker.options('second').length, 0);
    assert.equal(picker.second.active, -1);
    assert.equal(picker.second.id, '');
    assert.equal(picker.canCompare, false);
});

test('arrow keys and Enter select the highlighted search result', () => {
    const picker = headToHeadPicker(players, '1', '2');
    picker.first.query = 'rakha';
    picker.search('first');
    picker.move('first', 1);
    picker.move('first', 1);
    picker.chooseActive('first');
    assert.equal(picker.first.id, '4');
    assert.equal(picker.first.open, false);
});

test('swapping updates identifiers and visible names together', () => {
    const picker = headToHeadPicker(players, '1', '2');
    picker.first.open = true;
    picker.swapPlayers();
    assert.equal(picker.first.id, '2');
    assert.equal(picker.first.query, 'Pedong');
    assert.equal(picker.second.id, '1');
    assert.equal(picker.second.query, 'Fadil');
    assert.equal(picker.first.open, false);
    assert.equal(picker.canCompare, true);
});

test('ArrowUp from a new search selects the last available result', () => {
    const picker = headToHeadPicker(players, '1', '2');
    picker.first.query = 'rakha';
    picker.search('first');
    picker.move('first', -1);
    picker.chooseActive('first');
    assert.equal(picker.first.id, '4');
});
