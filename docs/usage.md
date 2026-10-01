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

- **`media.disk`** (default `s3`) — used to move, inspect (size and the first bytes) and delete the stored object.
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
| `max_upload_bytes` | `104857600` | largest accepted file, checked on initiate (declared size) and on complete (stored size) |
| `allowed_types` | `[]` | declared MIME type => MIME types accepted from the stored bytes; empty accepts any type |

## Endpoints

Inputs use spatie-Data (validated); every non-empty success response is a `{ "data": { … } }`
envelope (Laravel API resources) with snake_case fields. `204` responses have no body.

| Method | URI | Body → result |
|---|---|---|
| POST | `/{prefix}` | `{filename,mime,size}` → `201 {data:{file_id,upload_url}}` (presigned S3 PUT url) |
| POST | `/{prefix}/{fileId}/complete` | `{checksum?}` → `204` (`409` if the object is not in storage or the file is not pending; `422 {message, code}` if the stored file breaks the limits) |
| GET | `/{prefix}/{fileId}` | `{data:{id,original_name,mime,size,status,download_url}}` (`404` if unknown; `download_url` is `null` unless the file is `completed`) |
| DELETE | `/{prefix}/{fileId}` | `204` (removes the object + record) |

Errors map to JSON: file not found → `404`, object-not-confirmed / not-pending → `409`.

Complete, read and delete check access first: a file the current user may not access answers `404`, the same as an unknown id, so ids cannot be probed.

The URL TTL is domain policy (`UploadPolicy`: 15 minutes). Size and type limits come from config.

The presigned PUT URL does not bind the size, so the client can store more than it declared, and it
stays valid for 15 minutes. On complete the package first moves the object on the server from
`uploads/{id}/…` to `files/{id}/…`, a key no presigned URL covers, so a later PUT to the same URL
cannot replace an accepted file. It then reads the real size from storage and the type from the
first 4 KB of the moved object (`finfo`). A file over `max_upload_bytes`, or with a detected type that `allowed_types` does not list
for the declared type, is deleted from storage, marked `failed`, and answers `422` with the code
`file.too_large` or `file.type_mismatch`. CSV files are often detected as `text/plain`, so list both:

```php
'allowed_types' => [
    'application/pdf' => ['application/pdf'],
    'image/jpeg' => ['image/jpeg'],
    'text/csv' => ['text/csv', 'text/plain'],
],
```

If storage cannot read or delete an object, the request answers `503`; complete can be retried.

### Remaining risk and bucket setup

- The bytes land in the bucket before the check. One PUT can store up to 5 GB until complete runs.
- The type check reads the first 4 KB only. A file that starts like a PDF passes even if the rest is
  something else; treat accepted files as untrusted input.
- A PUT to the upload URL after complete leaves an object under `uploads/` that no record points to.
  Add a lifecycle rule that expires the `uploads/` prefix after one day, but only when no completed
  file still lives there (see the upgrade notes).
- In a versioned bucket `delete` only adds a delete marker. Add a `NoncurrentVersionExpiration` rule,
  or rejected files keep costing storage.

## Ports

**Inbound (consume from other modules):**
- `NetCode\Media\Api\Contracts\FileDirectory` — `snapshots(list<string> $ids, string $requestedBy): list<FileSnapshot>`
  and `areCompleted(list<string> $ids, string $requestedBy): bool`. Files the requester may not access
  are left out of `snapshots` and make `areCompleted` false, the same as unknown ids. Depend on this wherever another module references files by
  id (e.g. attachments); each `FileSnapshot` carries `{id, originalName, mime, size, downloadUrl}`; `downloadUrl` is `null` unless the
  file is completed.

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

`FileUploadInitiated`, `FileUploadCompleted` and `FileUploadRejected` (with the `UploadRejection` reason)
are published via the domain event publisher.

## Console

`media:purge-uploads {--hours=24}` deletes uploads that were never completed or were rejected (`pending`
and `failed`), both their record and their object under `uploads/` and `files/`; it is scheduled
daily at 03:00 by the service provider.

## Upgrading from 0.2 to 0.3

**Warning:** files completed before 0.3 keep their key under `uploads/`; only uploads completed
after the upgrade move to `files/`. Do not add a lifecycle rule that expires `uploads/` while this
query returns rows, or S3 deletes those files:

```sql
select id, key from files where status = 'completed' and key like 'uploads/%';
```

Move those objects to `files/{id}/{name}` and update `files.key` first.

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

- `ObjectStorage::exists()` is replaced by `inspect(): ?StoredObject` (size and detected MIME type).
  Custom storage adapters must implement it.
- `ObjectStorage` gets `move(string $from, string $to): void`; complete moves the object to
  `files/{id}/…` and the file record points there afterwards. `delete` must throw when it fails.
- `CompleteUpload` returns `?UploadRejection` and no longer takes `size`; the stored size wins. The
  `size` field of the complete request is ignored.
- Complete can now answer `422 {message, code}`; the file is then `failed` and its object is deleted.
- `UploadPolicy::MAX_UPLOAD_BYTES` is gone; set `media.max_upload_bytes` instead.
- `download_url` (HTTP) and `FileSnapshot::$downloadUrl` are `null` unless the file is `completed`.
- `FileRepository::pendingOlderThan()` is replaced by `unfinishedOlderThan()`, which also returns
  `failed` files; the purge removes them too.
- Files with `uploaded_by = null` are refused by the default rule. A custom `FileAccess` decides what
  to do with them; refusing is the safe default.

