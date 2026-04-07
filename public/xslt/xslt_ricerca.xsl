<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    exclude-result-prefixes="xhtml">

    <xsl:output method="xml" encoding="UTF-8" indent="yes" />

    <!-- XSLT Ricerca: trasforma la pagina XHTML di ricerca annunci
         in un XML con gli URL dei dettagli per il PVP -->

    <xsl:template match="/">
        <risultatiRicerca>
            <xsl:for-each select="//xhtml:table/xhtml:tbody/xhtml:tr">
                <annuncio>
                    <idInserzione>
                        <xsl:value-of select="xhtml:td[1]" />
                    </idInserzione>
                    <tribunale>
                        <xsl:value-of select="xhtml:td[2]" />
                    </tribunale>
                    <tipoProcedura>
                        <xsl:value-of select="xhtml:td[3]" />
                    </tipoProcedura>
                    <descrizione>
                        <xsl:value-of select="xhtml:td[4]" />
                    </descrizione>
                    <dataVendita>
                        <xsl:value-of select="xhtml:td[5]" />
                    </dataVendita>
                    <prezzoBase>
                        <xsl:value-of select="xhtml:td[6]" />
                    </prezzoBase>
                    <urlDettaglio>
                        <xsl:value-of select="xhtml:td[7]/xhtml:a/@href" />
                    </urlDettaglio>
                </annuncio>
            </xsl:for-each>
        </risultatiRicerca>
    </xsl:template>
</xsl:stylesheet>
