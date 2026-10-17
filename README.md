# Tour Bundle

Reusable, translated tours of rendered menus and explicit page controls. Driver.js is the initial renderer; application code deals with tours and steps. Requires PHP 8.5, Symfony 8.1, kit-bundle, TwigComponent and Stimulus with AssetMapper. Tabler, menu-bundle and field-bundle are optional integrations.

## Install and configure

After the coordinated mono changes are released, install `survos/tour-bundle` through the application's configured Satis repository. For local development use the existing `monolink` workflow; do not copy bundles into vendor or add Composer repository overrides. This implementation does not publish packages.

Flex must register `Survos\TourBundle\SurvosTourBundle`, the required kit bundle, and the usual TwigComponent/Stimulus bundles. Its UX recipe reads `assets/package.json`, registers `@survos/tour-bundle` in `assets/controllers.json`, and adds `driver.js` and `driver.js/dist/driver.css` to `importmap.php`. The CSS autoimport is required. Keep the application's standard Stimulus bootstrap. No app tour controller is needed.

```yaml
# config/packages/survos_tour.yaml
survos_tour:
    app_id: ink                    # unique for applications sharing an origin
    translation_domain: messages # explicit and legacy tour keys
    route_meta_fallback: false    # see public-copy policy below
```

## PHP menu metadata + one layout widget

In an existing MenuSlot listener using Tabler's `MenuBuilderTrait`:

```php
use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;
use Survos\TablerBundle\Menu\MenuBuilderTrait;

#[AsMenuListener(MenuSlot::PAGE_NAV)]
public function pageNav(MenuEvent $event): void
{
    $this->add($event->menu, 'paper_search', label: 'navigation.newspapers',
        tour: ['name' => 'page', 'id' => 'paper-search', 'order' => 20]);
}
```

```twig
{# Once in the layout, alongside the menus already rendered there. #}
<twig:Tour name="page" offer="once" version="1" />
```

```yaml
# translations/routes.en.yaml
paper_search.tour: Find newspapers by catalogue metadata, including title and place.
```

This is the normal consumption API. Menus remain the source of navigation. Tour metadata is attached only after `add()` passes its condition, route existence and access checks. A skipped call returns the parent as before; passing `tour:` is safe, while blindly chaining `setExtra('tour', ...)` is not. Destination route names are preserved in the `tourRoute` extra, independently of random Knp item names and generated URLs.

Route-less actions supply explicit copy (and their existing URI/action):

```php
$this->add($menu, uri: '#text', label: 'Search text', tour: [
    'name' => 'page', 'id' => 'text-search',
    'contentKey' => 'navigation.text_search_help', 'domain' => 'messages',
    'parameters' => ['%paper%' => $paperName], 'order' => 30,
]);
$this->addSubmenu($menu, 'More', tour: [
    'id' => 'more', 'content' => 'More ways to explore this paper.',
]);
```

With menu-bundle alone, use ordinary Knp extras on an **actually created and authorized** item:

```php
$item->setExtra('tour', ['name' => 'page', 'id' => 'paper-search']);
$item->setExtra('tourRoute', 'paper_search');
```

Both Tabler `menu_renderer: survos` and `menu_renderer: knp` are supported. Knp mode also works when SurvosMenuBundle is not registered: Tabler installs the same generic link decoration hook. These changes to menu-bundle, Tabler and tour-bundle must be consumed together (minimum coordinated version 2.42.23); older Tabler versions do not resolve tour extras. Custom legacy templates must call `menu_link_attributes(item)` where they render a link, and escape attribute values. Composable themes already receive decorated `item.linkAttributes`.

## Explicit controls

For controls that are not menu items, add a descriptor near the control. It joins the same named tour:

```twig
<button id="scan-text-toggle" type="button">Scan / Text</button>
<twig:Tour:Step name="page" id="scan-text" selector="#scan-text-toggle"
    title="Scan and text" contentKey="textsheet.switch_help"
    domain="messages" :order="50" />
```

`Tour:Step` accepts `name`, required `id` and `selector`, `title`/`titleKey`, `content`/`contentKey`, `domain`, `parameters`, `order`, and `placement` (`top`, `right`, `bottom`, `left`, `over`). `domain` defaults to the configured domain. Missing selectors and selectors matching only hidden elements are harmless. A selector matching responsive copies uses the first visible match. Descriptor markup is inert and hidden.

## Copy resolution and compatibility

Title precedence is explicit `title` (literal) or `titleKey` (translated), then the menu's translated label. A missing title translation falls back to the label. Description precedence:

1. Explicit `content` (literal) or `contentKey` in `domain`, with `parameters`.
2. `<route_name>.tour` in `routes`.
3. `<route_name>.info` in `routes`.
4. `tour.<step-id>.content` in the configured/per-step domain.
5. Compiled `RouteMetaRegistry::get(routeName)->description`, **only** with `route_meta_fallback: true`.

