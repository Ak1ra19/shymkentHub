<?php

namespace App\Services;

use App\Models\ConferenceRoomRequest;
use App\Models\WorkspaceBooking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class BookingReportExporter
{
    /**
     * @return array{path:string, name:string}
     */
    public function export(string $from, string $to): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required to create XLSX reports.');
        }

        $fromDate = CarbonImmutable::parse($from)->toDateString();
        $toDate = CarbonImmutable::parse($to)->toDateString();
        $rows = $this->rows($fromDate, $toDate);
        $path = storage_path('app/reports/bookings-'.now()->format('Ymd-His').'.xlsx');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create report file.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($rows));
        $zip->close();

        return [
            'path' => $path,
            'name' => basename($path),
        ];
    }

    /**
     * @return Collection<int, array<int, string>>
     */
    private function rows(string $from, string $to): Collection
    {
        $header = [[
            'ФИО пользователя',
            'Компания',
            'Тип бронирования',
            'Рабочее место',
            'Конференц-зал',
            'Номер места',
            'Дата',
            'Время начала',
            'Время окончания',
            'Количество часов',
            'Статус',
        ]];

        $workspaces = WorkspaceBooking::query()
            ->with('user:id,name,company')
            ->whereBetween('booking_date', [$from, $to])
            ->orderBy('booking_date')
            ->get()
            ->map(fn (WorkspaceBooking $booking): array => [
                $booking->user->name,
                (string) $booking->user->company,
                'Рабочее место',
                'Да',
                'Нет',
                (string) $booking->workspace_number,
                $booking->booking_date->toDateString(),
                $booking->starts_at->format('H:i'),
                $booking->ends_at->format('H:i'),
                $this->hours($booking->starts_at->format('H:i'), $booking->ends_at->format('H:i')),
                $booking->status->label(),
            ]);

        $conferenceRequests = ConferenceRoomRequest::query()
            ->with('user:id,name,company')
            ->whereBetween('booking_date', [$from, $to])
            ->orderBy('booking_date')
            ->get()
            ->map(fn (ConferenceRoomRequest $request): array => [
                $request->user->name,
                (string) $request->user->company,
                'Конференц-зал',
                'Нет',
                'Да',
                '',
                $request->booking_date->toDateString(),
                $request->starts_at->format('H:i'),
                $request->ends_at->format('H:i'),
                $this->hours($request->starts_at->format('H:i'), $request->ends_at->format('H:i')),
                $request->status->label(),
            ]);

        return collect($header)->merge($workspaces)->merge($conferenceRequests)->values();
    }

    private function hours(string $startsAt, string $endsAt): string
    {
        $start = CarbonImmutable::createFromFormat('H:i', $startsAt);
        $end = CarbonImmutable::createFromFormat('H:i', $endsAt);

        return number_format($start->diffInMinutes($end) / 60, 2, '.', '');
    }

    /**
     * @param  Collection<int, array<int, string>>  $rows
     */
    private function sheet(Collection $rows): string
    {
        $xmlRows = $rows
            ->map(function (array $row, int $rowIndex): string {
                $cells = collect($row)
                    ->map(function (string $value, int $columnIndex) use ($rowIndex): string {
                        $cell = $this->cellName($columnIndex + 1).($rowIndex + 1);
                        $escaped = htmlspecialchars($value, ENT_XML1);

                        return "<c r=\"{$cell}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
                    })
                    ->implode('');

                return '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
            })
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>';
    }

    private function cellName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Bookings" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    }
}
