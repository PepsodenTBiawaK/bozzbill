import Alpine from 'alpinejs';
import headToHeadPicker from './head-to-head-picker';
import playerPicker from './player-picker';
import avatarEditor from './avatar-editor';

window.Alpine = Alpine;
Alpine.data('headToHeadPicker', headToHeadPicker);
Alpine.data('playerPicker', playerPicker);
Alpine.data('avatarEditor', avatarEditor);

Alpine.start();
