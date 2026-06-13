<?php

namespace App\Http\Controllers;

use App\Services\BookingReportExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, BookingReportExporter $exporter): BinaryFileResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $report = $exporter->export($validated['from'], $validated['to']);

        return response()->download($report['path'], $report['name'])->deleteFileAfterSend();
    }
}
