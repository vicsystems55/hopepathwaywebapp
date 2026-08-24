<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOwnStaffDocumentRequest;
use App\Models\StaffDocument;
use App\Models\StaffRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffDocumentController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $this->staffRecord($request)->documents()->latest()->get(),
        ]);
    }

    public function store(StoreOwnStaffDocumentRequest $request)
    {
        $staffRecord = $this->staffRecord($request);
        $file = $request->file('file');
        $path = $file->store("staff_documents/{$staffRecord->id}", 'local');

        $document = $staffRecord->documents()->create([
            'uploaded_by' => $request->user()->id,
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'course_name' => $request->input('course_name'),
            'issued_on' => $request->input('issued_on'),
            'expires_on' => $request->input('expires_on'),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => $document,
        ], 201);
    }

    public function destroy(Request $request, StaffDocument $staffDocument)
    {
        $this->ensureOwnDocument($request, $staffDocument);
        Storage::disk('local')->delete($staffDocument->file_path);
        $staffDocument->delete();

        return response()->json(['message' => 'Document removed successfully.']);
    }

    public function download(Request $request, StaffDocument $staffDocument)
    {
        $isAdmin = $request->user()->role === User::ROLE_ADMIN;
        $isOwner = (int) $staffDocument->staffRecord()->value('user_id') === (int) $request->user()->id;

        abort_unless($isAdmin || $isOwner, 403, 'You do not have access to this document.');
        abort_unless(Storage::disk('local')->exists($staffDocument->file_path), 404, 'Document file not found.');

        return Storage::disk('local')->download($staffDocument->file_path, $staffDocument->original_name, [
            'Content-Type' => $staffDocument->mime_type ?: 'application/octet-stream',
        ]);
    }

    private function staffRecord(Request $request): StaffRecord
    {
        $staffRecord = $request->user()->staffRecord;
        abort_unless($staffRecord, 409, 'This account is not linked to a staff record.');

        return $staffRecord;
    }

    private function ensureOwnDocument(Request $request, StaffDocument $staffDocument): void
    {
        abort_unless(
            (int) $staffDocument->staff_record_id === (int) $this->staffRecord($request)->id,
            403,
            'You can only manage your own documents.'
        );
    }
}
