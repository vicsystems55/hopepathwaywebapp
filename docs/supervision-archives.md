# Completed supervision archives

Administrators can browse prepared historical supervision records without merging legacy IDs into current staff, supervision or question tables. Only completed sessions containing saved responses are accepted.

The archive contains staff name/reference snapshots, the question wording found in the source backup, all saved answer versions, source dates and review flags. A saved question snapshot is not proof of the question wording at the time of the original meeting. Scheduled dates are not verified completion dates. Supervisor identities, signatures and missing details are not invented.

## Import from the admin portal

1. Open **Supervision → Completed archive** and choose the import action.
2. Select the prepared `archive.json`, not the original SQL backup, manifest, HTML viewer or ZIP package.
3. Review the validated source, counts, staff snapshots and limitations.
4. Confirm the import. The records are saved in private application storage and become available only to administrators with supervision-management permission.

Preview does not write files or database records. Re-importing the same validated archive is safe and returns the existing archive. A different payload claiming an already imported source fingerprint is rejected; the previous records are preserved.

## Import using the server console

Use the same validation and import process from the Laravel project directory:

```bash
php artisan supervision:archive-import /private/path/archive.json --dry-run
php artisan supervision:archive-import /private/path/archive.json
```

The dry run prints only the archive ID and aggregate counts. It does not print staff names or supervision responses. These commands do not modify the relational database.

For the prepared September 2024 archive in the local workspace:

```powershell
php artisan supervision:archive-import ../supervision-archive/sep11-2024-completed/archive.json --dry-run
php artisan supervision:archive-import ../supervision-archive/sep11-2024-completed/archive.json
```

No database migration is required. Deploy the backend and frontend code, clear stale Laravel route caches, then import the JSON through the admin page or console. The confidential archive data is not part of the Git repository and must be transferred/imported separately.

## Storage and access

- Archives are stored under `storage/app/supervision-archives/{source-sha256}.json` on the private local disk.
- The archive directory uses private visibility and files use owner-only permissions where supported by the operating system.
- Do not place archive files under `public`, `storage/app/public` or the frontend source/build directories.
- Include this private archive directory in the organisation's protected application backups.
- Imports use a filesystem lock and atomic rename. Existing records are immutable through the supplied API.
- Authentication, the explicit `admin` role and `supervision.manage` permission protect all archive endpoints.
- Sensitive responses use `Cache-Control: no-store, private`. Lists contain summary/session metadata; answer text is fetched only when an administrator opens a session.
- Legacy staff snapshots are not automatically linked to current staff profiles. Such links require a separately reviewed identity-matching process.

## API

All paths below are relative to `/api`:

| Method | Path | Result |
|---|---|---|
| GET | `/admin/supervision-archives` | Available archive summaries |
| POST | `/admin/supervision-archives/preview` | Validate multipart field `archive`; return source/counts/staff preview |
| POST | `/admin/supervision-archives` | Validate and explicitly save multipart field `archive` |
| GET | `/admin/supervision-archives/{archive}` | Staff snapshots and session metadata without answer text |
| GET | `/admin/supervision-archives/{archive}/sessions/{session}` | One completed session with its preserved answers and question snapshots |

Archive IDs are lowercase SHA-256 strings; session IDs are the positive legacy IDs within that specific archive. File uploads are limited to 5 MB. The source fingerprint identifies a supplied backup; importing a JSON file does not independently prove that it was extracted from a particular original SQL file.

Validation checks schema version, IDs, relationships, snapshot consistency, declared counts and the completed/answered scope. Duplicate answers to the same question remain separate saved versions; duplicate answer primary IDs are rejected. Unrelated fields such as staff contact details or user credentials are not copied into archive storage.

## Verification

```bash
php artisan test --filter=SupervisionArchiveTest
php artisan route:list --path=admin/supervision-archives
```

The tests use synthetic data and fake private/public disks. They cover authentication and role restrictions, private storage, previews without writes, idempotency, conflicting imports, malformed/oversized files, orphan IDs, duplicate ID rejection, preserved response versions and answer-free summary endpoints.
