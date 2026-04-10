<?php

namespace App\Services;

use App\Support\SafeXml;

/**
 * Parse the Ministry `inserzioneEspVendita` SOAP payload into a flat array.
 *
 * The ministry XML is verbose and deeply nested with many optional branches
 * (proceduraGiudiziaria / altraVendita; beneImmobile / beneMobile / beneAzienda).
 * This parser flattens it into a shape consumable by InsertionPersistenceService.
 *
 * Namespace handling uses a three-tier fallback because real ministry samples
 * sometimes declare the InserzioneEsperimentoVenditaXMLSchema namespace on the
 * root element, sometimes leave children in the default namespace, and
 * sometimes use explicit `xsd1:` prefixes. The `getChild` / `getChildrenList`
 * helpers try all three in order so the parser tolerates any of these forms
 * without the caller knowing which shape was sent.
 *
 * Throws RuntimeException on XML errors or when the root `inserzioneEspVendita`
 * element or its `idInserzioneEspVendita` attribute is missing — these are
 * the only hard requirements; everything else is best-effort.
 */
class PvpXmlParser
{
    private const NS = 'http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema';

    /**
     * Parse the Ministry SOAP XML and return a structured array.
     */
    public function parse(string $xmlContent): array
    {
        $xml = $this->loadXml($xmlContent);
        $body = $this->extractBody($xml);

        return [
            'insertion' => $this->parseInsertion($body),
            'procedure' => $this->parseProcedure($body),
            'lot' => $this->parseLot($body),
            'sale_data' => $this->parseSaleData($body),
            'sites' => $this->parseSites($body),
            'events' => $this->parseEvents($body),
        ];
    }

    private function loadXml(string $xmlContent): \SimpleXMLElement
    {
        return SafeXml::loadSimpleXml($xmlContent);
    }

    private function extractBody(\SimpleXMLElement $xml): \SimpleXMLElement
    {
        $namespaces = $xml->getNamespaces(true);
        $soapNs = $namespaces['soap'] ?? $namespaces['soapenv'] ?? $namespaces['SOAP-ENV'] ?? 'http://schemas.xmlsoap.org/soap/envelope/';

        $xml->registerXPathNamespace('soap', $soapNs);
        $xml->registerXPathNamespace('xsd1', self::NS);

        $body = $xml->xpath('//soap:Body/xsd1:inserzioneEspVendita');

        if (empty($body)) {
            $body = $xml->xpath('//inserzioneEspVendita');
        }

        if (empty($body)) {
            throw new \RuntimeException('Element inserzioneEspVendita not found in XML');
        }

        return $body[0];
    }

    private function parseInsertion(\SimpleXMLElement $body): array
    {
        $attrs = $body->attributes();
        $idInserzione = (string) ($attrs['idInserzioneEspVendita'] ?? '');

        if (empty($idInserzione)) {
            throw new \RuntimeException('Attribute idInserzioneEspVendita missing');
        }

        $children = $this->getChildren($body);

        return [
            'pvp_id' => $idInserzione,
            'message_id' => (string) ($attrs['messageId'] ?? '') ?: null,
            'type' => $this->getChildValue($children, 'tipologiaInserzione', ''),
            'advertising_days' => $this->getChildValue($children, 'giorniPubblicita'),
            'publication_date' => $this->getChildValue($children, 'dataPubblicazione'),
        ];
    }

