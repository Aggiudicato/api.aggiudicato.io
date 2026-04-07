<?php

namespace App\Services;

use App\Models\Insertion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class InsertionSearchService
{
    public function search(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        return Insertion::query()
            ->with(['procedure', 'lot', 'saleData'])
            ->where('status', 'published')
            ->when($request->filled('pvp_id'), fn ($q) => $q->where('pvp_id', 'like', '%' . $request->input('pvp_id') . '%'))
            ->when($request->filled('court'), fn ($q) => $q->whereHas('procedure', fn ($p) => $p->where('court', 'like', '%' . $request->input('court') . '%')))
            ->when($request->filled('procedure_type'), fn ($q) => $q->whereHas('procedure', fn ($p) => $p->where('type', $request->input('procedure_type'))))
            ->when($request->filled('date_from'), fn ($q) => $q->where('publication_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('publication_date', '<=', $request->input('date_to')))
            ->orderByDesc('publication_date')
            ->paginate($perPage)
            ->withQueryString();
    }
}
