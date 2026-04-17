<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" lang="it">
<head>
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />
    <title>Ricerca Annunci - Aggiudicato</title>
    <style type="text/css">
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .search-form { margin-bottom: 20px; }
        .search-form label { display: inline-block; margin-right: 10px; }
        .search-form input, .search-form select { margin-right: 15px; padding: 4px; }
        .pagination { margin-top: 15px; }
        .pagination a, .pagination span { margin-right: 5px; padding: 4px 8px; border: 1px solid #ccc; text-decoration: none; }
        .pagination span.current { background-color: #333; color: #fff; }
    </style>
</head>
<body>
    <h1>Ricerca Annunci di Vendita</h1>

    <div class="search-form">
        <form method="get" action="{{ url('/listings') }}">
            <label for="pvp_id">ID Inserzione:</label>
            <input type="text" id="pvp_id" name="pvp_id" value="{{ request('pvp_id') }}" />

            <label for="court">Tribunale:</label>
            <input type="text" id="court" name="court" value="{{ request('court') }}" />

            <label for="procedure_type">Tipo Procedura:</label>
            <select id="procedure_type" name="procedure_type">
                <option value="">-- Tutti --</option>
                <option value="ESECUZIONE_IMMOBILIARE"{{ request('procedure_type') === 'ESECUZIONE_IMMOBILIARE' ? ' selected="selected"' : '' }}>Esecuzione Immobiliare</option>
                <option value="ESECUZIONE_MOBILIARE"{{ request('procedure_type') === 'ESECUZIONE_MOBILIARE' ? ' selected="selected"' : '' }}>Esecuzione Mobiliare</option>
                <option value="PROCEDURA_CONCORSUALE"{{ request('procedure_type') === 'PROCEDURA_CONCORSUALE' ? ' selected="selected"' : '' }}>Procedura Concorsuale</option>
                <option value="ALTRA_VENDITA"{{ request('procedure_type') === 'ALTRA_VENDITA' ? ' selected="selected"' : '' }}>Altra Vendita</option>
            </select>

            <input type="submit" value="Cerca" />
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Inserzione</th>
                <th>Tribunale</th>
                <th>Tipo Procedura</th>
                <th>Lotto</th>
                <th>Data Vendita</th>
                <th>Prezzo Base</th>
                <th>Dettaglio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($insertions as $ins)
            <tr data-pvp-listing="{{ $ins->pvp_id }}">
                <td>{{ $ins->pvp_id }}</td>
                <td>{{ $ins->procedure->court ?? '-' }}</td>
                <td>{{ $ins->procedure->type ?? '-' }}</td>
                <td>{{ $ins->lot->description_it ?? '-' }}</td>
                <td>{{ $ins->saleData->sale_datetime ?? '-' }}</td>
                <td>{{ $ins->saleData->base_price ?? '-' }}</td>
                <td><a href="{{ url('/listings/' . $ins->id) }}" data-pvp-listing-url="1">Dettaglio</a></td>
            </tr>
            @empty
            <tr>
                <td colspan="7">Nessun annuncio trovato.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($insertions->hasPages())
    <div class="pagination">
        @if($insertions->onFirstPage())
            <span>&laquo; Precedente</span>
        @else
            <a href="{{ $insertions->previousPageUrl() }}">&laquo; Precedente</a>
        @endif

        @foreach($insertions->getUrlRange(1, $insertions->lastPage()) as $page => $url)
            @if($page == $insertions->currentPage())
                <span class="current">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if($insertions->hasMorePages())
            <a href="{{ $insertions->nextPageUrl() }}">Successiva &raquo;</a>
        @else
            <span>Successiva &raquo;</span>
        @endif
    </div>
    @endif
</body>
</html>
