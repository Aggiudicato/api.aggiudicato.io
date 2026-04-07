<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" lang="it">
<head>
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />
    <title>Annuncio {{ $insertion->pvp_id }} - Aggiudicato</title>
    <style type="text/css">
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; width: 30%; }
        .section { margin-bottom: 30px; }
        .back-link { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="back-link">
        <a href="{{ url('/listings') }}">&laquo; Torna alla ricerca</a>
    </div>

    <h1>Annuncio di Vendita</h1>
    <p>ID Inserzione: <strong>{{ $insertion->pvp_id }}</strong></p>
    <p>Data Pubblicazione: <strong>{{ $insertion->publication_date }}</strong></p>
    <p>Tipologia: <strong>{{ $insertion->type }}</strong></p>

    {{-- PROCEDURE --}}
    @if($insertion->procedure)
    <div class="section" id="procedure">
        <h2>Dati Procedura</h2>
        <table>
            <tr><th>Tipo Procedura</th><td>{{ $insertion->procedure->type }}</td></tr>
            @if($insertion->procedure->court)
            <tr><th>Tribunale</th><td>{{ $insertion->procedure->court }}</td></tr>
            @endif
            @if($insertion->procedure->registry)
            <tr><th>Registro</th><td>{{ $insertion->procedure->registry }}</td></tr>
            @endif
            @if($insertion->procedure->rite)
            <tr><th>Rito</th><td>{{ $insertion->procedure->rite }}</td></tr>
            @endif
            @if($insertion->procedure->number)
            <tr><th>Numero Procedura</th><td>{{ $insertion->procedure->number }}/{{ $insertion->procedure->year }}</td></tr>
            @endif
        </table>

        @if($insertion->procedure->subjects && $insertion->procedure->subjects->count() > 0)
        <h3>Soggetti</h3>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Nominativo</th>
                    <th>Codice Fiscale</th>
                    <th>Email</th>
                    <th>Telefono</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insertion->procedure->subjects as $subject)
                <tr>
                    <td>{{ $subject->type }}</td>
                    <td>{{ $subject->first_name }} {{ $subject->last_name }}</td>
                    <td>{{ $subject->tax_code ?? '-' }}</td>
                    <td>{{ $subject->email ?? '-' }}</td>
                    <td>{{ $subject->mobile ?? $subject->phone ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    {{-- LOT --}}
    @if($insertion->lot)
    <div class="section" id="lot">
        <h2>Lotto</h2>
        <table>
            <tr><th>Codice</th><td>{{ $insertion->lot->code }}</td></tr>
            <tr><th>Genere</th><td>{{ $insertion->lot->genre }}</td></tr>
            <tr><th>Categoria</th><td>{{ $insertion->lot->category }}</td></tr>
            <tr><th>Descrizione</th><td>{{ $insertion->lot->description_it }}</td></tr>
            @if($insertion->lot->description_de)
            <tr><th>Descrizione (DE)</th><td>{{ $insertion->lot->description_de }}</td></tr>
            @endif
            @if($insertion->lot->location_address)
            <tr><th>Ubicazione</th><td>{{ $insertion->lot->location_address }}{{ $insertion->lot->location_street_number ? ', ' . $insertion->lot->location_street_number : '' }} - {{ $insertion->lot->location_zip }} {{ $insertion->lot->location_city }} ({{ $insertion->lot->location_province }})</td></tr>
            @endif
        </table>

        @foreach($insertion->lot->assets as $asset)
        <div class="section" id="asset-{{ $asset->id }}">
            <h3>Bene: {{ $asset->type }} - {{ $asset->category }}</h3>
            <table>
                <tr><th>Descrizione</th><td>{{ $asset->description_it }}</td></tr>
                @if($asset->description_de)
                <tr><th>Descrizione (DE)</th><td>{{ $asset->description_de }}</td></tr>
                @endif
                @if($asset->location_address)
                <tr><th>Ubicazione</th><td>{{ $asset->location_address }}{{ $asset->location_street_number ? ', ' . $asset->location_street_number : '' }} - {{ $asset->location_zip }} {{ $asset->location_city }} ({{ $asset->location_province }})</td></tr>
                @endif
                @if($asset->availability)
                <tr><th>Disponibilit&agrave;</th><td>{{ $asset->availability }}</td></tr>
                @endif
                @if($asset->square_meters)
                <tr><th>Superficie (mq)</th><td>{{ $asset->square_meters }}</td></tr>
                @endif
                @if($asset->rooms)
                <tr><th>Vani</th><td>{{ $asset->rooms }}</td></tr>
                @endif
                @if($asset->floor)
                <tr><th>Piano</th><td>{{ $asset->floor }}</td></tr>
                @endif
            </table>

            @if($asset->cadastralRecords && $asset->cadastralRecords->count() > 0)
            <h4>Dati Catastali</h4>
            <table>
                <thead>
                    <tr>
                        <th>Sezione</th>
                        <th>Foglio</th>
                        <th>Particella</th>
                        <th>Subalterno</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($asset->cadastralRecords as $record)
                    <tr>
                        <td>{{ $record->section ?? '-' }}</td>
                        <td>{{ $record->sheet }}</td>
                        <td>{{ $record->parcel }}</td>
                        <td>{{ $record->sub_unit ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- SALE DATA --}}
    @if($insertion->saleData)
    <div class="section" id="sale-data">
        <h2>Dati Vendita</h2>
        <table>
            <tr><th>Data e Ora Vendita</th><td>{{ $insertion->saleData->sale_datetime }}</td></tr>
            <tr><th>Tipologia Vendita</th><td>{{ $insertion->saleData->sale_type }}</td></tr>
            <tr><th>Modalit&agrave; Vendita</th><td>{{ $insertion->saleData->sale_method }}</td></tr>
            @if($insertion->saleData->base_price)
            <tr><th>Prezzo/Valore Base</th><td>&euro; {{ $insertion->saleData->base_price }}</td></tr>
            @endif
            @if($insertion->saleData->minimum_bid)
            <tr><th>Offerta Minima</th><td>&euro; {{ $insertion->saleData->minimum_bid }}</td></tr>
            @endif
            @if($insertion->saleData->minimum_raise)
            <tr><th>Rialzo Minimo</th><td>&euro; {{ $insertion->saleData->minimum_raise }}</td></tr>
            @endif
            @if($insertion->saleData->security_deposit)
            <tr><th>Deposito Cauzionale</th><td>&euro; {{ $insertion->saleData->security_deposit }}</td></tr>
            @endif
            @if($insertion->saleData->expense_deposit)
            <tr><th>Deposito Conto Spese</th><td>&euro; {{ $insertion->saleData->expense_deposit }}</td></tr>
            @endif
            <tr><th>Termine Presentazione Offerte</th><td>{{ $insertion->saleData->bid_deadline }}</td></tr>
            @if($insertion->saleData->venue_address)
            <tr><th>Luogo Vendita</th><td>{{ $insertion->saleData->venue_address }}{{ $insertion->saleData->venue_street_number ? ', ' . $insertion->saleData->venue_street_number : '' }} - {{ $insertion->saleData->venue_zip }} {{ $insertion->saleData->venue_city }} ({{ $insertion->saleData->venue_province }})</td></tr>
            @endif
        </table>
    </div>
    @endif

    {{-- SITES --}}
    @if($insertion->sites && $insertion->sites->count() > 0)
    <div class="section" id="sites">
        <h2>Siti di Pubblicazione</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipologia</th>
                    <th>Nominativo</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insertion->sites as $site)
                <tr>
                    <td>{{ $site->type ?? '-' }}</td>
                    <td>{{ $site->name ?? '-' }}</td>
                    <td><a href="{{ $site->url }}">{{ $site->url }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- EVENTS --}}
    @if($insertion->events && $insertion->events->count() > 0)
    <div class="section" id="events">
        <h2>Eventi</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipologia</th>
                    <th>Nota</th>
                    <th>Data Pubblicazione</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insertion->events as $event)
                <tr>
                    <td>{{ $event->type }}</td>
                    <td>{{ $event->note }}</td>
                    <td>{{ $event->publication_date }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</body>
</html>
