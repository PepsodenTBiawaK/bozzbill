export default function matchRecorder(participants, values = {}, options = {}) {
    return {
        members: participants,
        p1Id: String(values.p1Id ?? ''),
        p2Id: String(values.p2Id ?? ''),
        p1Score: Number(values.p1Score ?? 0),
        p2Score: Number(values.p2Score ?? 0),
        winnerId: String(values.winnerId ?? ''),
        stage: values.stage ?? 'Penyisihan',
        get voorPreview() {
            if (options.format === 'double') return '';
            const first = this.getP1();
            const second = this.getP2();
            if (!first || !second || this.p1Id === this.p2Id) return 'Pilih kedua pemain untuk melihat voor.';
            if (options.originalIds?.includes(this.p1Id) && options.originalIds.includes(this.p2Id)) return options.originalVoor;
            if (!Number.isInteger(first.handicapRank) || !Number.isInteger(second.handicapRank)) return 'Voor tidak tercatat';
            const gap = Math.abs(first.handicapRank - second.handicapRank);
            const label = options.voorLabels?.[gap];
            if (!label) return 'Voor tidak tercatat';
            return gap === 0 ? label : `${label} untuk ${first.handicapRank < second.handicapRank ? first.name : second.name}`;
        },
        get inferredRace() {
            const first = Number(this.p1Score);
            const second = Number(this.p2Score);
            const winner = first > second ? this.p1Id : this.p2Id;
            const score = Math.max(first, second);
            return this.getP1() && this.getP2() && this.p1Id !== this.p2Id && first !== second
                && Number.isInteger(first) && Number.isInteger(second) && Math.min(first, second) >= 0
                && this.winnerId === winner && [3, 4, 5].includes(score) ? score : null;
        },
        get racePreview() {
            return this.inferredRace === null ? 'Race mengikuti skor akhir 3, 4, atau 5.' : `Race ${this.inferredRace} · dari skor akhir`;
        },
        init() {
            ['p1Id', 'p2Id', 'p1Score', 'p2Score'].forEach((field) => {
                this.$watch(field, () => this.updateWinner());
            });
            this.updateWinner();
        },
        getP1() {
            return this.members.find((member) => String(member.id) === this.p1Id);
        },
        getP2() {
            return this.members.find((member) => String(member.id) === this.p2Id);
        },
        updateWinner() {
            if (options.autoWinner === false) return;
            const firstScore = Number(this.p1Score);
            const secondScore = Number(this.p2Score);
            if (!this.getP1() || !this.getP2() || this.p1Id === this.p2Id
                || !Number.isInteger(firstScore) || !Number.isInteger(secondScore)
                || firstScore < 0 || secondScore < 0 || firstScore > 50 || secondScore > 50
                || firstScore === secondScore) {
                this.winnerId = '';
                return;
            }
            this.winnerId = firstScore > secondScore ? this.p1Id : this.p2Id;
        },
    };
}
