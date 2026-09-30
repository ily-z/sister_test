<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\TemplateSurat;
use Illuminate\Http\Request;

class TemplateSuratController extends Controller
{
    public function index()
    {
        $templates = TemplateSurat::latest()->get();
        return view('surat-menyurat.template.index', compact('templates'));
    }

    public function create()
    {
        $jenisSurat = Surat::select('jenis_dokumen')->distinct()->orderBy('jenis_dokumen')->pluck('jenis_dokumen');
        return view('surat-menyurat.template.create', compact('jenisSurat'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'jenis_surat' => 'required|string|max:255',
            'konten' => 'nullable|string',
        ]);

        TemplateSurat::create([
            'judul' => $request->judul,
            'jenis_surat' => $request->jenis_surat,
            'konten' => $request->konten,
        ]);

        return redirect()->route('admin.surat-menyurat.template')
            ->with('success', 'Template surat berhasil dibuat.');
    }

    public function edit($id)
    {
        $template = TemplateSurat::findOrFail($id);
        $jenisSurat = Surat::select('jenis_dokumen')->distinct()->orderBy('jenis_dokumen')->pluck('jenis_dokumen');
        return view('surat-menyurat.template.edit', compact('template', 'jenisSurat'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'jenis_surat' => 'required|string|max:255',
            'konten' => 'nullable|string',
        ]);

        $template = TemplateSurat::findOrFail($id);
        $template->update([
            'judul' => $request->judul,
            'jenis_surat' => $request->jenis_surat,
            'konten' => $request->konten,
        ]);

        return redirect()->route('admin.surat-menyurat.template')
            ->with('success', 'Template surat berhasil diperbarui.');
    }

    public function print($id)
    {
        $template = TemplateSurat::findOrFail($id);
        return view('surat-menyurat.template.print', compact('template'));
    }

    public function destroy($id)
    {
        $template = TemplateSurat::findOrFail($id);
        $template->delete();

        return redirect()->route('admin.surat-menyurat.template')
            ->with('success', 'Template surat berhasil dihapus.');
    }
}
