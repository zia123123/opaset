<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssetUsahaImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetUsahaImportController extends Controller
{
    public function index(): View
    {
        return view('admin.assets.import-usaha');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_files' => ['required', 'array', 'min:1'],
            'excel_files.*' => ['file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $importer = new AssetUsahaImporter();
        $summary = [
            'matched_assets' => 0,
            'unmatched_assets' => 0,
            'kontrak_updated' => 0,
            'errors' => [],
        ];

        foreach ($request->file('excel_files') as $file) {
            $result = $importer->import($file->getRealPath());

            $summary['matched_assets'] += $result['matched_assets'];
            $summary['unmatched_assets'] += $result['unmatched_assets'];
            $summary['kontrak_updated'] += $result['kontrak_updated'];
            $summary['errors'] = array_merge(
                $summary['errors'],
                array_map(fn ($e) => "[{$file->getClientOriginalName()}] {$e}", $result['errors'])
            );
        }

        return redirect()
            ->route('admin.assets.import-usaha')
            ->with('result', $summary);
    }
}