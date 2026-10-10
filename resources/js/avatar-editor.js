const maxAvatarBytes = 2 * 1024 * 1024;
const clamp = (value, minimum, maximum) => Math.min(maximum, Math.max(minimum, value));

export function avatarCropRect(width, height, zoom = 1, centerX = width / 2, centerY = height / 2) {
    const size = Math.min(width, height) / clamp(Number(zoom), 1, 3);
    return {
        x: clamp(centerX - size / 2, 0, width - size),
        y: clamp(centerY - size / 2, 0, height - size),
        size,
    };
}

export default function avatarEditor(hasAvatar = false, removeAvatar = false) {
    let sourceImage = null;
    let pointer = null;
    let revision = 0;

    return {
        hasAvatar,
        removeAvatar,
        previewUrl: null,
        sourceUrl: null,
        previewError: '',
        editing: false,
        loading: false,
        exporting: false,
        sourceApplied: false,
        appliedCrop: null,
        imageWidth: 0,
        imageHeight: 0,
        zoom: 1,
        centerX: 0,
        centerY: 0,

        releasePreview() {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
        },
        releaseSource() {
            revision++;
            if (this.sourceUrl) URL.revokeObjectURL(this.sourceUrl);
            this.sourceUrl = null;
            sourceImage = null;
            pointer = null;
            this.editing = false;
            this.loading = false;
            this.exporting = false;
            this.sourceApplied = false;
            this.appliedCrop = null;
        },
        async chooseAvatar(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file) return;
            this.previewError = '';
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > maxAvatarBytes) {
                this.previewError = 'Pilih foto JPG, PNG, atau WebP dengan ukuran maksimal 2 MB.';
                return;
            }
            this.releaseSource();
            const currentRevision = revision;
            this.loading = true;
            this.sourceUrl = URL.createObjectURL(file);
            try {
                const image = await new Promise((resolve, reject) => {
                    const photo = new Image();
                    photo.onload = () => resolve(photo);
                    photo.onerror = reject;
                    photo.src = this.sourceUrl;
                });
                if (currentRevision !== revision) return;
                if (!image.naturalWidth || !image.naturalHeight) throw new Error('Invalid photo');
                sourceImage = image;
                this.imageWidth = image.naturalWidth;
                this.imageHeight = image.naturalHeight;
                this.resetCrop();
                this.editing = true;
                this.loading = false;
            } catch {
                if (currentRevision !== revision) return;
                this.releaseSource();
                this.previewError = 'Foto tidak dapat dibuka. Silakan pilih foto lain.';
            }
        },
        cropRect() {
            return avatarCropRect(this.imageWidth, this.imageHeight, this.zoom, this.centerX, this.centerY);
        },
        imageStyle() {
            if (!this.imageWidth || !this.imageHeight) return {};
            const crop = this.cropRect();
            return {
                width: `${this.imageWidth / crop.size * 100}%`,
                height: `${this.imageHeight / crop.size * 100}%`,
                left: `${-crop.x / crop.size * 100}%`,
                top: `${-crop.y / crop.size * 100}%`,
            };
        },
        constrainPosition() {
            const crop = this.cropRect();
            this.centerX = crop.x + crop.size / 2;
            this.centerY = crop.y + crop.size / 2;
        },
        setZoom(value) {
            this.zoom = clamp(Number(value), 1, 3);
            this.constrainPosition();
        },
        resetCrop() {
            this.zoom = 1;
            this.centerX = this.imageWidth / 2;
            this.centerY = this.imageHeight / 2;
        },
        movePhoto(horizontal, vertical) {
            if (!this.editing || this.exporting) return;
            const { size } = this.cropRect();
            this.centerX -= horizontal * size;
            this.centerY -= vertical * size;
            this.constrainPosition();
        },
        startDrag(event) {
            if (!this.editing || this.exporting || pointer || event.button !== 0) return;
            pointer = { id: event.pointerId, x: event.clientX, y: event.clientY };
            event.currentTarget.setPointerCapture(event.pointerId);
        },
        drag(event) {
            if (!pointer || pointer.id !== event.pointerId) return;
            const width = event.currentTarget.getBoundingClientRect().width;
            if (!width) return;
            this.movePhoto((event.clientX - pointer.x) / width, (event.clientY - pointer.y) / width);
            pointer.x = event.clientX;
            pointer.y = event.clientY;
        },
        endDrag(event) {
            if (!pointer || pointer.id !== event.pointerId) return;
            pointer = null;
            if (event.currentTarget.hasPointerCapture(event.pointerId)) event.currentTarget.releasePointerCapture(event.pointerId);
        },
        editCrop() {
            if (sourceImage && !this.exporting) {
                this.previewError = '';
                this.editing = true;
            }
        },
        cancelCrop() {
            this.previewError = '';
            revision++;
            pointer = null;
            if (this.sourceApplied) {
                Object.assign(this, this.appliedCrop);
                this.editing = false;
                this.exporting = false;
            } else {
                this.releaseSource();
            }
        },
        async applyCrop() {
            if (!sourceImage || !this.editing || this.exporting) return;
            this.previewError = '';
            this.exporting = true;
            const currentRevision = revision;
            const appliedCrop = { zoom: this.zoom, centerX: this.centerX, centerY: this.centerY };
            try {
                const crop = this.cropRect();
                const canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 512;
                const context = canvas.getContext('2d');
                if (!context) throw new Error('Canvas unavailable');
                context.fillStyle = '#16171a';
                context.fillRect(0, 0, 512, 512);
                context.drawImage(sourceImage, crop.x, crop.y, crop.size, crop.size, 0, 0, 512, 512);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                if (currentRevision !== revision) return;
                if (!blob || blob.size > maxAvatarBytes) throw new Error('Photo export failed');
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'avatar.jpg', { type: blob.type }));
                this.$refs.avatarInput.files = transfer.files;
                this.releasePreview();
                this.previewUrl = URL.createObjectURL(blob);
                this.removeAvatar = false;
                this.sourceApplied = true;
                this.appliedCrop = appliedCrop;
                this.editing = false;
            } catch {
                if (currentRevision !== revision) return;
                this.previewError = 'Foto gagal diproses. Silakan coba lagi atau pilih foto lain.';
            } finally {
                if (currentRevision === revision) this.exporting = false;
            }
        },
        clearAvatar() {
            this.releaseSource();
            this.releasePreview();
            this.previewError = '';
            this.$refs.avatarInput.value = '';
            this.removeAvatar = true;
        },
        guardSubmit(event) {
            if (this.editing || this.loading || this.exporting) {
                event.preventDefault();
                this.previewError = 'Pilih Gunakan Foto atau Batal sebelum menyimpan profil.';
            }
        },
        destroy() {
            this.releaseSource();
            this.releasePreview();
        },
    };
}
