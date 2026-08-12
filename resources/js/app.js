import { animate } from 'motion';

/*
| Animation
|
| Motion One drives everything that happens once — things arriving, leaving, or
| reacting. The perpetual background drift is CSS, because it should not need a
| main-thread frame every 16ms for as long as the tab is open.
|
| Markup reaches this through the `$dream` Alpine magic, e.g.
|
|     <li x-data x-init="$dream.enter($el)">
*/

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

const SOFT = [0.22, 1, 0.36, 1];

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Motion rejects `finished` when an animation is interrupted — deleting a row
 * that is still sliding in, say. That is a normal thing to do here, not an error.
 */
const done = (controls) => controls.finished.catch(() => {});

/*
| `enter` has two jobs and has to tell them apart on its own, because the markup
| that calls it is the same either way.
|
| Every element that wants an entrance is collected for one frame. A whole batch
| means a page was just rendered, so they cascade. A batch of one, on a page that
| has already settled, means a row was just created, so it unfolds and pushes the
| rest of the list down.
*/
let settled = false;
let pending = [];
let pendingIsInsert = false;

/*
| Reordering a keyed list makes Livewire's morph move rows rather than rewrite
| them, and a moved element is torn out and re-inserted, so x-init runs again on
| rows that are not new at all. Remembering which keys have already had their
| entrance is what separates "just added" from "just shuffled up one place".
*/
const seenKeys = new Set();
const seenElements = new WeakSet();

const unsettle = () => {
    settled = false;
    seenKeys.clear();
};

const settle = () => {
    settled = true;
};

/** True the first time this element is offered, false every time after. */
function isNew(el) {
    const key = el.getAttribute('wire:key');
    const seen = key === null ? seenElements : seenKeys;
    const id = key === null ? el : key;

    if (seen.has(id)) {
        return false;
    }

    seen.add(id);

    return true;
}

/**
 * Motion leaves the last frame behind as an inline style. Clearing it hands the
 * element back to the stylesheet, which owns the hover lift on these cards.
 */
function release(el) {
    el.style.opacity = '';
    el.style.transform = '';
}

function flush() {
    const batch = pending;
    pending = [];

    if (pendingIsInsert && batch.length === 1) {
        unfold(batch[0]);

        return;
    }

    batch.forEach(cascade);
}

/** One of many: rise into place, a beat after the element above. */
function cascade(el, index) {
    done(animate(
        el,
        { opacity: [0, 1], y: [14, 0], scale: [0.985, 1] },
        { duration: 0.55, delay: Math.min(index, 12) * 0.06, ease: SOFT },
    )).then(() => release(el));
}

/** Brand new: open a gap for it, then slide in from above. */
function unfold(el) {
    const height = el.offsetHeight;

    el.style.overflow = 'hidden';

    done(animate(
        el,
        { height: ['0px', `${height}px`], opacity: [0, 1], y: [-14, 0], scale: [0.96, 1] },
        { duration: 0.6, ease: SOFT },
    )).then(() => {
        el.style.height = '';
        el.style.overflow = '';
        release(el);
    });
}

let toastRun = 0;

const dream = {
    /** Entrance for a card, a row, or anything else that just appeared. */
    enter(el) {
        if (reduced.matches || ! isNew(el)) {
            return;
        }

        // Hide it in the same tick it was inserted, so nothing flashes before the frame lands.
        el.style.opacity = '0';

        if (pending.push(el) === 1) {
            // Read now rather than in the frame that flushes: by then the page has settled.
            pendingIsInsert = settled;
            requestAnimationFrame(flush);
        }
    },

    /**
     * Exit for a row that is about to be deleted: slide out, then collapse the
     * space it was taking up. Awaited, so the server call lands after the gap closes.
     */
    async leave(el) {
        if (reduced.matches) {
            return;
        }

        const height = el.offsetHeight;

        el.style.overflow = 'hidden';

        await done(animate(el, { opacity: 0, x: 44, scale: 0.96 }, { duration: 0.28, ease: 'easeIn' }));

        await done(animate(
            el,
            { height: [`${height}px`, '0px'], marginTop: '0px' },
            { duration: 0.26, ease: 'easeIn' },
        ));
    },

    /** A small confirmation that springs up from the bottom and sees itself out. */
    async toast(el, hold = 2600) {
        const run = ++toastRun;

        animate(
            el,
            { opacity: 1, y: [18, 0], scale: [0.94, 1] },
            reduced.matches ? { duration: 0 } : { type: 'spring', stiffness: 340, damping: 24 },
        );

        await wait(hold);

        if (run !== toastRun) {
            return;
        }

        animate(el, { opacity: 0, y: 10, scale: 0.97 }, { duration: 0.35, ease: 'easeIn' });
    },

    /** A quick pop for something small that appeared in place, like a validation message. */
    pop(el) {
        if (reduced.matches) {
            return;
        }

        done(animate(
            el,
            { opacity: [0, 1], y: [-5, 0], scale: [0.94, 1] },
            { type: 'spring', stiffness: 520, damping: 22 },
        )).then(() => release(el));
    },
};

document.addEventListener('alpine:init', () => window.Alpine.magic('dream', () => dream));

// A page counts as settled once its markup has been through Alpine, because from then
// on anything new can only have come from the user. wire:navigate swaps the DOM
// without a reload, so each page it loads gets its own unsettled period.
document.addEventListener('alpine:initialized', settle);
document.addEventListener('livewire:navigate', unsettle);
document.addEventListener('livewire:navigating', unsettle);
document.addEventListener('livewire:navigated', settle);

/*
| Theme
|
| The initial class is applied by an inline script in the layout head (before
| first paint, so there is no flash); this only handles clicks and re-syncs the
| buttons after Livewire swaps the DOM.
*/

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-option]');

    if (! button) {
        return;
    }

    localStorage.setItem('theme', button.dataset.themeOption);
    window.applyTheme();
});

document.addEventListener('DOMContentLoaded', () => window.applyTheme());
document.addEventListener('livewire:navigated', () => window.applyTheme());
