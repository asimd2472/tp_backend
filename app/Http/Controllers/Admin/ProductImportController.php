<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SpreadsheetImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductImportController extends Controller
{
    public function index(): View
    {
        return view('admin.products.import');
    }

    public function store(Request $request, SpreadsheetImporter $importer): RedirectResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $request->validate([
            'spreadsheet' => ['required', 'file', 'mimes:ods,xlsx,csv', 'max:20480'],
        ]);

        $file = $request->file('spreadsheet');
        $path = $file->storeAs('imports', $file->getClientOriginalName(), 'local');

        $result = $importer->importFromFile(Storage::disk('local')->path($path));

        if (! empty($result['errors'])) {
            return redirect()->route('admin.products.import.index')->withErrors(['spreadsheet' => implode(' ', $result['errors'])])->withInput();
        }

        return redirect()->route('admin.products.index')->with('success', 'Imported '.$result['products'].' product(s), '.$result['designs'].' design(s), and '.$result['shades'].' shade(s).');
    }
}
