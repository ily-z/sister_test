<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\TemplateSurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Template surat (isi boilerplate surat menyurat, dieditor dengan Quill).
 *
 * Logika bisnis identik dengan versi Blade, hanya return-nya JSON.
 */
class TemplateSuratController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'templates' => TemplateSurat::latest()->get(),
        ]);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'jenisSurat' => $this->jenisSuratOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'jenis_surat' => ['required', 'string', 'max:255'],
            'konten' => ['nullable', 'string'],
        ]);

        $template = TemplateSurat::create($validated);

        return response()->json([
            'message' => 'Template surat berhasil dibuat.',
            'template' => $template,
        ], 201);
    }

    public function edit(TemplateSurat $template): JsonResponse
    {
        return response()->json([
            'template' => $template,
            'jenisSurat' => $this->jenisSuratOptions(),
        ]);
    }

    public function update(Request $request, TemplateSurat $template): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'jenis_surat' => ['required', 'string', 'max:255'],
            'konten' => ['nullable', 'string'],
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Template surat berhasil diperbarui.',
            'template' => $template->fresh(),
        ]);
    }

    /**
     * Data untuk pratinjau sebelum dicetak.
     *
     * Tidak merender HTML di backend: frontend punya komponen pratinjau
     * sendiri, jadi backend cukup mengirim isi mentahnya. Isi `konten`
     * berasal dari user (rich text), jadi TIDAK pernah dikembalikan
     * dengan Content-Type text/html dari endpoint lain -- lihat
     * FileController yang memaksa Content-Disposition: attachment untuk
     * file HTML.
     */
    public function print(TemplateSurat $template): JsonResponse
    {
        return response()->json(['template' => $template]);
    }

    public function destroy(TemplateSurat $template): JsonResponse
    {
        $template->delete();

        return response()->json(['message' => 'Template surat berhasil dihapus.']);
    }

    /**
     * Opsi dropdown jenis dokumen, diambil dari surat yang sudah ada.
     */
    private function jenisSuratOptions()
    {
        return Surat::select('jenis_dokumen')
            ->distinct()
            ->orderBy('jenis_dokumen')
            ->pluck('jenis_dokumen');
    }
}
