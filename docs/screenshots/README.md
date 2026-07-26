# Screenshots

Images used by the main [README](../../README.md).

| File | Shows | Captured from |
|------|-------|---------------|
| `client-overview.png` | Client panel, Overview tab — hero, tabs, specs, access details, bandwidth | Client area → service → Product Details |
| `client-graphs.png` | Client panel, Graphs tab — CPU and network charts at 24 hours | same page, Graphs tab |
| `client-rebuild.png` | Client panel, Rebuild tab — searchable image picker | same page, Rebuild tab |
| `admin-service.png` | Admin service page — module commands, facts strip and the panel | Clients → service → Hetzner Cloud |
| `admin-graphs.png` | Admin service page, Graphs tab | same page |
| `addon-products.png` | Addon Products tab — per-product feature matrix and limits | Addons → Hetzner Cloud → Products |
| `addon-create.png` | Addon Create products tab — plan table with live pricing | Addons → Hetzner Cloud → Create products |

## Replacing or adding one

Keep the file names — the README links to them directly. Use lowercase `.png`: GitHub serves paths
case-sensitively, so `Foo.PNG` returns 404 against a link written as `foo.png`.

Blank out any real IP address, hostname, server id or password before committing. Light theme and
roughly 1400 px wide match the existing set.

Worth adding if you extend the set: the Network tab (floating IPs, reverse DNS, private networks),
the Firewall rule editor, and the addon Status tab.
