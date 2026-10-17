import { Controller } from '@hotwired/stimulus';
import { driver } from 'driver.js';

// Driver owns one overlay at a time. Controllers coordinate through a DOM event,
// with listeners scoped to connect/disconnect rather than a module singleton.
export default class extends Controller {
    static targets = ['replay', 'invitation'];
    static values = {
        name: { type: String, default: 'page' },
        app: { type: String, default: 'app' },
        version: { type: String, default: '1' },
        offer: { type: String, default: 'never' },
        labels: Object,
    };

    connect() {
        this.connected = true;
        this.cancelOther = (event) => { if (event.detail !== this) this.stop(); };
        this.refresh = () => this.scheduleRefresh();
        document.addEventListener('survos:tour-start', this.cancelOther);
        window.addEventListener('resize', this.refresh);
        // Menus may arrive after the layout widget (Turbo, lazy controllers).
        this.observer = new MutationObserver((records) => {
            if (records.some((record) => !this.element.contains(record.target) &&
                !record.target.closest?.('.driver-popover, .driver-overlay'))) this.scheduleRefresh();
        });
        this.observer.observe(document.body, { childList: true, subtree: true, attributes: true,
            attributeFilter: ['hidden', 'class', 'style', 'aria-expanded', 'data-tour-step'] });
        this.update();
    }

    disconnect() {
        this.connected = false;
        this.observer?.disconnect();
        document.removeEventListener('survos:tour-start', this.cancelOther);
        window.removeEventListener('resize', this.refresh);
        cancelAnimationFrame(this.frame);
        this.stop();
    }

    scheduleRefresh() {
        cancelAnimationFrame(this.frame);
        this.frame = requestAnimationFrame(() => { if (this.connected) this.update(); });
    }

    visible(element) {
        if (!element?.isConnected || element.closest('[hidden], [inert], [aria-hidden="true"]')) return false;
        const style = getComputedStyle(element);
        return style.visibility !== 'hidden' && style.visibility !== 'collapse' &&
            style.display !== 'none' && element.getClientRects().length > 0;
    }

    collect() {
        const steps = new Map();
        document.querySelectorAll('[data-tour-step]').forEach((node) => {
            let step;
            try { step = JSON.parse(node.dataset.tourStep); } catch { return; }
            if (step.name !== this.nameValue || !step.id || !step.description) return;
            let element = node;
            if (step.selector) {
                // Explicit selectors may match responsive copies. Select a visible copy.
                try { element = [...document.querySelectorAll(step.selector)].find((target) => this.visible(target)); }
                catch { return; }
            }
            if (!this.visible(element) || steps.has(step.id)) return;
            steps.set(step.id, { ...step, element });
        });
        return [...steps.values()].sort((a, b) => a.order - b.order);
    }

    update() {
        const available = this.collect().length > 0;
        if (this.hasReplayTarget) this.replayTarget.hidden = !available;
        if (this.hasInvitationTarget) this.invitationTarget.hidden = !available || this.offerValue !== 'once' || this.dismissed;
        // If authorization/UI changes remove an active target, end safely. Replaying
        // collects a fresh set; we never open or click menu items to expose children.
        if (this.tour?.isActive() && this.activeTargets?.some((target) => !this.visible(target))) this.stop();
    }

    get storageKey() {
        return `survos-tour:${JSON.stringify([this.appValue, this.nameValue, this.versionValue])}`;
    }

    get dismissed() {
        if (this.dismissedInMemory === this.storageKey) return true;
        try { return localStorage.getItem(this.storageKey) === '1'; } catch { return false; }
    }

    dismiss(event) {
        this.dismissedInMemory = this.storageKey;
        try { localStorage.setItem(this.storageKey, '1'); } catch { /* Private/blocked storage is nonfatal. */ }
        if (this.hasInvitationTarget) this.invitationTarget.hidden = true;
        if (event && this.hasReplayTarget) this.replayTarget.focus({ preventScroll: true });
    }

    start(event) {
        const initiatingControl = event?.currentTarget instanceof HTMLElement ? event.currentTarget : document.activeElement;
        this.stop();
        const steps = this.collect();
        if (!steps.length) { this.update(); return; }
        this.returnFocus = initiatingControl;
        document.dispatchEvent(new CustomEvent('survos:tour-start', { detail: this }));
        this.activeTargets = steps.map((step) => step.element);
        this.dismiss();
        const labels = this.labelsValue;
        const escape = (text) => { const node = document.createElement('span'); node.textContent = text; return node.innerHTML; };
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.tour = driver({
            animate: !reduced,
            smoothScroll: false,
            allowClose: true,
            allowKeyboardControl: false,
            disableActiveInteraction: true,
            showProgress: true,
            showButtons: ['previous', 'next', 'close'],
            prevBtnText: escape(labels.back), nextBtnText: escape(labels.next), doneBtnText: escape(labels.done),
            progressText: escape(labels.progress),
            steps: steps.map((step) => ({
                element: step.element,
                popover: { title: escape(step.title), description: escape(step.description), side: step.placement },
            })),
            onPopoverRender: (popover) => {
                popover.closeButton.setAttribute('aria-label', labels.close);
                popover.wrapper.setAttribute('role', 'dialog');
                popover.wrapper.setAttribute('aria-modal', 'true');
                // Driver's focus trap includes the highlighted link. Keep keyboard
                // focus on the dialog so Enter cannot follow a navigation target.
                this.dialog = popover.wrapper;
                popover.nextButton.focus({ preventScroll: true });
            },
            // Driver can omit onDestroyed when dismissed before its first frame.
            // Route every normal ending through our idempotent cleanup as well.
            onDestroyStarted: () => this.stop(),
            onDestroyed: () => this.finish(),
        });
        this.keyGuard = (event) => {
            if (['ArrowRight', 'ArrowLeft', 'Escape'].includes(event.key)) {
                event.preventDefault(); event.stopImmediatePropagation();
                if (event.key === 'Escape') this.stop();
                else if (event.key === 'ArrowRight') this.tour?.moveNext();
                else if (this.tour?.hasPreviousStep()) this.tour.movePrevious();
                return;
            }
            if (event.key === 'Tab' && this.dialog) {
                const buttons = [...this.dialog.querySelectorAll('button')].filter((button) => !button.disabled && this.visible(button));
                const index = buttons.indexOf(document.activeElement);
                const next = event.shiftKey ? (index <= 0 ? buttons.length - 1 : index - 1) : (index + 1) % buttons.length;
                event.preventDefault(); event.stopImmediatePropagation(); buttons[next]?.focus();
            }
            if ((event.key === 'Enter' || event.key === ' ') && !this.dialog?.contains(event.target)) {
                event.preventDefault(); event.stopImmediatePropagation();
            }
        };
        document.addEventListener('keydown', this.keyGuard, true);
        this.tour.drive();
    }

    stop() {
        this.tour?.destroy();
        this.finish();
    }

    finish() {
        if (!this.tour && !this.keyGuard) return;
        this.tour = null;
        this.activeTargets = null;
        document.removeEventListener('keydown', this.keyGuard, true);
        this.keyGuard = null;
        this.dialog = null;
        const target = this.visible(this.returnFocus) ? this.returnFocus : (this.hasReplayTarget ? this.replayTarget : null);
        // Driver restores its own saved focus after its ending hook. Restore the
        // initiating control last, unless another tour has already started.
        queueMicrotask(() => {
            if (this.connected && !document.body.classList.contains('driver-active') && this.visible(target)) {
                target.focus({ preventScroll: true });
            }
        });
    }
}
