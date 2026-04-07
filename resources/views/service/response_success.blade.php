<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:xsd2="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaResponseXMLSchema">
    <soap:Body>
        <xsd2:inserzioneEspVenditaResponse{{ isset($messageId) ? ' messageId="' . e($messageId) . '"' : '' }}>
            <xsd2:codice>{{ $codice }}</xsd2:codice>
            <xsd2:descrizione>{{ $descrizione }}</xsd2:descrizione>
        </xsd2:inserzioneEspVenditaResponse>
    </soap:Body>
</soap:Envelope>
