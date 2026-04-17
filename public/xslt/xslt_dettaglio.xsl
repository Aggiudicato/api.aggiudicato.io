<?xml version="1.0" encoding="UTF-8"?>
<!--
    XSLT_DETTAGLIO for PVP "Sito Pubblicità" accreditation.

    Contract (Allegato A v1.4, Appendice A):
    transform the detail XHTML page into the canonical inserzioneEspVendita
    XML element in the Ministry's namespace, preserving the element order
    received via SOAP. The Ministry then canonicalizes both its original
    SOAP payload and this output (c14n) and compares them, byte for byte.

    Element names, attribute names and hierarchy are therefore NOT our
    choice — they are dictated by InserzioneEsperimentoVenditaXMLSchema.
    Every piece of data on the page is tagged with:
      - data-pvp-field="<canonicalName>" for simple values
      - data-pvp-group="<canonicalName>" for nested / repeatable elements
      - data-pvp-attr-<name>="..." for element attributes
    The XSLT walks the DOM, skips decorative markup (tables, divs, headings),
    and emits one XML node per tagged element in document order.

    Tags explicitly omitted per spec: allegatiInserzione, immaginiBene,
    dataPrevistaTermineVendita, spesaPrenotataDebito48 — never rendered by
    the view so never emitted here.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns:pvp="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema"
    exclude-result-prefixes="xhtml">

    <xsl:output method="xml" encoding="UTF-8" indent="no" omit-xml-declaration="no" />
    <xsl:strip-space elements="*" />

    <xsl:template match="/">
        <xsl:apply-templates select="(//xhtml:*[@data-pvp-group='inserzioneEspVendita'])[1]" />
    </xsl:template>

    <xsl:template match="xhtml:*[@data-pvp-group]">
        <xsl:element name="{@data-pvp-group}" namespace="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema">
            <xsl:for-each select="@*[starts-with(name(), 'data-pvp-attr-')]">
                <xsl:attribute name="{substring-after(name(), 'data-pvp-attr-')}">
                    <xsl:value-of select="." />
                </xsl:attribute>
            </xsl:for-each>
            <xsl:apply-templates />
        </xsl:element>
    </xsl:template>

    <xsl:template match="xhtml:*[@data-pvp-field]">
        <xsl:element name="{@data-pvp-field}" namespace="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema">
            <xsl:value-of select="normalize-space(.)" />
        </xsl:element>
    </xsl:template>

    <xsl:template match="xhtml:*">
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="text()" />
</xsl:stylesheet>
