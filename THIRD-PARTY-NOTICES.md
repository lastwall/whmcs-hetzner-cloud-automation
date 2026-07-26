# Third-party notices

This project is MIT licensed (see [LICENSE](LICENSE)). It **redistributes** the components below,
each of which keeps its own licence. Nothing here is relicensed by inclusion.

---

## Redistributed in this repository

### noVNC — Mozilla Public License 2.0

- **Path:** `modules/servers/hetznercloud/noVNC/`
- **Version:** 1.0.0
- **Upstream:** https://github.com/novnc/noVNC
- **Licence text:** [`modules/servers/hetznercloud/noVNC/LICENSE.txt`](modules/servers/hetznercloud/noVNC/LICENSE.txt)
- Copyright (C) 2012 Joel Martin; Copyright (C) 2018 Samuel Mannehed for Cendio AB.
  TIGHT decoder portion (c) 2012 Michael Tinglof, Joe Balaz, Les Piech (Mercuri.ca).

MPL-2.0 is a **file-level copyleft** licence. Modifying any file inside `noVNC/` obliges you to make
that file's source available under MPL-2.0. Combining it with MIT-licensed code in the same project,
as this module does, is explicitly permitted — the two licences are compatible and only the noVNC
files themselves carry the copyleft.

noVNC bundles its own dependencies, redistributed with it:

| Component | Path | Licence |
|---|---|---|
| pako | `noVNC/vendor/pako/` | MIT — [`LICENSE`](modules/servers/hetznercloud/noVNC/vendor/pako/LICENSE) |
| promise-polyfill | `noVNC/vendor/promise.js` | MIT — © 2014 Taylor Hakes, © 2014 Forbes Lindesay (header in file) |
| Sinon.JS 4.0.0 | `noVNC/vendor/sinon.js` | BSD-3-Clause (header in file) — test dependency only |
| browser-es-module-loader | `noVNC/vendor/browser-es-module-loader/` | MIT |
| Orbitron (font) | `noVNC/app/styles/Orbitron700.{ttf,woff}` | SIL Open Font License 1.1 — © The Orbitron Project Authors |

---

## Loaded at runtime, not redistributed

### Chart.js — MIT

- **Version:** 4.4.1, loaded from `cdn.jsdelivr.net` by the client panel
- **Upstream:** https://github.com/chartjs/Chart.js

No Chart.js source is stored in this repository. If you self-host it to avoid the CDN, include its
MIT licence alongside the file you ship.

---

## Trademarks

**Hetzner** and **Hetzner Cloud** are trademarks of Hetzner Online GmbH. This project is an
independent, unofficial integration and is **not affiliated with, endorsed by, or supported by**
Hetzner Online GmbH. **WHMCS** is a trademark of WHMCS Ltd, likewise unaffiliated.

---

## If you redistribute this module

1. Keep [`LICENSE`](LICENSE), this file, and every bundled licence text in place.
2. Keep the copyright headers inside `noVNC/`.
3. If you modify a file under `noVNC/`, publish that file's source under MPL-2.0.
