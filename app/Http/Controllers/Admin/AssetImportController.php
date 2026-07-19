<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssetKdListImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetImportController extends Controller
{
    public function index(): View
    {
        return view('admin.assets.import');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], // max 10MB
        ]);

        $path = $request->file('excel_file')->getRealPath();

        $importer = new AssetKdListImporter();
        $result = $importer->import($path);

        return redirect()
            ->route('admin.assets.import')
            ->with('result', $result);
    }
}