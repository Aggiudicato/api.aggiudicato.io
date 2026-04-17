<?php
/**
 * Detail page for a single inserzioneEspVendita.
 *
 * XHTML 1.0 Strict so the Ministry's XSLT_DETTAGLIO can parse it as XML.
 * Every piece of data that must roundtrip back to the canonical
 * inserzioneEspVendita schema is tagged with:
 *   - data-pvp-field="<canonicalName>" for leaf values
 *   - data-pvp-group="<canonicalName>" for containers (repeatable or nested)
 *   - data-pvp-attr-<name>="..." for XML attributes on the corresponding element
 *
 * The XSLT selects by these attributes — never by position or by <th> text —
 * so cosmetic changes to the page cannot break PVP ingestion.
 */
?><?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

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
<body data-pvp-group="inserzioneEspVendita"
      data-pvp-attr-idInserzioneEspVendita="{{ $insertion->pvp_id }}"
      @if($insertion->message_id) data-pvp-attr-messageId="{{ $insertion->message_id }}" @endif>

    <div class="back-link">
        <a href="{{ url('/listings') }}">&laquo; Torna alla ricerca</a>
    </div>

    <h1>Annuncio di Vendita</h1>
    <p>ID Inserzione: <strong data-pvp-attr-root="idInserzioneEspVendita">{{ $insertion->pvp_id }}</strong></p>
    <p>Tipologia Inserzione: <strong data-pvp-field="tipologiaInserzione">{{ $insertion->type }}</strong></p>
    @if($insertion->advertising_days !== null)
    <p>Giorni Pubblicit&agrave;: <strong data-pvp-field="giorniPubblicita">{{ $insertion->advertising_days }}</strong></p>
    @endif
    @if($insertion->publication_date)
    <p>Data Pubblicazione: <strong data-pvp-field="dataPubblicazione">{{ $insertion->publication_date->format('Y-m-d') }}</strong></p>
    @endif

    {{-- datiProcedura --}}
    @if($insertion->procedure)
    <div class="section" data-pvp-group="datiProcedura"
         @if($insertion->procedure->pvp_id) data-pvp-attr-idProcedura="{{ $insertion->procedure->pvp_id }}" @endif>
        <h2>Dati Procedura</h2>
        <table>
            <tr><th>Tipo Procedura</th><td data-pvp-field="tipoProcedura">{{ $insertion->procedure->type }}</td></tr>
        </table>

        @if($insertion->procedure->court || $insertion->procedure->number)
        <div data-pvp-group="proceduraGiudiziaria"
             @if($insertion->procedure->court_pvp_id) data-pvp-attr-idTribunale="{{ $insertion->procedure->court_pvp_id }}" @endif
             @if($insertion->procedure->rite_pvp_id) data-pvp-attr-idRito="{{ $insertion->procedure->rite_pvp_id }}" @endif
             @if($insertion->procedure->registry_pvp_id) data-pvp-attr-idRegistro="{{ $insertion->procedure->registry_pvp_id }}" @endif>
            <h3>Procedura Giudiziaria</h3>
            <table>
                @if($insertion->procedure->court)
                <tr><th>Tribunale</th><td data-pvp-field="tribunale">{{ $insertion->procedure->court }}</td></tr>
                @endif
                @if($insertion->procedure->registry)
                <tr><th>Registro</th><td data-pvp-field="registro">{{ $insertion->procedure->registry }}</td></tr>
                @endif
                @if($insertion->procedure->rite)
                <tr><th>Rito</th><td data-pvp-field="rito">{{ $insertion->procedure->rite }}</td></tr>
                @endif
                @if($insertion->procedure->number)
                <tr><th>Numero Procedura</th><td data-pvp-field="numeroProcedura">{{ $insertion->procedure->number }}</td></tr>
                @endif
                @if($insertion->procedure->year)
                <tr><th>Anno Procedura</th><td data-pvp-field="annoProcedura">{{ $insertion->procedure->year }}</td></tr>
                @endif
            </table>
        </div>
        @endif

        @if($insertion->procedure->proceeding)
        <div data-pvp-group="altraVendita">
            <h3>Altra Vendita</h3>
            <table>
                <tr><th>Procedimento</th><td data-pvp-field="procedimento">{{ $insertion->procedure->proceeding }}</td></tr>
                @if($insertion->procedure->proceeding_number)
                <tr><th>Numero Procedimento</th><td data-pvp-field="numeroProcedimento">{{ $insertion->procedure->proceeding_number }}</td></tr>
                @endif
                @if($insertion->procedure->proceeding_year)
                <tr><th>Anno Procedimento</th><td data-pvp-field="annoProcedimento">{{ $insertion->procedure->proceeding_year }}</td></tr>
                @endif
            </table>
        </div>
        @endif

        @foreach($insertion->procedure->subjects as $subject)
        <div data-pvp-group="soggetti"
             @if($subject->pvp_id) data-pvp-attr-idAnagrafica="{{ $subject->pvp_id }}" @endif>
            <h3>Soggetto</h3>
            <table>
                <tr><th>Tipo</th><td data-pvp-field="tipo">{{ $subject->type }}</td></tr>
                @if($subject->first_name)
                <tr><th>Nome</th><td data-pvp-field="nome">{{ $subject->first_name }}</td></tr>
                @endif
                <tr><th>Cognome</th><td data-pvp-field="cognome">{{ $subject->last_name }}</td></tr>
                @if($subject->tax_code)
                <tr><th>Codice Fiscale</th><td data-pvp-field="codiceFiscale">{{ $subject->tax_code }}</td></tr>
                @endif
                @if($subject->email)
                <tr><th>Email</th><td data-pvp-field="email">{{ $subject->email }}</td></tr>
                @endif
                @if($subject->mobile)
                <tr><th>Cellulare</th><td data-pvp-field="cellulare">{{ $subject->mobile }}</td></tr>
                @endif
                @if($subject->phone)
                <tr><th>Telefono</th><td data-pvp-field="telefono">{{ $subject->phone }}</td></tr>
                @endif
                @if($subject->handles_sale !== null)
                <tr><th>Procede Op. Vendita</th><td data-pvp-field="procedeOpVendita">{{ $subject->handles_sale ? 'true' : 'false' }}</td></tr>
                @endif
                @if($subject->handles_viewing !== null)
                <tr><th>Soggetto Visita Bene</th><td data-pvp-field="soggVisitaBene">{{ $subject->handles_viewing ? 'true' : 'false' }}</td></tr>
                @endif
            </table>
        </div>
        @endforeach
    </div>
    @endif

    {{-- lotto --}}
    @if($insertion->lot)
    <div class="section" data-pvp-group="lotto"
         @if($insertion->lot->pvp_id) data-pvp-attr-idLotto="{{ $insertion->lot->pvp_id }}" @endif>
        <h2>Lotto</h2>
        <table>
            @if($insertion->lot->primary_id)
            <tr><th>Primo Identificativo Lotto</th><td data-pvp-field="primoIdentificativoLotto">{{ $insertion->lot->primary_id }}</td></tr>
            @endif
            <tr><th>Codice</th><td data-pvp-field="codice">{{ $insertion->lot->code }}</td></tr>
            <tr><th>Genere</th><td data-pvp-field="genere">{{ $insertion->lot->genre }}</td></tr>
            <tr><th>Categoria</th><td data-pvp-field="categoria">{{ $insertion->lot->category }}</td></tr>
            <tr><th>Descrizione</th><td data-pvp-field="descrizioneIT">{{ $insertion->lot->description_it }}</td></tr>
            @if($insertion->lot->description_de)
            <tr><th>Descrizione (DE)</th><td data-pvp-field="descrizioneDE">{{ $insertion->lot->description_de }}</td></tr>
            @endif
        </table>

        @if($insertion->lot->location_address || $insertion->lot->location_city)
        <div data-pvp-group="ubicazione">
            <h3>Ubicazione Lotto</h3>
            <table>
                @if($insertion->lot->location_address)
                <tr><th>Indirizzo</th><td data-pvp-field="indirizzo">{{ $insertion->lot->location_address }}</td></tr>
                @endif
                @if($insertion->lot->location_street_number)
                <tr><th>Civico</th><td data-pvp-field="civicoEsponente">{{ $insertion->lot->location_street_number }}</td></tr>
                @endif
                @if($insertion->lot->location_zip)
                <tr><th>CAP</th><td data-pvp-field="capZipCode">{{ $insertion->lot->location_zip }}</td></tr>
                @endif
                @if($insertion->lot->location_city)
                <tr><th>Citt&agrave;</th><td data-pvp-field="citta">{{ $insertion->lot->location_city }}</td></tr>
                @endif
                @if($insertion->lot->location_province)
                <tr><th>Provincia</th><td data-pvp-field="provincia">{{ $insertion->lot->location_province }}</td></tr>
                @endif
                @if($insertion->lot->location_region)
                <tr><th>Regione</th><td data-pvp-field="regione">{{ $insertion->lot->location_region }}</td></tr>
                @endif
                @if($insertion->lot->location_country)
                <tr><th>Nazione</th><td data-pvp-field="nazione">{{ $insertion->lot->location_country }}</td></tr>
                @endif
            </table>
        </div>
        @endif

        @foreach($insertion->lot->assets as $asset)
        <div data-pvp-group="beni"
             @if($asset->pvp_id) data-pvp-attr-idBene="{{ $asset->pvp_id }}" @endif>
            <h3>Bene</h3>
            <table>
                @if($asset->primary_id)
                <tr><th>Primo Identificativo Bene</th><td data-pvp-field="primoIdentificativoBene">{{ $asset->primary_id }}</td></tr>
                @endif
                <tr><th>Tipologia</th><td data-pvp-field="tipologia">{{ $asset->type }}</td></tr>
                <tr><th>Categoria</th><td data-pvp-field="categoria">{{ $asset->category }}</td></tr>
                <tr><th>Descrizione</th><td data-pvp-field="descrizioneIT">{{ $asset->description_it }}</td></tr>
                @if($asset->description_de)
                <tr><th>Descrizione (DE)</th><td data-pvp-field="descrizioneDE">{{ $asset->description_de }}</td></tr>
                @endif
            </table>

            @if($asset->location_address || $asset->location_city)
            <div data-pvp-group="ubicazione">
                <h4>Ubicazione Bene</h4>
                <table>
                    @if($asset->location_address)
                    <tr><th>Indirizzo</th><td data-pvp-field="indirizzo">{{ $asset->location_address }}</td></tr>
                    @endif
                    @if($asset->location_street_number)
                    <tr><th>Civico</th><td data-pvp-field="civicoEsponente">{{ $asset->location_street_number }}</td></tr>
                    @endif
                    @if($asset->location_zip)
                    <tr><th>CAP</th><td data-pvp-field="capZipCode">{{ $asset->location_zip }}</td></tr>
                    @endif
                    @if($asset->location_city)
                    <tr><th>Citt&agrave;</th><td data-pvp-field="citta">{{ $asset->location_city }}</td></tr>
                    @endif
                    @if($asset->location_province)
                    <tr><th>Provincia</th><td data-pvp-field="provincia">{{ $asset->location_province }}</td></tr>
                    @endif
                    @if($asset->location_region)
                    <tr><th>Regione</th><td data-pvp-field="regione">{{ $asset->location_region }}</td></tr>
                    @endif
                    @if($asset->location_country)
                    <tr><th>Nazione</th><td data-pvp-field="nazione">{{ $asset->location_country }}</td></tr>
                    @endif
                </table>
            </div>
            @endif

            @if($asset->availability || $asset->square_meters || $asset->rooms || $asset->floor || ($asset->cadastralRecords && $asset->cadastralRecords->count() > 0))
            <div data-pvp-group="beneImmobile">
                <h4>Bene Immobile</h4>
                <table>
                    @if($asset->availability)
                    <tr><th>Disponibilit&agrave;</th><td data-pvp-field="disponibilita">{{ $asset->availability }}</td></tr>
                    @endif
                    @if($asset->square_meters)
                    <tr><th>Superficie (mq)</th><td data-pvp-field="mq">{{ $asset->square_meters }}</td></tr>
                    @endif
                    @if($asset->rooms)
                    <tr><th>Vani</th><td data-pvp-field="vani">{{ $asset->rooms }}</td></tr>
                    @endif
                    @if($asset->floor)
                    <tr><th>Piano</th><td data-pvp-field="piano">{{ $asset->floor }}</td></tr>
                    @endif
                </table>

                @if($asset->cadastralRecords && $asset->cadastralRecords->count() > 0)
                @foreach($asset->cadastralRecords as $record)
                <div data-pvp-group="datiCatastali">
                    <h5>Dati Catastali</h5>
                    <table>
                        @if($record->section)
                        <tr><th>Sezione</th><td data-pvp-field="sezione">{{ $record->section }}</td></tr>
                        @endif
                        <tr><th>Foglio</th><td data-pvp-field="foglio">{{ $record->sheet }}</td></tr>
                        <tr><th>Particella</th><td data-pvp-field="particella">{{ $record->parcel }}</td></tr>
                        @if($record->sub_parcel)
                        <tr><th>Subparticella</th><td data-pvp-field="subparticella">{{ $record->sub_parcel }}</td></tr>
                        @endif
                        @if($record->sub_unit)
                        <tr><th>Subalterno</th><td data-pvp-field="subalterno">{{ $record->sub_unit }}</td></tr>
                        @endif
                        @if($record->sub_unit_2)
                        <tr><th>Subalterno 2</th><td data-pvp-field="subalterno2">{{ $record->sub_unit_2 }}</td></tr>
                        @endif
                        @if($record->stapled)
                        <tr><th>Graffato</th><td data-pvp-field="graffato">{{ $record->stapled }}</td></tr>
                        @endif
                    </table>
                </div>
                @endforeach
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- datiVendita --}}
    @if($insertion->saleData)
    <div class="section" data-pvp-group="datiVendita">
        <h2>Dati Vendita</h2>
        <table>
            <tr><th>Data e Ora Vendita</th><td data-pvp-field="dataOraVendita">{{ $insertion->saleData->sale_datetime }}</td></tr>
            <tr><th>Tipologia Vendita</th><td data-pvp-field="tipologiaVendita">{{ $insertion->saleData->sale_type }}</td></tr>
            <tr><th>Modalit&agrave; Vendita</th><td data-pvp-field="modalitaVendita">{{ $insertion->saleData->sale_method }}</td></tr>
            @if($insertion->saleData->base_price)
            <tr><th>Prezzo/Valore Base</th><td data-pvp-field="prezzoValoreBase">{{ $insertion->saleData->base_price }}</td></tr>
            @endif
            @if($insertion->saleData->minimum_bid)
            <tr><th>Offerta Minima</th><td data-pvp-field="offertaMinima">{{ $insertion->saleData->minimum_bid }}</td></tr>
            @endif
            @if($insertion->saleData->minimum_raise)
            <tr><th>Rialzo Minimo</th><td data-pvp-field="rialzoMinimo">{{ $insertion->saleData->minimum_raise }}</td></tr>
            @endif
            @if($insertion->saleData->security_deposit)
            <tr><th>Deposito Cauzionale</th><td data-pvp-field="depositoCauzionale">{{ $insertion->saleData->security_deposit }}</td></tr>
            @endif
            @if($insertion->saleData->expense_deposit)
            <tr><th>Deposito Conto Spese</th><td data-pvp-field="depositoContoSpese">{{ $insertion->saleData->expense_deposit }}</td></tr>
            @endif
            <tr><th>Termine Presentazione Offerte</th><td data-pvp-field="terminePresentazioneOfferte">{{ $insertion->saleData->bid_deadline }}</td></tr>
        </table>

        @if($insertion->saleData->venue_address || $insertion->saleData->venue_city)
        <div data-pvp-group="luogoVendita">
            <h3>Luogo Vendita</h3>
            <table>
                @if($insertion->saleData->venue_address)
                <tr><th>Indirizzo</th><td data-pvp-field="indirizzo">{{ $insertion->saleData->venue_address }}</td></tr>
                @endif
                @if($insertion->saleData->venue_street_number)
                <tr><th>Civico</th><td data-pvp-field="civicoEsponente">{{ $insertion->saleData->venue_street_number }}</td></tr>
                @endif
                @if($insertion->saleData->venue_zip)
                <tr><th>CAP</th><td data-pvp-field="capZipCode">{{ $insertion->saleData->venue_zip }}</td></tr>
                @endif
                @if($insertion->saleData->venue_city)
                <tr><th>Citt&agrave;</th><td data-pvp-field="citta">{{ $insertion->saleData->venue_city }}</td></tr>
                @endif
                @if($insertion->saleData->venue_province)
                <tr><th>Provincia</th><td data-pvp-field="provincia">{{ $insertion->saleData->venue_province }}</td></tr>
                @endif
            </table>
        </div>
        @endif

        @if($insertion->saleData->exemption_reason !== null || $insertion->saleData->prepaid_expense !== null || $insertion->saleData->contribution_not_due !== null)
        <div data-pvp-group="pagamentoContributo">
            <h3>Pagamento Contributo</h3>
            @if($insertion->saleData->exemption_reason)
            <div data-pvp-group="esenzione">
                <table>
                    <tr><th>Motivo Esenzione</th><td data-pvp-field="motivoEsenzione">{{ $insertion->saleData->exemption_reason }}</td></tr>
                </table>
            </div>
            @endif
            <table>
                @if($insertion->saleData->prepaid_expense !== null)
                <tr><th>Spesa Prenotata a Debito</th><td data-pvp-field="spesaPrenotataDebito">{{ $insertion->saleData->prepaid_expense ? 'true' : 'false' }}</td></tr>
                @endif
                @if($insertion->saleData->contribution_not_due !== null)
                <tr><th>Contributo Non Dovuto</th><td data-pvp-field="contributoNonDovuto">{{ $insertion->saleData->contribution_not_due ? 'true' : 'false' }}</td></tr>
                @endif
            </table>
        </div>
        @endif
    </div>
    @endif

    {{-- siti --}}
    @foreach($insertion->sites as $site)
    <div class="section" data-pvp-group="siti"
         @if($site->pvp_id) data-pvp-attr-idSito="{{ $site->pvp_id }}" @endif>
        <h2>Sito</h2>
        <table>
            @if($site->type)
            <tr><th>Tipologia</th><td data-pvp-field="tipologia">{{ $site->type }}</td></tr>
            @endif
            @if($site->name)
            <tr><th>Nominativo</th><td data-pvp-field="nominativo">{{ $site->name }}</td></tr>
            @endif
            <tr><th>URL</th><td data-pvp-field="url">{{ $site->url }}</td></tr>
        </table>
    </div>
    @endforeach

    {{-- eventi --}}
    @foreach($insertion->events as $event)
    <div class="section" data-pvp-group="eventi"
         @if($event->pvp_id) data-pvp-attr-idEvento="{{ $event->pvp_id }}" @endif>
        <h2>Evento</h2>
        <table>
            <tr><th>Tipologia</th><td data-pvp-field="tipologia">{{ $event->type }}</td></tr>
            <tr><th>Nota</th><td data-pvp-field="nota">{{ $event->note }}</td></tr>
            <tr><th>Data Pubblicazione</th><td data-pvp-field="dataPubblicazione">{{ $event->publication_date }}</td></tr>
        </table>
    </div>
    @endforeach
</body>
</html>
