# Flows

End-to-end behaviour of the package. Flows are covered by feature tests in `tests/Feature`
(Testbench, Postgres). Every non-empty success response is a `{ "data": { … } }` envelope with
snake_case fields; `204` responses have no body.

## Upload lifecycle (initiate → complete)

Bytes never pass through the app — the client uploads straight to the object store with a presigned
URL, then confirms.

1. `POST /{prefix}` `{filename, mime, size}` — `InitiateUpload` creates a **pending** `File` (a random
   storage key, the `CurrentUser`'s id as `uploaded_by`), records `FileUploadInitiated`, and returns a
   presigned S3 **upload** URL: `201 {data:{file_id, upload_url}}`. `size` is capped by `UploadPolicy`.
2. The client **PUTs the file bytes to `upload_url`** directly (S3), out of band.
3. `POST /{prefix}/{fileId}/complete` `{checksum?, size?}` — `CompleteUpload` verifies the object is
   actually in storage (`ObjectStorage::exists`) — if not, **409** (`UploadNotConfirmedException`) — then
   marks the `File` **completed** (guarded by `FileMustBePending`; a non-pending file → **409**), records
   `FileUploadCompleted`. Responds **204**.

## Read & delete

- `GET /{prefix}/{fileId}` — returns `{data:{id, original_name, mime, size, status, download_url}}` with a
  fresh presigned **download** URL. Unknown id → **404**.
- `DELETE /{prefix}/{fileId}` — removes the stored object **and** the record → **204**.

## Referencing files from other modules — `FileDirectory`

Other modules never touch the `files` table. They depend on `NetCode\Media\Contract\FileDirectory`:
`snapshots($ids)` returns `FileSnapshot`s (`{id, originalName, mime, size, downloadUrl}`) and
`areCompleted($ids)` gates a workflow on every referenced file being uploaded. The host binds it to the
package's `FileDirectoryAdapter`.

## Purge of abandoned uploads

`app:media:purge-uploads` (scheduled daily 03:00) deletes files still **pending** past a threshold
(`--hours`, default 24) — both the stored object and the record — so half-finished uploads don't
accumulate.
