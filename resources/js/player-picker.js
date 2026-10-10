export default function playerPicker(players, value = '') {
    const normalize = (text) => String(text).trim().replace(/\s+/g, ' ').toLocaleLowerCase();
    const roster = players.map((player) => ({ ...player, id: String(player.id) }));
    return {
        players: roster,
        selectedId: String(value ?? ''),
        query: roster.find((player) => player.id === String(value))?.name ?? '',
        open: false,
        active: -1,
        excluded: [],
        allowed: null,
        init() {
            this.$watch('selectedId', (value) => {
                const player = this.players.find((player) => player.id === String(value));
                if (player) this.query = player.name;
                else if (!this.open) this.query = '';
            });
        },
        options() {
            const query = this.selectedId ? '' : normalize(this.query);
            return this.players.filter((player) => !this.excluded.includes(player.id)
                && (this.allowed === null || this.allowed.includes(player.id))
                && (normalize(player.name).includes(query) || normalize(player.handicap).includes(query)));
        },
        setRestrictions(excluded = [], allowed = null) {
            const excludedIds = excluded.filter(Boolean).map(String);
            const allowedIds = allowed === null ? null : allowed.filter(Boolean).map(String);
            if (JSON.stringify(excludedIds) !== JSON.stringify(this.excluded)) this.excluded = excludedIds;
            if (JSON.stringify(allowedIds) !== JSON.stringify(this.allowed)) this.allowed = allowedIds;
            if (this.selectedId && !this.options().some((player) => player.id === String(this.selectedId))) this.clear();
        },
        search() {
            this.open = true;
            this.selectedId = '';
            this.active = -1;
        },
        choose(player) {
            if (!this.options().some((option) => option.id === player.id)) return;
            this.selectedId = player.id;
            this.query = player.name;
            this.open = false;
            this.active = -1;
            this.$dispatch?.('player-selected', { player });
        },
        clear() {
            this.selectedId = '';
            this.query = '';
            this.active = -1;
            this.open = false;
        },
        move(direction) {
            this.open = true;
            const count = this.options().length;
            this.active = !count ? -1 : this.active < 0 ? (direction > 0 ? 0 : count - 1)
                : (this.active + direction + count) % count;
        },
        chooseActive() {
            const player = this.options()[this.active < 0 ? 0 : this.active];
            if (this.open && player) this.choose(player);
        },
    };
}