Catalogue lookup includes Symfony locale fallbacks; a missing key is never used as explanatory prose. Missing/empty copy skips the step and emits a debug log with step ID and route. Applications own the public suitability of `.info` translations. RouteMeta descriptions are developer-oriented English: review them before enabling that fallback; it is not translated automatically. No runtime controller reflection is used.

Tabler's existing `tourOptions()` helper is deliberately preserved. Its `tour` key aliases `name`, `placement` is retained, and its default `title: tour.<id>.title` / `content: tour.<id>.content` are resolved as translation keys. Missing legacy keys fall through rather than appearing as prose. The helper never established a domain: the configured/per-step domain now supplies it. To join a page tour, use `tourOptions('search', {tour: 'page'})`, or use the direct metadata shown above. New explicit translation keys should use `titleKey`/`contentKey`; literal strings may otherwise contain punctuation freely.

Ordinary copy is text. Attributes are escaped server-side and Driver popovers escape text client-side; there is no trusted HTML boundary.

## Identity, visibility and lifecycle

- Every step needs a stable explicit `id`; neither routes nor random Knp names supply it. Menu identities are `<root-slot>[:<instance>]:<id>`. Set `instance` in metadata when the same slot/step represents distinct contexts. Use the same identity for desktop/mobile copies so only the first visible copy is included. Keep explicit step IDs distinct from scoped menu IDs.
- One widget per named tour. Different names are independent; starting one closes the previous overlay. Numeric `order` sorts ascending, with rendered DOM order breaking ties.
- The first-visit UI is an inline invitation with Start / Not now. It never opens a modal automatically. `offer="never"` (default) shows only the permanent “Explain this page” replay control. No eligible targets means no invitation or replay control.
- Starting or declining dismisses that invitation for the application/name/version. Replay always remains available. Storage failures fall back to the current controller's memory. Bump `version` when a revised tour should invite visitors again.
- Closed dropdown and collapsed mobile children are skipped. Annotate their visible parent explicitly. The tour never opens, closes, clicks or navigates a menu item. Opening a menu normally makes its children eligible on the next replay. If any captured target disappears or becomes hidden during a tour, it closes safely; replay collects a fresh list.
- Back / Next / Done, progress, Escape / Close, arrow keys and dialog-only Tab cycling are supported. Focus returns to the initiating control, or replay if it is no longer visible. Highlighted navigation cannot be activated during the tour. Reduced motion disables Driver animation and smooth scrolling.
- Disconnect destroys the overlay and removes observers/listeners; reconnect rediscovers the live DOM. The controller uses DOM events, not a persistent global Driver singleton.

## Extension boundary

Menu-bundle exposes `LinkAttributesDecoratorInterface::decorate(ItemInterface $item, string $translatedLabel, array $renderOptions): array`. Tag services `survos_menu.link_attributes_decorator`. Composable rendering calls it after filtering; legacy templates call the shared `menu_link_attributes()` function only for items they render. The tour adapter returns an escaped-at-render-time `data-tour-step` descriptor on the existing target. It creates no second menu registry and adds no Driver dependency to menu-bundle. Visibility checks in JavaScript are presentation checks, not authorization; listeners/builders must enforce access before rendering any sensitive item or copy.

## Validation fixture (from mono root)

```bash
vendor/bin/phpunit -c bu/tour-bundle/phpunit.dist.xml
vendor/bin/phpunit --bootstrap bu/menu-bundle/tests/bootstrap.php bu/menu-bundle/tests
vendor/bin/phpunit --bootstrap bu/tour-bundle/tests/bootstrap.php bu/tabler-bundle/tests/Menu
npm ci --prefix bu/tour-bundle/tests/browser
# Install Playwright Chromium once, or use an existing Chrome with PW_CHANNEL=chrome.
cd bu/tour-bundle/tests/browser
npx playwright install chromium
npm test
```

The test-only kernel exercises real bundle registration, container compilation, named component rendering, UX controller discovery, CSS autoimport metadata, asset mapping, anonymous/user/admin menus, locale fallback, denied/skipped builder safety, route-less actions, scoped identities, and a full legacy Tabler kernel without SurvosMenuBundle. A standalone kernel renders the explicit components without either menu bundle or Tabler. Runtime Composer metadata in the fixture simulates installation without vendor edits or repository overrides.

Playwright bundles the **same production controller** with real Driver.js/Stimulus and serves the PHP-rendered fixture; its small fixture script only operates ordinary mobile/dropdown controls and exposes Stimulus for lifecycle assertions. The temporary server stops with the tests. Scenarios cover both renderers, keyboard/focus, replay/dismissal, responsive duplicates, closed/open menus, explicit steps, safe text, empty tours, storage errors, independent tours, versions, and disconnect/reconnect. This fixture uses representative menu markup and small presentation CSS; it does not certify every application's custom theme, Bootstrap behavior or assistive technology.

Ink is not changed by this bundle. Its follow-on should distinguish catalogue metadata, article-text search, issue dates, TextSheet and OCR source blocks. Partial/unreviewed article qualifications belong in normal result UI as well as tour copy.
