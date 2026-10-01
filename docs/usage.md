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

Complete, read and delete check access first: a file the current user may not access answers `404`, the same as an unknown id, so ids cannot be probed.

Max upload size and URL TTL are domain policy (`UploadPolicy`: 100 MB, 15-minute URLs).

## Ports

**Inbound (consume from other modules):**
- `NetCode\Media\Api\Contracts\FileDirectory` — `snapshots(list<string> $ids, string $requestedBy): list<FileSnapshot>`
  and `areCompleted(list<string> $ids, string $requestedBy): bool`. Files the requester may not access
  are left out of `snapshots` and make `areCompleted` false, the same as unknown ids. Depend on this wherever another module references files by
  id (e.g. attachments); each `FileSnapshot` carries `{id, originalName, mime, size, downloadUrl}`.

**Outbound (the host provides):**
- `NetCode\Media\Application\Ports\CurrentUser` — `id(): string`, the uploader's subject id. **Bind it**
  to your authentication (e.g. bridge to `net-code/laravel-identity`'s `CurrentUser`). No default — auth
  is the host's concern.
- `NetCode\Media\Application\Ports\FileAccess` — `allows(string $requestedBy, string $fileId, ?string $uploadedBy): bool`,
  asked before every complete, read and delete, and by `FileDirectory`. The file id lets the host decide
  from its own records (for example the household that owns a document). The default `UploaderOnlyFileAccess` allows the uploader
  only. Bind your own to share files, for example with every member of the uploader's team or household.

**Internal (swappable; defaults wired):** `FileRepository` → Eloquent, `ObjectStorage` → `S3ObjectStorage`
(Laravel S3 disks), `FileDirectory` → `FileDirectoryAdapter`.

## Events

`FileUploadInitiated` and `FileUploadCompleted` are published via the domain event publisher.

## Console

`media:purge-uploads {--hours=24}` deletes uploads initiated but never completed; it is scheduled
daily at 03:00 by the service provider.

## Upgrading from 0.2 to 0.3

0.3 closes access to other users' files. Before, any caller that knew a file id could complete, read or
delete it.

- `CompleteUpload`, `GetFile` and `DeleteFile` take a required `requestedBy` (the current user's id).
  The shipped controllers pass `CurrentUser::id()`.
- `FileDirectory::snapshots()` and `areCompleted()` take a required `requestedBy`.
- `FileAccess` decides who may touch a file. The default `UploaderOnlyFileAccess` allows the uploader
  only, so after the upgrade other users get `404` even when your code did not change. Bind your own
  `FileAccess` in a service provider of the host if files are shared:

  ```php
  $this->app->bind(FileAccess::class, HouseholdFileAccess::class);
  ```

- Files with `uploaded_by = null` are refused by the default rule. A custom `FileAccess` decides what
  to do with them; refusing is the safe default.

