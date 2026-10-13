export default function matchRecorder(participants, values = {}) {
    return {
        members: participants,
        p1Id: String(values.p1Id ?? ''),
        p2Id: String(values.p2Id ?? ''),
        p1Score: Number(values.p1Score ?? 0),
        p2Score: Number(values.p2Score ?? 0),
        winnerId: String(values.winnerId ?? ''),
        stage: values.stage ?? 'Penyisihan',
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
