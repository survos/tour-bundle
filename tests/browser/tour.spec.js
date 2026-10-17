import { test, expect } from '@playwright/test';

const widget = (page) => page.locator('#widgets [data-controller="survos--tour-bundle--tour"]');
const replay = (page) => widget(page).getByRole('button', { name: 'Explain this page', exact: true });
const title = (page) => page.locator('.driver-popover-title');
const count = (page) => page.locator('.driver-popover-progress-text');

for (const mode of ['survos', 'knp']) {
    test(`${mode}: PHP menu replay needs no widget, responsive copies and invitation cleanup`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.goto(`/?mode=${mode}&menuControl=1`);
        const menuReplay = page.locator('.desktop [data-tour-replay]');
        await expect(menuReplay).toBeVisible();
        await expect(page.locator('#widgets')).toHaveCount(0);
        await expect(page.locator('[data-tour-invitation]')).toHaveCount(1);
        await page.locator('[data-tour-invitation]').getByRole('button', { name: 'Not now' }).click();
        await expect(page.locator('[data-tour-invitation]')).toHaveCount(0);
        const url = page.url();
        await menuReplay.focus();
        await page.keyboard.press('Space');
        await expect(title(page)).toHaveText('Newspapers');
        await page.keyboard.press('Escape');
        await expect(menuReplay).toBeFocused();
        expect(page.url()).toBe(url);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.getByRole('button', { name: 'Navigation', exact: true }).click();
        const mobileReplay = page.locator('.mobile [data-tour-replay]');
        await mobileReplay.click();
        await expect(title(page)).toHaveText('Newspapers');
        await expect(count(page)).toHaveText('1 of 4');
        await page.keyboard.press('Escape');
        await expect(mobileReplay).toBeFocused();
        // A menu item can disconnect/reconnect without leaving a panel or listeners.
        await page.evaluate(() => {
            const node = document.querySelector('.mobile [data-tour-replay]');
            const control = window.fixtureApplication.getControllerForElementAndIdentifier(node, node.dataset.controller);
            control.disconnect(); control.connect();
        });
        await mobileReplay.click();
        await expect(title(page)).toBeVisible();
        await page.keyboard.press('Escape');
        await page.goto(`/?mode=${mode}&menuControl=1&empty=1`);
        await expect(page.locator('[data-tour-replay]:visible')).toHaveCount(0);
        await expect(page.locator('[data-tour-invitation]')).toHaveCount(0);
        expect(errors).toEqual([]);
    });

    test(`${mode}: invitation, keyboard, escaped text, replay, dismissal and reconnect`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.goto(`/?mode=${mode}`);
        await expect(widget(page).getByRole('button', { name: 'Start', exact: true })).toBeVisible();
        await widget(page).getByRole('button', { name: 'Not now', exact: true }).click();
        await expect(widget(page).getByRole('button', { name: 'Start', exact: true })).toBeHidden();
        await replay(page).click();
        await expect(title(page)).toHaveText('Newspapers');
        await expect(count(page)).toHaveText('1 of 4');
        await page.screenshot({ path: `../../../../var/tour-fixture/tour-${mode}-desktop.png` }); // closed dropdown children & responsive duplicate omitted
        await page.keyboard.press('ArrowRight');
        await expect(title(page)).toHaveText('Search text');
        await page.keyboard.press('ArrowLeft');
        await expect(title(page)).toHaveText('Newspapers');
        for (let i = 0; i < 6; i++) {
            await page.keyboard.press('Tab');
            await expect(page.locator('.driver-popover :focus')).toHaveCount(1);
        }
        const url = page.url();
        await page.keyboard.press('Escape');
        await expect(title(page)).toHaveCount(0);
        await expect(replay(page)).toBeFocused();
        expect(page.url()).toBe(url);
        await page.reload();
        await expect(replay(page)).toBeVisible();
        await expect(widget(page).getByRole('button', { name: 'Start', exact: true })).toBeHidden();
        await replay(page).click();
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(title(page)).toHaveText('Scan and text');
        await page.getByRole('button', { name: 'Done', exact: true }).click();
        await expect(title(page)).toHaveCount(0);
        // Disconnect while active, then reconnect the same widget.
        await replay(page).click();
        await page.evaluate(() => { const element = document.querySelector('#widgets'); window.detachedWidget = element; element.remove(); });
        await expect(title(page)).toHaveCount(0);
        await page.evaluate(() => document.body.append(window.detachedWidget));
        await replay(page).click();
        await expect(title(page)).toHaveCount(1);
        await page.getByRole('button', { name: 'Close', exact: true }).click();
        await expect(replay(page)).toBeFocused();
        // Removing a future target must not leave a dead step in the snapshot.
        await replay(page).click();
        await page.evaluate(() => document.querySelector('#scan').remove());
        await expect(title(page)).toHaveCount(0);
        await replay(page).click();
        await expect(count(page)).toHaveText('1 of 3');
        await page.keyboard.press('Escape');
        // Copy is text, even though Driver accepts HTML.
        await page.evaluate(() => {
            const element = document.querySelector('.desktop [data-tour-step]');
            const data = JSON.parse(element.dataset.tourStep);
            data.description = '<img src=x onerror="window.injected=true">';
            element.dataset.tourStep = JSON.stringify(data);
        });
        await replay(page).click();
        await expect(page.locator('.driver-popover-description')).toHaveText('<img src=x onerror="window.injected=true">');
        await expect(page.locator('.driver-popover-description img')).toHaveCount(0);
        expect(errors).toEqual([]);
    });

    test(`${mode}: mobile visibility, dropdown policy, no navigation, dynamic targets`, async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`/?mode=${mode}&role=admin`);
        await replay(page).click();
        await expect(title(page)).toHaveText('Scan and text');
        await expect(count(page)).toHaveText('1 of 1');
        await page.keyboard.press('Escape');
        await page.getByRole('button', { name: 'Navigation', exact: true }).click();
        await replay(page).click();
        await expect(title(page)).toHaveText('Newspapers');
        await expect(count(page)).toHaveText('1 of 6');
        await page.screenshot({ path: `../../../../var/tour-fixture/tour-${mode}-mobile.png` });
        const url = page.url();
        await page.keyboard.press('Enter'); // advances focused dialog button
        expect(page.url()).toBe(url);
        await page.keyboard.press('Escape');
        await page.locator('.mobile .dropdown-toggle').click();
        await replay(page).click();
        await expect(count(page)).toHaveText('1 of 7'); // now issue dates is visible
        await page.keyboard.press('Escape');
        // Layout shrinks/closes nav while the tour is running: stop safely.
        await replay(page).click();
        await page.evaluate(() => document.querySelector('.mobile nav').classList.remove('open'));
        await expect(title(page)).toHaveCount(0);
        await replay(page).click();
        await expect(title(page)).toHaveText('Scan and text');
    });
}

