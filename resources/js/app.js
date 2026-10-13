import Alpine from 'alpinejs';
import headToHeadPicker from './head-to-head-picker';
import playerPicker from './player-picker';
import avatarEditor from './avatar-editor';
import accountActivation from './account-activation';
import matchRecorder from './match-recorder';

window.Alpine = Alpine;
Alpine.data('headToHeadPicker', headToHeadPicker);
Alpine.data('playerPicker', playerPicker);
Alpine.data('avatarEditor', avatarEditor);
Alpine.data('accountActivation', accountActivation);
Alpine.data('matchRecorder', matchRecorder);

Alpine.start();