    private function parseProcedure(\SimpleXMLElement $body): ?array
    {
        $datiProcedura = $this->getChild($body, 'datiProcedura');
        if (! $datiProcedura) {
            return null;
        }

        $attrs = $datiProcedura->attributes();
        $children = $this->getChildren($datiProcedura);

        $data = [
            'pvp_id' => (string) ($attrs['idProcedura'] ?? ''),
            'type' => $this->getChildValue($children, 'tipoProcedura', ''),
        ];

        // ProceduraGiudiziaria
        $pg = $this->getChild($datiProcedura, 'proceduraGiudiziaria');
        if ($pg) {
            $pgAttrs = $pg->attributes();
            $pgChildren = $this->getChildren($pg);
            $data = array_merge($data, [
                'court_pvp_id' => (string) ($pgAttrs['idTribunale'] ?? ''),
                'rite_pvp_id' => (string) ($pgAttrs['idRito'] ?? ''),
                'registry_pvp_id' => (string) ($pgAttrs['idRegistro'] ?? ''),
                'court' => $this->getChildValue($pgChildren, 'tribunale'),
                'registry' => $this->getChildValue($pgChildren, 'registro'),
                'rite' => $this->getChildValue($pgChildren, 'rito'),
                'number' => $this->getChildValue($pgChildren, 'numeroProcedura'),
                'year' => $this->getChildValue($pgChildren, 'annoProcedura'),
            ]);
        }

        // AltraVendita
        $av = $this->getChild($datiProcedura, 'altraVendita');
        if ($av) {
            $avChildren = $this->getChildren($av);
            $data = array_merge($data, [
                'proceeding' => $this->getChildValue($avChildren, 'procedimento'),
                'proceeding_number' => $this->getChildValue($avChildren, 'numeroProcedimento'),
                'proceeding_year' => $this->getChildValue($avChildren, 'annoProcedimento'),
                'pledge_holder_first_name' => $this->getChildValue($avChildren, 'nomeTitolarePegno'),
                'pledge_holder_last_name' => $this->getChildValue($avChildren, 'cognomeTitolarePegno'),
                'pledge_holder_tax_code' => $this->getChildValue($avChildren, 'codFisTitolarePegno'),
            ]);
        }

        // Soggetti
        $data['subjects'] = $this->parseSubjects($datiProcedura);

        return $data;
    }

    private function parseSubjects(\SimpleXMLElement $datiProcedura): array
    {
        $soggetti = $this->getChildrenList($datiProcedura, 'soggetti');
        $result = [];

        foreach ($soggetti as $subject) {
            $attrs = $subject->attributes();
            $children = $this->getChildren($subject);

            $result[] = [
                'pvp_id' => (string) ($attrs['idAnagrafica'] ?? null) ?: null,
                'type' => $this->getChildValue($children, 'tipo', ''),
                'first_name' => $this->getChildValue($children, 'nome'),
                'last_name' => $this->getChildValue($children, 'cognome', ''),
                'tax_code' => $this->getChildValue($children, 'codiceFiscale'),
                'email' => $this->getChildValue($children, 'email'),
                'mobile' => $this->getChildValue($children, 'cellulare'),
                'phone' => $this->getChildValue($children, 'telefono'),
                'handles_sale' => $this->getChildBool($children, 'procedeOpVendita'),
                'handles_viewing' => $this->getChildBool($children, 'soggVisitaBene'),
            ];
        }

        return $result;
    }

    private function parseLot(\SimpleXMLElement $body): ?array
    {
        $lottoXml = $this->getChild($body, 'lotto');
        if (! $lottoXml) {
            return null;
        }

        $attrs = $lottoXml->attributes();
        $children = $this->getChildren($lottoXml);
        $ubicazione = $this->parseIndirizzo($lottoXml, 'ubicazione', 'location_');

        $lot = [
            'pvp_id' => (string) ($attrs['idLotto'] ?? 0),
            'primary_id' => $this->getChildValue($children, 'primoIdentificativoLotto', ''),
            'code' => $this->getChildValue($children, 'codice', ''),
            'genre' => $this->getChildValue($children, 'genere', ''),
            'category' => $this->getChildValue($children, 'categoria', ''),
            'description_it' => $this->getChildValue($children, 'descrizioneIT', ''),
            'description_de' => $this->getChildValue($children, 'descrizioneDE'),
            ...$ubicazione,
        ];

        // Assets
        $lot['assets'] = [];
        foreach ($this->getChildrenList($lottoXml, 'beni') as $beneXml) {
            $lot['assets'][] = $this->parseAsset($beneXml);
        }

        return $lot;
    }

