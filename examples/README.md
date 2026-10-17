# Menu tour example

Adapt these files in an application with autowired services, Tabler menus, and the tour bundle installed. The `app_homepage` and `app_search` routes stand for existing application routes; replace them with yours. Keep the application's existing menu rendering and authorization.

| Example | Application destination |
| --- | --- |
| [PageMenu.php](PageMenu.php) | `src/Menu/PageMenu.php`, or merge the metadata into an existing listener |
| [survos_tour.yaml](survos_tour.yaml) | `config/packages/survos_tour.yaml` |
| [routes.en.yaml](routes.en.yaml) | Merge into `translations/routes.en.yaml` |
| [messages.en.yaml](messages.en.yaml) | Merge into `translations/messages.en.yaml` |
| [layout.html.twig](layout.html.twig) | Optional placement outside a menu; omit for normal PHP menu integration |
| [page.html.twig](page.html.twig) | Add an explicit step beside a non-menu control |

The listener uses the typed `TourStep` DTO. `order` controls sequence and `id` is stable across renders. The destination route supplies `.tour` copy in the `routes` domain. The route-less help link supplies its own copy key and domain. Nothing has to be collected separately from the menu.

The listener adds the replay control to `NAVBAR_MENU_END` in PHP, including expanded mobile navigation. Use any slot your layout already renders, or add it to an existing submenu; its usual responsive behavior still applies. No new Twig is needed for menu integration. The layout snippet is an optional alternative for pages placing a control outside menus; do not combine it with a menu control for the same named tour. The page snippet's explicit step joins the tour automatically; keep the control's real behavior in the application.

To share a typed tour definition with both PHP and Twig, define it once in your application service and expose it as a template variable:

```php
use Survos\TourBundle\Model\Tour;
use Survos\TourBundle\Model\TourStep;

$pageTour = new Tour('page', version: '2', offer: 'once');
$step = new TourStep($pageTour, 'search', order: 20);
// Supply $pageTour to the layout as pageTour through your normal Twig context.
```

```twig
<twig:Tour :tour="pageTour" />
```

Use a PHP menu control (responsive copies are supported) or one Twig widget per name. For another tour, use another name on its steps and widget. Set `instance` only when two occurrences of the same step represent distinct contexts; responsive copies should retain the same identity so the browser selects one visible target.

English copy is example text for the host application to review and translate. Configure Symfony's locale fallbacks normally. Do not enable developer-oriented RouteMeta descriptions as public copy without reviewing them. See the [bundle README](../README.md) for copy precedence, compatibility, lifecycle, and validation commands.
