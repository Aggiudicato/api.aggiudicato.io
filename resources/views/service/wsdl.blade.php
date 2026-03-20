<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<wsdl:definitions xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" 
    xmlns:tns="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/Siti/" 
    xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" 
    xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
    name="Siti" 
    targetNamespace="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/Siti/" 
    xmlns:xsd1="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema" 
    xmlns:xsd2="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaResponseXMLSchema">

    <wsdl:types>
        <xsd:schema targetNamespace="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema" 
            xmlns:tns="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema" 
            elementFormDefault="qualified">

            <xsd:element name="inserzioneEspVendita" type="tns:InserzioneEsperimentoVendita" />

            <xsd:complexType name="InserzioneEsperimentoVendita">
                <xsd:sequence>
                    <xsd:element name="tipologiaInserzione" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="datiProcedura" type="tns:DatiProcedura" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="lotto" type="tns:Lotto" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="datiVendita" type="tns:DatiVendita" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="siti" type="tns:Sito" minOccurs="0" maxOccurs="unbounded" />
                    <xsd:element name="giorniPubblicita" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="dataPubblicazione" type="xsd:date" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="eventi" type="tns:Evento" minOccurs="0" maxOccurs="unbounded" />
                </xsd:sequence>
                <xsd:attribute name="idInserzioneEspVendita" type="xsd:string" use="required" />
                <xsd:attribute name="messageId" type="xsd:string" />
            </xsd:complexType>

            <xsd:complexType name="DatiProcedura">
                <xsd:sequence>
                    <xsd:element name="tipoProcedura" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="proceduraGiudiziaria" type="tns:ProceduraGiudiziaria" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="altraVendita" type="tns:AltraVendita" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="soggetti" type="tns:Soggetto" minOccurs="1" maxOccurs="unbounded" />
                </xsd:sequence>
                <xsd:attribute name="idProcedura" type="xsd:string" use="required" />
            </xsd:complexType>

            <xsd:complexType name="ProceduraGiudiziaria">
                <xsd:sequence>
                    <xsd:element name="tribunale" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="registro" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="rito" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="numeroProcedura" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="annoProcedura" type="xsd:string" minOccurs="1" maxOccurs="1" />
                </xsd:sequence>
                <xsd:attribute name="idProcedura" type="xsd:string" use="required" />
                <xsd:attribute name="idTribunale" type="xsd:string" use="required" />
                <xsd:attribute name="idRito" type="xsd:string" use="required" />
                <xsd:attribute name="idRegistro" type="xsd:string" use="required" />
            </xsd:complexType>

            <xsd:complexType name="AltraVendita">
                <xsd:sequence>
                    <xsd:element name="procedimento" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="numeroProcedimento" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="annoProcedimento" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="nomeTitolarePegno" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="cognomeTitolarePegno" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="codFisTitolarePegno" type="xsd:string" minOccurs="1" maxOccurs="1" />
                </xsd:sequence>
                <xsd:attribute name="idProcedura" type="xsd:string" use="required" />
                <xsd:attribute name="idRito" type="xsd:string" use="required" />
                <xsd:attribute name="idRegistro" type="xsd:string" use="required" />
            </xsd:complexType>

            <xsd:complexType name="Soggetto">
                <xsd:sequence>
                    <xsd:element name="tipo" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="nome" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="cognome" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="codiceFiscale" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="email" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="cellulare" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="telefono" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="procedeOpVendita" type="xsd:boolean" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="soggVisitaBene" type="xsd:boolean" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
                <xsd:attribute name="idAnagrafica" type="xsd:long" />
            </xsd:complexType>

            <xsd:complexType name="DatiVendita">
                <xsd:sequence>
                    <xsd:element name="dataOraVendita" type="xsd:dateTime" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="tipologiaVendita" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="modalitaVendita" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="luogoVendita" type="tns:Indirizzo" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="prezzoValoreBase" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="offertaMinima" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="rialzoMinimo" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="depositoCauzionale" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="depositoContoSpese" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="pagamentoContributo" type="tns:PagamentoContributo" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="terminePresentazioneOfferte" type="xsd:dateTime" minOccurs="1" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="PagamentoContributo">
                <xsd:sequence>
                    <xsd:element name="esenzione" type="tns:EsenzionePagamento" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="spesaPrenotataDebito" type="xsd:boolean" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="contributoNonDovuto" type="xsd:boolean" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="EsenzionePagamento">
                <xsd:sequence>
                    <xsd:element name="motivoEsenzione" type="xsd:string" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="Indirizzo">
                <xsd:sequence>
                    <xsd:element name="indirizzo" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="civicoEsponente" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="capZipCode" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="citta" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="provincia" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="regione" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="nazione" type="xsd:string" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="Lotto">
                <xsd:sequence>
                    <xsd:element name="primoIdentificativoLotto" type="xsd:string" nillable="false" />
                    <xsd:element name="codice" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="genere" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="categoria" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="ubicazione" type="tns:Indirizzo" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="descrizioneIT" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="descrizioneDE" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="beni" type="tns:Bene" minOccurs="1" maxOccurs="unbounded" />
                </xsd:sequence>
                <xsd:attribute name="idLotto" type="xsd:long" use="required" />
            </xsd:complexType>

            <xsd:complexType name="Bene">
                <xsd:sequence>
                    <xsd:element name="primoIdentificativoBene" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="tipologia" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="categoria" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="ubicazione" type="tns:Indirizzo" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="descrizioneIT" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="descrizioneDE" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="beneImmobile" type="tns:BeneImmobile" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="beneMobile" type="tns:BeneMobile" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="beneAzienda" type="tns:BeneAzienda" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
                <xsd:attribute name="idBene" type="xsd:string" />
            </xsd:complexType>

            <xsd:complexType name="BeneImmobile">
                <xsd:sequence>
                    <xsd:element name="datiCatastali" type="tns:DatiCatastali" minOccurs="0" maxOccurs="unbounded" />
                    <xsd:element name="denuncia" type="tns:DatiDenuncia" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="disponibilita" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="mq" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="vani" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="piano" type="xsd:string" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="BeneMobile">
                <xsd:sequence>
                    <xsd:element name="luogoVisioneBene" type="tns:Indirizzo" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="modalitaConsegna" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="luogoRitiroBene" type="tns:Indirizzo" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="BeneAzienda">
                <xsd:sequence>
                    <xsd:element name="categorieMerceologicheAteco" type="xsd:string" minOccurs="0" maxOccurs="unbounded" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="DatiCatastali">
                <xsd:sequence>
                    <xsd:element name="sezione" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="foglio" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="particella" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="subparticella" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="subalterno" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="subalterno2" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="graffato" type="xsd:string" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="DatiDenuncia">
                <xsd:sequence>
                    <xsd:element name="tipologia" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="anno" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="numero" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="data" type="xsd:date" minOccurs="0" maxOccurs="1" />
                </xsd:sequence>
            </xsd:complexType>

            <xsd:complexType name="Sito">
                <xsd:sequence>
                    <xsd:element name="tipologia" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="nominativo" type="xsd:string" minOccurs="0" maxOccurs="1" />
                    <xsd:element name="url" type="xsd:string" minOccurs="1" maxOccurs="1" />
                </xsd:sequence>
                <xsd:attribute name="idSito" type="xsd:string" />
            </xsd:complexType>

            <xsd:complexType name="Evento">
                <xsd:sequence>
                    <xsd:element name="tipologia" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="nota" type="xsd:string" minOccurs="1" maxOccurs="1" />
                    <xsd:element name="dataPubblicazione" type="xsd:string" minOccurs="1" maxOccurs="1" />
                </xsd:sequence>
                <xsd:attribute name="idEvento" type="xsd:string" />
            </xsd:complexType>
        </xsd:schema>

        <xsd:schema targetNamespace="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaResponseXMLSchema" 
            xmlns:tns="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaResponseXMLSchema" 
            elementFormDefault="qualified">
            
            <xsd:element name="inserzioneEspVenditaResponse" type="tns:InserizioneEsperimentoVenditaResponse" />
            
            <xsd:complexType name="InserizioneEsperimentoVenditaResponse">
                <xsd:sequence>
                    <xsd:element name="codice" type="xsd:string" />
                    <xsd:element name="descrizione" type="xsd:string" />
                </xsd:sequence>
                <xsd:attribute name="messageId" type="xsd:string" />
            </xsd:complexType>
        </xsd:schema>
    </wsdl:types>

    <wsdl:message name="ricezioneRichiestaPubblicazioneRequest">
        <wsdl:part name="espVendita" element="xsd1:inserzioneEspVendita" />
    </wsdl:message>
    <wsdl:message name="ricezioneRichiestaPubblicazioneResponse">
        <wsdl:part name="espVenditaResp" element="xsd2:inserzioneEspVenditaResponse" />
    </wsdl:message>

    <wsdl:portType name="Siti">
        <wsdl:operation name="ricezioneRichiestaPubblicazione">
            <wsdl:input message="tns:ricezioneRichiestaPubblicazioneRequest" />
            <wsdl:output message="tns:ricezioneRichiestaPubblicazioneResponse" />
        </wsdl:operation>
    </wsdl:portType>

    <wsdl:binding name="SitiSoapBinding" type="tns:Siti">
        <soap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http" />
        <wsdl:operation name="ricezioneRichiestaPubblicazione">
            <soap:operation soapAction="" />
            <wsdl:input>
                <soap:body use="literal" />
            </wsdl:input>
            <wsdl:output>
                <soap:body use="literal" />
            </wsdl:output>
        </wsdl:operation>
    </wsdl:binding>

    <wsdl:service name="SitiService">
        <wsdl:port binding="tns:SitiSoapBinding" name="SitiPort">
            <soap:address location="https://api.aggiudicato.io/pvp/service" />
        </wsdl:port>
    </wsdl:service>
</wsdl:definitions>