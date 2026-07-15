# Usage

## Install

```bash
composer require net-code/laravel-media
php artisan vendor:publish --tag=media-config   # optional
php artisan migrate
```

The service provider is auto-discovered. It ships the `files` migration and mounts the routes under
`config('media.route_prefix')`.

## Object storage (two S3 disks)

The package talks to your object store through Laravel filesystem disks — configure them in the host
`config/filesystems.php`:

- **`media.disk`** (default `s3`) — used for `exists`/`delete` of the stored object.
- **`media.presign_disk`** (default `s3_public`) — used to mint presigned **upload** and **download**
  URLs (`temporaryUploadUrl` / `temporaryUrl`); must be an `s3`-driver disk.

Both may point at the same bucket. The client PUTs the file bytes straight to S3 with the presigned
upload URL — the bytes never pass through the app.

## Config (`config/media.php`)

| Key | Default | Purpose |
|---|---|---|
| `route_prefix` | `files` | prefix for the shipped routes |
| `middleware` | `['api']` | middleware on the route group — **add your auth** (e.g. `auth:sanctum`) here |
| `upload_middleware` | `[]` | extra middleware on the initiate route (e.g. an idempotency middleware) |
| `disk` | `s3` | filesystem disk for object ops |
| `presign_disk` | `s3_public` | filesystem disk for presigned URLs |

## Endpoints

Inputs use spatie-Data (validated); every non-empty success response is a `{ "data": { … } }`
envelope (Laravel API resources) with snake_case fields. `204` responses have no body.

| Method | URI | Body → result |
|---|---|---|
| POST | `/{prefix}` | `{filename,mime,size}` → `201 {data:{file_id,upload_url}}` (presigned S3 PUT url) |
| POST | `/{prefix}/{fileId}/complete` | `{checksum?,size?}` → `204` (`409` if the object is not in storage or the file is not pending) |
| GET | `/{prefix}/{fileId}` | `{data:{id,original_name,mime,size,status,download_url}}` (`404` if unknown) |
| DELETE | `/{prefix}/{fileId}` | `204` (removes the object + record) |

Errors map to JSON: file not found → `404`, object-not-confirmed / not-pending → `409`.

Max upload size and URL TTL are domain policy (`UploadPolicy`: 100 MB, 15-minute URLs).

## Ports

**Inbound (consume from other modules):**
- `NetCode\Media\Contract\FileDirectory` — `snapshots(list<string> $ids): list<FileSnapshot>` and
  `areCompleted(list<string> $ids): bool`. Depend on this wherever another module references files by
  id (e.g. attachments); each `FileSnapshot` carries `{id, originalName, mime, size, downloadUrl}`.

**Outbound (the host provides):**
- `NetCode\Media\Application\Ports\CurrentUser` — `id(): string`, the uploader's subject id. **Bind it**
  to your authentication (e.g. bridge to `net-code/laravel-identity`'s `CurrentUser`). No default — auth
  is the host's concern.

**Internal (swappable; defaults wired):** `FileRepository` → Eloquent, `ObjectStorage` → `S3ObjectStorage`
(Laravel S3 disks), `FileDirectory` → `FileDirectoryAdapter`.

## Events

`FileUploadInitiated` and `FileUploadCompleted` are published via the domain event publisher.

## Console

`app:media:purge-uploads {--hours=24}` deletes uploads initiated but never completed; it is scheduled
daily at 03:00 by the service provider.
