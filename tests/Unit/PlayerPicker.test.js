import { test } from 'node:test';
import assert from 'node:assert/strict';
import playerPicker from '../../resources/js/player-picker.js';

const players = [
    { id: 1, name: 'Fadil', handicap: '3A' },
    { id: 2, name: 'Rakha (Teman Azril)', handicap: '3B' },
    { id: 3, name: 'Satrio', handicap: '3N' },
];

test('name search normalizes case and spaces and clears the old submitted identifier', () => {
    const picker = playerPicker(players, '1');
    picker.query = '  TEMAN   azril ';
    picker.search();
    assert.equal(picker.selectedId, '');
    assert.deepEqual(picker.options().map((player) => player.id), ['2']);
    picker.chooseActive();
    assert.equal(picker.selectedId, '2');
    assert.equal(picker.query, 'Rakha (Teman Azril)');
    assert.equal(picker.open, false);
});

test('excluded opponents and podium positions cannot be selected and changed restrictions clear invalid selections', () => {
    const picker = playerPicker(players);
    picker.setRestrictions(['1']);
    picker.choose({ ...players[0], id: '1' });
    assert.equal(picker.selectedId, '');
    picker.choose(picker.options()[0]);
    assert.equal(picker.selectedId, '2');
    picker.setRestrictions(['1', '2']);
    assert.equal(picker.selectedId, '');
    assert.equal(picker.query, '');
});

test('winner selection only offers the current two players and invalidates the replaced winner', () => {
    const picker = playerPicker(players, '1');
    picker.setRestrictions([], ['1', '3']);
    assert.deepEqual(picker.options().map((player) => player.id), ['1', '3']);
    picker.setRestrictions([], ['2', '3']);
    assert.equal(picker.selectedId, '');
    picker.setRestrictions([], []);
    assert.deepEqual(picker.options(), []);
});

test('arrow navigation selects by keyboard and a nonexistent query cannot choose a player', () => {
    const picker = playerPicker(players);
    picker.move(-1);
    picker.chooseActive();
    assert.equal(picker.selectedId, '3');
    picker.query = 'tidak ada';
    picker.search();
    picker.move(1);
    picker.chooseActive();
    assert.equal(picker.selectedId, '');
    assert.equal(picker.active, -1);
});

test('handicap search and optional clearing keep visible text and submitted selection consistent', () => {
    const picker = playerPicker(players);
    picker.query = '3a';
    picker.search();
    picker.chooseActive();
    assert.equal(picker.query, 'Fadil');
    picker.clear();
    assert.equal(picker.query, '');
    assert.equal(picker.selectedId, '');
    assert.equal(picker.open, false);
});

test('pair selection can find either member or handicap and excludes the opposing pair', () => {
    const picker = playerPicker([
        { id: 12, name: 'Fadil & Hafiz', handicap: '3A / 3N' },
        { id: 34, name: 'Andrew & Fitrah', handicap: '3B / 3N' },
    ]);
    picker.query = 'hafiz';
    picker.search();

    assert.deepEqual(picker.options().map((team) => team.id), ['12']);
    picker.chooseActive();
    assert.equal(picker.query, 'Fadil & Hafiz');
    assert.equal(picker.selectedId, '12');
    picker.setRestrictions(['12']);
    picker.query = '3n';
    picker.search();
    assert.deepEqual(picker.options().map((team) => team.id), ['34']);
});
