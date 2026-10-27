<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\WindowSpreadsheetImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WindowImportController extends Controller
{
    public function index(): View
    {
        return view('admin.windows.import');
    }

    public function store(Request $request, WindowSpreadsheetImporter $importer): RedirectResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $request->validate([
            'spreadsheet' => ['required', 'file', 'mimes:ods,xlsx,csv', 'max:20480'],
        ]);

        $file = $request->file('spreadsheet');
        $path = $file->storeAs('imports/windows', $file->getClientOriginalName(), 'local');
        $result = $importer->importFromFile(Storage::disk('local')->path($path));

        if ($result['errors'] !== []) {
            return redirect()->route('admin.windows.import.index')
                ->withErrors(['spreadsheet' => implode(' ', $result['errors'])])
                ->withInput();
        }

        return redirect()->route('admin.windows.index')->with(
            'success',
            'Imported '.$result['products'].' window product(s), '.$result['models'].' model(s), '.$result['shades'].' shade(s), and '.$result['sizes'].' size(s).'
        );
    }
}
