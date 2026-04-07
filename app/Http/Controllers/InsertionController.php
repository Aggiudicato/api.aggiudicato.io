<?php

namespace App\Http\Controllers;

use App\Models\Insertion;
use App\Services\InsertionSearchService;
use Illuminate\Http\Request;

class InsertionController extends Controller
{
    public function index(Request $request, InsertionSearchService $search)
    {
        $insertions = $search->search($request);

        return response()
            ->view('listings.index', compact('insertions'))
            ->header('Content-Type', 'application/xhtml+xml; charset=UTF-8');
    }

    public function show(Insertion $insertion)
    {
        $insertion->load([
            'procedure.subjects',
            'lot.assets.cadastralRecords',
            'saleData',
            'sites',
            'events',
        ]);

        return response()
            ->view('listings.show', compact('insertion'))
            ->header('Content-Type', 'application/xhtml+xml; charset=UTF-8');
    }
}
