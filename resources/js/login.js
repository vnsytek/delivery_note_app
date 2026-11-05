import { initPoster } from './login/poster';
import { initPasswordToggle } from './login/password-toggle';
import { initFolder } from './login/folder';

const poster = document.querySelector('[data-poster]');
if (poster) {
    initPoster(poster);
}

document.querySelectorAll('[data-toggle-password]').forEach(initPasswordToggle);

document.querySelectorAll('[data-folder]').forEach(initFolder);
