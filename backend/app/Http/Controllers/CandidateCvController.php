<?php

namespace App\Http\Controllers;

use App\Models\CandidateCv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateCvController extends Controller
{
    public function download(Request $request, int $cv)
    {
        $record = CandidateCv::query()
            ->where('candidate_id', $request->user()->id)
            ->findOrFail($cv);

        abort_unless(Storage::disk('local')->exists($record->file_path), 404, 'CV file not found.');

        return Storage::disk('local')->download($record->file_path, $record->original_name);
    }
}
