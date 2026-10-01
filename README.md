# net-code/laravel-media

Reusable **presigned-S3 file uploads** for Laravel — an *initiate → complete* lifecycle over an
object store, temporary download URLs, and a `FileDirectory` seam so other modules can reference
files by id. Built with hexagonal ports & adapters.

```bash
composer require net-code/laravel-media
php artisan migrate
```

- **`docs/usage.md`** — install, config, ports, wiring.
- **`docs/flows.md`** — the upload lifecycle end to end.

## v1 scope

Presigned S3 upload (initiate → complete), read + delete, temporary download URLs, the
`FileDirectory` contract (`snapshots` + `areCompleted`) for cross-module reference, and a daily purge
of uploads that were never completed or were rejected. Authentication is host-wired via the
`CurrentUser` port; access to a file via the `FileAccess` port (default: the uploader only); storage
via the `ObjectStorage` port (default: Laravel S3 disks). Upgrading from 0.2: see `docs/usage.md`.

## License

MIT