    private function parseAsset(\SimpleXMLElement $beneXml): array
    {
        $attrs = $beneXml->attributes();
        $children = $this->getChildren($beneXml);
        $ubicazione = $this->parseIndirizzo($beneXml, 'ubicazione', 'location_');

        $data = [
            'pvp_id' => (string) ($attrs['idBene'] ?? null) ?: null,
            'primary_id' => $this->getChildValue($children, 'primoIdentificativoBene'),
            'type' => $this->getChildValue($children, 'tipologia', ''),
            'category' => $this->getChildValue($children, 'categoria', ''),
            'description_it' => $this->getChildValue($children, 'descrizioneIT', ''),
            'description_de' => $this->getChildValue($children, 'descrizioneDE'),
            ...$ubicazione,
        ];

        // BeneImmobile
        $immobile = $this->getChild($beneXml, 'beneImmobile');
        if ($immobile) {
            $immChildren = $this->getChildren($immobile);
            $data['availability'] = $this->getChildValue($immChildren, 'disponibilita');
            $data['square_meters'] = $this->getChildValue($immChildren, 'mq');
            $data['rooms'] = $this->getChildValue($immChildren, 'vani');
            $data['floor'] = $this->getChildValue($immChildren, 'piano');

            $denuncia = $this->getChild($immobile, 'denuncia');
            if ($denuncia) {
                $denChildren = $this->getChildren($denuncia);
                $data['complaint_type'] = $this->getChildValue($denChildren, 'tipologia');
                $data['complaint_year'] = $this->getChildValue($denChildren, 'anno');
                $data['complaint_number'] = $this->getChildValue($denChildren, 'numero');
                $data['complaint_date'] = $this->getChildValue($denChildren, 'data');
            }

            // Cadastral records
            $data['cadastral_records'] = [];
            foreach ($this->getChildrenList($immobile, 'datiCatastali') as $catastale) {
                $catChildren = $this->getChildren($catastale);
                $data['cadastral_records'][] = [
                    'section' => $this->getChildValue($catChildren, 'sezione'),
                    'sheet' => $this->getChildValue($catChildren, 'foglio', ''),
                    'parcel' => $this->getChildValue($catChildren, 'particella', ''),
                    'sub_parcel' => $this->getChildValue($catChildren, 'subparticella'),
                    'sub_unit' => $this->getChildValue($catChildren, 'subalterno'),
                    'sub_unit_2' => $this->getChildValue($catChildren, 'subalterno2'),
                    'stapled' => $this->getChildValue($catChildren, 'graffato'),
                ];
            }
        }

        // BeneMobile
        $mobile = $this->getChild($beneXml, 'beneMobile');
        if ($mobile) {
            $mobChildren = $this->getChildren($mobile);
            $data['delivery_method'] = $this->getChildValue($mobChildren, 'modalitaConsegna');
            $data['viewing_location'] = $this->parseIndirizzoJson($mobile, 'luogoVisioneBene');
            $data['pickup_location'] = $this->parseIndirizzoJson($mobile, 'luogoRitiroBene');
        }

        // BeneAzienda
        $azienda = $this->getChild($beneXml, 'beneAzienda');
        if ($azienda) {
            $cats = [];
            foreach ($this->getChildrenList($azienda, 'categorieMerceologicheAteco') as $cat) {
                $cats[] = (string) $cat;
            }
            $data['ateco_categories'] = $cats ?: null;
        }

        return $data;
    }

    private function parseSaleData(\SimpleXMLElement $body): ?array
    {
        $dv = $this->getChild($body, 'datiVendita');
        if (! $dv) {
            return null;
        }

        $children = $this->getChildren($dv);
        $venue = $this->parseIndirizzo($dv, 'luogoVendita', 'venue_');

        $data = [
            'sale_datetime' => $this->getChildValue($children, 'dataOraVendita', ''),
            'sale_type' => $this->getChildValue($children, 'tipologiaVendita', ''),
            'sale_method' => $this->getChildValue($children, 'modalitaVendita', ''),
            'base_price' => $this->getChildValue($children, 'prezzoValoreBase'),
            'minimum_bid' => $this->getChildValue($children, 'offertaMinima'),
            'minimum_raise' => $this->getChildValue($children, 'rialzoMinimo'),
            'security_deposit' => $this->getChildValue($children, 'depositoCauzionale'),
            'expense_deposit' => $this->getChildValue($children, 'depositoContoSpese'),
            'bid_deadline' => $this->getChildValue($children, 'terminePresentazioneOfferte', ''),
            ...$venue,
        ];

        $pc = $this->getChild($dv, 'pagamentoContributo');
        if ($pc) {
            $pcChildren = $this->getChildren($pc);
            $esenzione = $this->getChild($pc, 'esenzione');
            if ($esenzione) {
                $esChildren = $this->getChildren($esenzione);
                $data['exemption_reason'] = $this->getChildValue($esChildren, 'motivoEsenzione');
            }
            $data['prepaid_expense'] = $this->getChildBool($pcChildren, 'spesaPrenotataDebito');
            $data['contribution_not_due'] = $this->getChildBool($pcChildren, 'contributoNonDovuto');
        }

        return $data;
    }

