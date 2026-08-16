<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssetFullImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetFullImportController extends Controller
{
    public function index(): View
    {
        return view('admin.assets.import-full');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'], // max 20MB
        ]);

        $importer = new AssetFullImporter();
        $result = $importer->import($request->file('excel_file')->getRealPath());

        return redirect()
            ->route('admin.assets.import-full')
            ->with('result', $result);
    }
}
