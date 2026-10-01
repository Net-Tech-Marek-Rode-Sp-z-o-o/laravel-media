# Flows

End-to-end behaviour of the package. Flows are covered by feature tests in `tests/Feature`
(Testbench, Postgres). Every non-empty success response is a `{ "data": { … } }` envelope with
snake_case fields; `204` responses have no body.

## Upload lifecycle (initiate → complete)

Bytes never pass through the app — the client uploads straight to the object store with a presigned
URL, then confirms.

1. `POST /{prefix}` `{filename, mime, size}` — `InitiateUpload` creates a **pending** `File` (a random
   storage key, the `CurrentUser`'s id as `uploaded_by`), records `FileUploadInitiated`, and returns a
   presigned S3 **upload** URL: `201 {data:{file_id, upload_url}}`. `size` and `mime` are checked against `media.allowed_types` and the size
   limit of the declared type (its own `max_bytes`, or `media.max_upload_bytes`).
2. The client **PUTs the file bytes to `upload_url`** directly (S3), out of band.
3. `POST /{prefix}/{fileId}/complete` `{checksum?}` — a non-pending file → **409** (`FileMustBePending`).
   `CompleteUpload` moves the object on the server from `uploads/{id}/…` to `files/{id}/…`
   (`ObjectStorage::move`), so the upload URL can no longer change it, then inspects the moved object
   (`ObjectStorage::inspect`: real size and the type detected from the first bytes) — if it is
   missing, **409** (`UploadNotConfirmedException`); if storage cannot read it, **503**. If the
   stored file breaks `UploadLimits`, the object is deleted, the `File` becomes **failed**, records
   `FileUploadRejected`, and the response is **422** `{message, code}`. Otherwise the `File` becomes
   **completed** with the stored size, records `FileUploadCompleted`, and responds **204**.

## Read & delete

Complete, read and delete load the file through `AccessibleFiles`, which asks the `FileAccess` port
whether the current user may touch it. A refused file answers **404**, the same as an unknown id.

- `GET /{prefix}/{fileId}` — returns `{data:{id, original_name, mime, size, status, download_url}}` with a
  fresh presigned **download** URL for a completed file; `download_url` is `null` for a pending or
  failed one. Unknown id → **404**.
- `DELETE /{prefix}/{fileId}` — removes the stored object **and** the record → **204**.

## Referencing files from other modules — `FileDirectory`

Other modules never touch the `files` table. They depend on `NetCode\Media\Api\Contracts\FileDirectory`:
`snapshots($ids, $requestedBy)` returns `FileSnapshot`s (`{id, originalName, mime, size, downloadUrl}`) and
`areCompleted($ids, $requestedBy)` gates a workflow on every referenced file being uploaded. Both ask
`FileAccess`: a file the requester may not access is left out, the same as an unknown id. The host binds it to the
package's `FileDirectoryAdapter`.

## Purge of abandoned uploads

`media:purge-uploads` (scheduled daily 03:00) deletes files still **pending** or **failed** past a
threshold (`--hours`, default 24), both the record and the object under its upload and its stored key,
so half-finished and rejected uploads don't accumulate.
