<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DealerSpreadsheetImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class DealerImportController extends Controller
{
    public function index(): View
    {
        return view('admin.dealers.import');
    }

    public function store(Request $request, DealerSpreadsheetImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'spreadsheet' => ['required', 'file', 'mimes:csv,txt,xlsx,ods', 'max:20480'],
        ]);

        $file = $validated['spreadsheet'];
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('imports/dealers', Str::uuid().'.'.$extension, 'local');
        if ($path === false) {
            throw new RuntimeException('Unable to store the uploaded dealer spreadsheet.');
        }

        try {
            $result = $importer->importFromFile(Storage::disk('local')->path($path));
        } catch (Throwable $exception) {
            if (! Storage::disk('local')->delete($path)) {
                report(new RuntimeException('Unable to remove the temporary dealer spreadsheet.'));
            }

            throw $exception;
        }

        if (! Storage::disk('local')->delete($path)) {
            throw new RuntimeException('Unable to remove the temporary dealer spreadsheet.');
        }

        return redirect()->route('admin.dealers.index')->with(
            'success',
            'Imported '.$result['created'].' dealer(s) and updated '.$result['updated'].' dealer(s).'
        );
    }
}
