import { test } from 'node:test';
import assert from 'node:assert/strict';
import matchRecorder from '../../resources/js/match-recorder.js';

const teams = [
    { id: 12, name: 'Fadil & Hafiz', handicap: '3A / 3N' },
    { id: 34, name: 'Andrew & Fitrah', handicap: '3B / 3N' },
    { id: 56, name: 'Huwan & Revan', handicap: '3B / 3B' },
];

test('new recording starts at zero for both sides and no winner', () => {
    const recorder = matchRecorder(teams);

    assert.equal(recorder.p1Score, 0);
    assert.equal(recorder.p2Score, 0);
    assert.equal(recorder.winnerId, '');
});

test('winner follows numeric scores including values submitted as strings', () => {
    const recorder = matchRecorder(teams, { p1Id: '12', p2Id: '34', p1Score: '10', p2Score: '9' });

    recorder.updateWinner();
    assert.equal(recorder.winnerId, '12');
    recorder.p2Score = '11';
    recorder.updateWinner();
    assert.equal(recorder.winnerId, '34');
});

test('changing a selected pair updates the winner to the current pair', () => {
    const recorder = matchRecorder(teams, { p1Id: '12', p2Id: '34', p1Score: 3, p2Score: 1 });
    recorder.updateWinner();

    recorder.p1Id = '56';
    recorder.updateWinner();

    assert.equal(recorder.winnerId, '56');
    assert.equal(recorder.getP1().name, 'Huwan & Revan');
});

test('ties and absent, invalid or duplicate pairs clear a previous winner', () => {
    const invalidChanges = [
        { p1Score: 1, p2Score: 1 },
        { p1Id: '' },
        { p2Id: '999' },
        { p2Id: '12' },
        { p1Score: -1 },
        { p2Score: 51 },
        { p1Score: 1.5 },
        { p2Score: 'abc' },
    ];

    for (const change of invalidChanges) {
        const recorder = matchRecorder(teams, { p1Id: '12', p2Id: '34', p1Score: 3, p2Score: 1, winnerId: '12' });
        Object.assign(recorder, change);

        recorder.updateWinner();

        assert.equal(recorder.winnerId, '', JSON.stringify(change));
    }
});

test('initialization restores scores and stage and tracks all inputs that determine a winner', () => {
    const recorder = matchRecorder(teams, { p1Id: 12, p2Id: 34, p1Score: 1, p2Score: 3, stage: 'Final', winnerId: 12 });
    const watchers = new Map();
    recorder.$watch = (field, callback) => watchers.set(field, callback);

    recorder.init();
    assert.equal(recorder.winnerId, '34');
    assert.equal(recorder.stage, 'Final');
    assert.deepEqual([...watchers.keys()], ['p1Id', 'p2Id', 'p1Score', 'p2Score']);
    recorder.p1Score = 4;
    watchers.get('p1Score')();
    assert.equal(recorder.winnerId, '12');
});

const voorLabels = ['Tanpa Voor', 'Voor 1 Frame', 'Voor Bola 8 Naik', 'Voor Bola 8 Sambung', 'Voor Bola 7 Naik'];

test('single voor preview follows the selected HC gap and names the lower HC recipient', () => {
    const players = [
        { id: 1, name: 'Awal', handicapRank: 1 },
        { id: 2, name: 'Lawan', handicapRank: 2 },
        { id: 3, name: 'Tertinggi', handicapRank: 5 },
    ];
    const recorder = matchRecorder(players, { p1Id: 1, p2Id: 2 }, { format: 'single', voorLabels });
    assert.equal(recorder.voorPreview, 'Voor 1 Frame untuk Awal');
    recorder.p2Id = '3';
    assert.equal(recorder.voorPreview, 'Voor Bola 7 Naik untuk Awal');
    recorder.p1Id = '3';
    recorder.p2Id = '1';
    assert.equal(recorder.voorPreview, 'Voor Bola 7 Naik untuk Awal');
});

test('double previews omit voor while valid final scores infer races three through five', () => {
    const recorder = matchRecorder(teams, { p1Id: 12, p2Id: 34, p1Score: 3, p2Score: 5 }, { format: 'double' });
    recorder.updateWinner();
    assert.equal(recorder.voorPreview, '');
    assert.equal(recorder.inferredRace, 5);
    recorder.p1Score = 4;
    recorder.p2Score = 2;
    recorder.updateWinner();
    assert.equal(recorder.inferredRace, 4);
    recorder.p1Score = 3;
    recorder.updateWinner();
    assert.equal(recorder.inferredRace, 3);
    recorder.p1Score = 2;
    recorder.updateWinner();
    assert.equal(recorder.inferredRace, null);
});

test('historical or missing HC cannot silently acquire a guessed voor', () => {
    const players = [{ id: 1, name: 'Awal', handicapRank: 1 }, { id: 2, name: 'Lawan', handicapRank: 5 }, { id: 3, name: 'Arsip' }];
    const recorder = matchRecorder(players, { p1Id: 1, p2Id: 2 }, { format: 'single', voorLabels, originalIds: ['1', '2'], originalVoor: 'Voor tidak tercatat' });
    assert.equal(recorder.voorPreview, 'Voor tidak tercatat');
    recorder.p2Id = '3';
    assert.equal(recorder.voorPreview, 'Voor tidak tercatat');
});

test('manual winner selection remains intact in active single edits', () => {
    const recorder = matchRecorder(teams, { p1Id: 12, p2Id: 34, p1Score: 3, p2Score: 5, winnerId: 12 }, { autoWinner: false });
    recorder.updateWinner();
    assert.equal(recorder.winnerId, '12');
    assert.equal(recorder.inferredRace, null);
});
