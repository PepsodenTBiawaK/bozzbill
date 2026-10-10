export default function headToHeadPicker(players, firstId, secondId) {
    const normalized = (value) => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();
    const field = (id) => ({ id, query: players.find((player) => player.id === id)?.name ?? '', open: false, active: -1 });

    return {
        players,
        first: field(firstId),
        second: field(secondId),

        options(side) {
            const current = this[side];
            const otherId = this[side === 'first' ? 'second' : 'first'].id;
            const query = current.id ? '' : normalized(current.query);

            return this.players.filter((player) => player.id !== otherId
                && (normalized(player.name).includes(query) || normalized(player.handicap).includes(query)));
        },

        search(side) {
            this[side].id = '';
            this[side].active = -1;
            this[side].open = true;
        },

        choose(side, player) {
            if (!this.options(side).some((option) => option.id === player.id)) return;
            this[side] = field(player.id);
        },

        move(side, direction) {
            const current = this[side];
            current.open = true;
            const count = this.options(side).length;
            current.active = !count ? -1 : current.active < 0
                ? (direction > 0 ? 0 : count - 1)
                : (current.active + direction + count) % count;
        },

        chooseActive(side) {
            const player = this.options(side)[this[side].active < 0 ? 0 : this[side].active];
            if (this[side].open && player) this.choose(side, player);
        },

        swapPlayers() {
            const firstId = this.first.id;
            this.first = field(this.second.id);
            this.second = field(firstId);
        },

        get canCompare() {
            return Boolean(this.first.id && this.second.id && this.first.id !== this.second.id);
        },
    };
}
