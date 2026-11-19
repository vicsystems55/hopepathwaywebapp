<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportSupervisionArchiveRequest;
use App\Services\SupervisionArchiveStore;

class SupervisionArchiveController extends Controller
{
    public function index(SupervisionArchiveStore $archives)
    {
        return $this->respond(['data' => $archives->summaries()]);
    }

    public function show(SupervisionArchiveStore $archives, string $archive)
    {
        return $this->respond(['data' => $archives->detail($archive)]);
    }

    public function session(SupervisionArchiveStore $archives, string $archive, string $session)
    {
        return $this->respond(['data' => $archives->session($archive, (int) $session)]);
    }

    public function preview(ImportSupervisionArchiveRequest $request, SupervisionArchiveStore $archives)
    {
        $archive = $archives->validateJson(file_get_contents($request->file('archive')->getRealPath()));
        return $this->respond(['data' => $archives->preview($archive)]);
    }

    public function store(ImportSupervisionArchiveRequest $request, SupervisionArchiveStore $archives)
    {
        $archive = $archives->validateJson(file_get_contents($request->file('archive')->getRealPath()));
        $result = $archives->import($archive);
        return $this->respond([
            'message' => $result['created'] ? 'Completed supervision archive imported.' : 'This archive is already available. No records were changed.',
            'data' => $result,
        ], $result['created'] ? 201 : 200);
    }

    private function respond(array $data, int $status = 200)
    {
        return response()->json($data, $status, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
