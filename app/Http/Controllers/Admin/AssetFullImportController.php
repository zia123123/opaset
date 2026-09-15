<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssetFullImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetFullImportController extends Controller
{
    public function index(): View
    {
        return view('admin.assets.import-full');
    }

    /**
     * Endpoint kecil dipanggil via JS begitu user pilih file — baca daftar
     * nama sheet di dalamnya tanpa proses import, buat ditampilkan sebagai
     * checkbox supaya admin bisa pilih sheet mana saja yang mau diproses.
     */
    public function listSheets(Request $request)
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $spreadsheet = IOFactory::load($request->file('excel_file')->getRealPath());

        return response()->json([
            'sheets' => $spreadsheet->getSheetNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'sheets' => ['nullable', 'array'],
        ]);

        $importer = new AssetFullImporter();
        $result = $importer->import(
            $request->file('excel_file')->getRealPath(),
            $request->input('sheets', [])
        );

        return redirect()
            ->route('admin.assets.import-full')
            ->with('result', $result);
    }
}