test('storage failures, versioning, multiple tours, empty and missing explicit targets', async ({ page }) => {
    await page.addInitScript(() => {
        Storage.prototype.getItem = () => { throw new Error('blocked'); };
        Storage.prototype.setItem = () => { throw new Error('blocked'); };
    });
    await page.goto('/');
    await widget(page).getByRole('button', { name: 'Not now', exact: true }).click();
    await replay(page).click();
    await expect(title(page)).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(widget(page).getByRole('button', { name: 'Start', exact: true })).toBeHidden();
    await page.goto('/?empty=1');
    await expect(page.getByRole('button', { name: 'Explain this page', exact: true })).toHaveCount(0);
});

test('versioned storage and independent named tours', async ({ page }) => {
    await page.goto('/');
    await widget(page).getByRole('button', { name: 'Not now', exact: true }).click();
    await page.evaluate(() => {
        const node = document.querySelector('.desktop [data-tour-step]');
        const data = JSON.parse(node.dataset.tourStep);
        data.name = 'other'; node.dataset.tourStep = JSON.stringify(data);
    });
    const other = page.locator('[data-controller="survos--tour-bundle--tour"]').nth(1);
    await other.getByRole('button', { name: 'Explain this page', exact: true }).click();
    await expect(title(page)).toHaveText('Newspapers');
    await page.evaluate(() => {
        const first = document.querySelector('#widgets [data-controller]');
        first.setAttribute('data-survos--tour-bundle--tour-version-value', '2');
        const controller = window.fixtureApplication.getControllerForElementAndIdentifier(first, 'survos--tour-bundle--tour');
        controller.disconnect(); controller.connect();
    });
    await expect(widget(page).getByRole('button', { name: 'Start', exact: true })).toBeVisible();
    // Programmatic start while another tour owns the modal overlay.
    await widget(page).getByRole('button', { name: 'Start', exact: true }).dispatchEvent('click');
    await expect(title(page)).toHaveText('Search text');
    await page.keyboard.press('Escape');
    await expect(title(page)).toHaveCount(0);
    await expect(replay(page)).toBeFocused();
});
