<?php

namespace App\Support;

use App\Models\Dealer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DealerSpreadsheetImporter
{
    private const HEADERS = [
        'state',
        'district',
        'dealername',
        'dealeraddress',
        'contactperson',
        'mobilenumber',
        'emailid',
    ];

    public function __construct(private readonly SpreadsheetImporter $spreadsheetImporter) {}

    public function importFromFile(string $path): array
    {
        $rows = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'csv'
            ? $this->rowsFromCsv($path)
            : $this->spreadsheetImporter->rowsFromFile($path);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'spreadsheet' => 'The uploaded file contains no dealer rows.',
            ]);
        }

        $normalizedRows = [];
        foreach ($rows as $index => $row) {
            $normalized = [];
            foreach ($row as $header => $value) {
                $key = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? ''));
                $key = preg_replace('/[^a-z0-9]+/', '', $key) ?? '';
                $normalized[$key] = trim((string) $value);
            }

            if ($index === 0) {
                $missingHeaders = array_diff(self::HEADERS, array_keys($normalized));
                if ($missingHeaders !== []) {
                    throw ValidationException::withMessages([
                        'spreadsheet' => 'Missing required columns: '.implode(', ', $missingHeaders).'.',
                    ]);
                }
            }

            $dealer = [
                'state' => $normalized['state'] ?? '',
                'district' => $normalized['district'] ?? '',
                'dealer_name' => $normalized['dealername'] ?? '',
                'dealer_address' => $normalized['dealeraddress'] ?? '',
                'contact_person' => $normalized['contactperson'] ?? '',
                'mobile_number' => $normalized['mobilenumber'] ?? '',
                'email_id' => $normalized['emailid'] ?? '',
            ];

            if (implode('', $dealer) === '') {
                continue;
            }

            if ($dealer['dealer_name'] === '') {
                throw ValidationException::withMessages([
                    'spreadsheet' => 'Dealer name is required on spreadsheet row '.($index + 2).'.',
                ]);
            }

            $normalizedRows[] = $dealer;
        }

        if ($normalizedRows === []) {
            throw ValidationException::withMessages([
                'spreadsheet' => 'The uploaded file contains no dealer rows.',
            ]);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($normalizedRows, &$created, &$updated): void {
            foreach ($normalizedRows as $dealerData) {
                $match = Dealer::query()
                    ->where('dealer_name', $dealerData['dealer_name'])
                    ->where('state', $dealerData['state'] !== '' ? $dealerData['state'] : null)
                    ->where('district', $dealerData['district'] !== '' ? $dealerData['district'] : null);

                if ($dealerData['mobile_number'] !== '') {
                    $match->where('mobile_number', $dealerData['mobile_number']);
                } elseif ($dealerData['email_id'] !== '') {
                    $match->where('email_id', $dealerData['email_id']);
                } else {
                    $match->where('dealer_address', $dealerData['dealer_address'] !== '' ? $dealerData['dealer_address'] : null);
                }

                $dealer = $match->first();
                if ($dealer) {
                    $dealer->update($dealerData);
                    $updated++;
                } else {
                    Dealer::create($dealerData);
                    $created++;
                }
            }
        });

        return compact('created', 'updated');
    }

    private function rowsFromCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the uploaded dealer CSV file.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                return [];
            }

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                $row = [];
                foreach ($headers as $index => $header) {
                    $row[(string) $header] = $values[$index] ?? '';
                }
                $rows[] = $row;
            }

            if (! feof($handle)) {
                throw new RuntimeException('Unable to read all rows from the uploaded dealer CSV file.');
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
