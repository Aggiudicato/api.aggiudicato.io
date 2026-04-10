<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:xsd2="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaResponseXMLSchema">
    <soap:Body>
        {{-- $messageIdAttr is pre-escaped in PvpController::soapResponse --}}
        <xsd2:inserzioneEspVenditaResponse{!! $messageIdAttr !!}>
            <xsd2:codice>{{ $codice }}</xsd2:codice>
            <xsd2:descrizione>{{ $descrizione }}</xsd2:descrizione>
        </xsd2:inserzioneEspVenditaResponse>
    </soap:Body>
</soap:Envelope>
