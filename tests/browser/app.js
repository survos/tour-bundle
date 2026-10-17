import { Application } from '@hotwired/stimulus';
import Tour from '../../assets/src/controllers/tour_controller.js';
const application = Application.start();
application.register('survos--tour-bundle--tour', Tour);
// Fixture presentation only: these are ordinary navigation controls, not tour logic.
document.querySelector('#mobile-toggle').addEventListener('click', (event) => {
    const open = document.querySelector('.mobile nav').classList.toggle('open');
    event.target.setAttribute('aria-expanded', String(open));
});
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-bs-toggle="dropdown"]');
    if (toggle) { event.preventDefault(); toggle.nextElementSibling.classList.toggle('show'); }
});
// Test handle for disconnect/reconnect and programmatic inspection.
window.fixtureApplication = application;
