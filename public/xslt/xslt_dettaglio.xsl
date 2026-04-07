<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    exclude-result-prefixes="xhtml">

    <xsl:output method="xml" encoding="UTF-8" indent="yes" />

    <!-- XSLT Dettaglio: trasforma la pagina XHTML di dettaglio annuncio
         in un XML conforme allo schema PVP del Ministero -->

    <xsl:template match="/">
        <dettaglioAnnuncio>
            <!-- Dati principali dall'header -->
            <idInserzione>
                <xsl:value-of select="//xhtml:body/xhtml:p[1]/xhtml:strong" />
            </idInserzione>
            <dataPubblicazione>
                <xsl:value-of select="//xhtml:body/xhtml:p[2]/xhtml:strong" />
            </dataPubblicazione>
            <tipologiaInserzione>
                <xsl:value-of select="//xhtml:body/xhtml:p[3]/xhtml:strong" />
            </tipologiaInserzione>

            <!-- Procedura -->
            <xsl:if test="//xhtml:div[@id='procedura']">
                <procedura>
                    <xsl:for-each select="//xhtml:div[@id='procedura']/xhtml:table[1]/xhtml:tr">
                        <xsl:element name="{translate(normalize-space(xhtml:th), ' ', '')}">
                            <xsl:value-of select="xhtml:td" />
                        </xsl:element>
                    </xsl:for-each>
                    <!-- Soggetti -->
                    <xsl:if test="//xhtml:div[@id='procedura']/xhtml:table[2]">
                        <soggetti>
                            <xsl:for-each select="//xhtml:div[@id='procedura']/xhtml:table[2]/xhtml:tbody/xhtml:tr">
                                <soggetto>
                                    <tipo><xsl:value-of select="xhtml:td[1]" /></tipo>
                                    <nominativo><xsl:value-of select="xhtml:td[2]" /></nominativo>
                                    <codiceFiscale><xsl:value-of select="xhtml:td[3]" /></codiceFiscale>
                                    <email><xsl:value-of select="xhtml:td[4]" /></email>
                                    <telefono><xsl:value-of select="xhtml:td[5]" /></telefono>
                                </soggetto>
                            </xsl:for-each>
                        </soggetti>
                    </xsl:if>
                </procedura>
            </xsl:if>

            <!-- Lotto -->
            <xsl:if test="//xhtml:div[@id='lotto']">
                <lotto>
                    <xsl:for-each select="//xhtml:div[@id='lotto']/xhtml:table[1]/xhtml:tr">
                        <xsl:element name="{translate(normalize-space(xhtml:th), ' ', '')}">
                            <xsl:value-of select="xhtml:td" />
                        </xsl:element>
                    </xsl:for-each>
                    <!-- Beni -->
                    <xsl:for-each select="//xhtml:div[starts-with(@id, 'bene-')]">
                        <bene>
                            <xsl:for-each select="xhtml:table[1]/xhtml:tr">
                                <xsl:element name="{translate(normalize-space(xhtml:th), ' ', '')}">
                                    <xsl:value-of select="xhtml:td" />
                                </xsl:element>
                            </xsl:for-each>
                            <!-- Dati Catastali -->
                            <xsl:if test="xhtml:table[2]">
                                <datiCatastali>
                                    <xsl:for-each select="xhtml:table[2]/xhtml:tbody/xhtml:tr">
                                        <catastale>
                                            <sezione><xsl:value-of select="xhtml:td[1]" /></sezione>
                                            <foglio><xsl:value-of select="xhtml:td[2]" /></foglio>
                                            <particella><xsl:value-of select="xhtml:td[3]" /></particella>
                                            <subalterno><xsl:value-of select="xhtml:td[4]" /></subalterno>
                                        </catastale>
                                    </xsl:for-each>
                                </datiCatastali>
                            </xsl:if>
                        </bene>
                    </xsl:for-each>
                </lotto>
            </xsl:if>

            <!-- Dati Vendita -->
            <xsl:if test="//xhtml:div[@id='dati-vendita']">
                <datiVendita>
                    <xsl:for-each select="//xhtml:div[@id='dati-vendita']/xhtml:table/xhtml:tr">
                        <xsl:element name="{translate(normalize-space(xhtml:th), ' ', '')}">
                            <xsl:value-of select="xhtml:td" />
                        </xsl:element>
                    </xsl:for-each>
                </datiVendita>
            </xsl:if>

            <!-- Siti -->
            <xsl:if test="//xhtml:div[@id='siti']">
                <siti>
                    <xsl:for-each select="//xhtml:div[@id='siti']/xhtml:table/xhtml:tbody/xhtml:tr">
                        <sito>
                            <tipologia><xsl:value-of select="xhtml:td[1]" /></tipologia>
                            <nominativo><xsl:value-of select="xhtml:td[2]" /></nominativo>
                            <url><xsl:value-of select="xhtml:td[3]/xhtml:a/@href" /></url>
                        </sito>
                    </xsl:for-each>
                </siti>
            </xsl:if>

            <!-- Eventi -->
            <xsl:if test="//xhtml:div[@id='eventi']">
                <eventi>
                    <xsl:for-each select="//xhtml:div[@id='eventi']/xhtml:table/xhtml:tbody/xhtml:tr">
                        <evento>
                            <tipologia><xsl:value-of select="xhtml:td[1]" /></tipologia>
                            <nota><xsl:value-of select="xhtml:td[2]" /></nota>
                            <dataPubblicazione><xsl:value-of select="xhtml:td[3]" /></dataPubblicazione>
                        </evento>
                    </xsl:for-each>
                </eventi>
            </xsl:if>
        </dettaglioAnnuncio>
    </xsl:template>
</xsl:stylesheet>