    private function parseSites(\SimpleXMLElement $body): array
    {
        $result = [];
        foreach ($this->getChildrenList($body, 'siti') as $sitoXml) {
            $attrs = $sitoXml->attributes();
            $children = $this->getChildren($sitoXml);

            $result[] = [
                'pvp_id' => (string) ($attrs['idSito'] ?? null) ?: null,
                'type' => $this->getChildValue($children, 'tipologia'),
                'name' => $this->getChildValue($children, 'nominativo'),
                'url' => $this->getChildValue($children, 'url', ''),
            ];
        }

        return $result;
    }

    private function parseEvents(\SimpleXMLElement $body): array
    {
        $result = [];
        foreach ($this->getChildrenList($body, 'eventi') as $eventoXml) {
            $attrs = $eventoXml->attributes();
            $children = $this->getChildren($eventoXml);

            $result[] = [
                'pvp_id' => (string) ($attrs['idEvento'] ?? null) ?: null,
                'type' => $this->getChildValue($children, 'tipologia', ''),
                'note' => $this->getChildValue($children, 'nota', ''),
                'publication_date' => $this->getChildValue($children, 'dataPubblicazione', ''),
            ];
        }

        return $result;
    }

    // --- Helper methods ---

    private function getChild(\SimpleXMLElement $parent, string $name): ?\SimpleXMLElement
    {
        $child = $parent->children(self::NS)->$name ?? null;
        if ($child && $child->count() > 0) {
            return $child;
        }

        $child = $parent->$name ?? null;
        if ($child && strlen((string) $child) > 0) {
            return $child;
        }

        $parent->registerXPathNamespace('xsd1', self::NS);
        $result = $parent->xpath("xsd1:{$name}");
        if (! empty($result)) {
            return $result[0];
        }

        $result = $parent->xpath($name);

        return $result[0] ?? null;
    }

    private function getChildren(\SimpleXMLElement $parent): \SimpleXMLElement
    {
        $children = $parent->children(self::NS);

        if ($children->count() === 0) {
            $children = $parent->children();
        }

        return $children;
    }

    private function getChildrenList(\SimpleXMLElement $parent, string $name): array
    {
        $parent->registerXPathNamespace('xsd1', self::NS);
        $result = $parent->xpath("xsd1:{$name}");

        if (empty($result)) {
            $result = $parent->xpath($name);
        }

        return $result ?: [];
    }

    private function getChildValue(\SimpleXMLElement $children, string $name, ?string $default = null): ?string
    {
        $value = isset($children->$name) ? (string) $children->$name : null;

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    private function getChildBool(\SimpleXMLElement $children, string $name): ?bool
    {
        $value = $this->getChildValue($children, $name);
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function parseIndirizzo(\SimpleXMLElement $parent, string $elementName, string $prefix = 'location_'): array
    {
        $indirizzo = $this->getChild($parent, $elementName);
        if (! $indirizzo) {
            return [];
        }

        $children = $this->getChildren($indirizzo);

        return [
            "{$prefix}address" => $this->getChildValue($children, 'indirizzo'),
            "{$prefix}street_number" => $this->getChildValue($children, 'civicoEsponente'),
            "{$prefix}zip" => $this->getChildValue($children, 'capZipCode'),
            "{$prefix}city" => $this->getChildValue($children, 'citta'),
            "{$prefix}province" => $this->getChildValue($children, 'provincia'),
            "{$prefix}region" => $this->getChildValue($children, 'regione'),
            "{$prefix}country" => $this->getChildValue($children, 'nazione'),
        ];
    }

    private function parseIndirizzoJson(\SimpleXMLElement $parent, string $elementName): ?array
    {
        $indirizzo = $this->getChild($parent, $elementName);
        if (! $indirizzo) {
            return null;
        }

        $children = $this->getChildren($indirizzo);

        return [
            'address' => $this->getChildValue($children, 'indirizzo'),
            'street_number' => $this->getChildValue($children, 'civicoEsponente'),
            'zip' => $this->getChildValue($children, 'capZipCode'),
            'city' => $this->getChildValue($children, 'citta'),
            'province' => $this->getChildValue($children, 'provincia'),
            'region' => $this->getChildValue($children, 'regione'),
            'country' => $this->getChildValue($children, 'nazione'),
        ];
    }
}
