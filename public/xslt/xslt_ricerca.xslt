<?xml version="1.0" encoding="UTF-8"?>
<!--
    XSLT_RICERCA for PVP "Sito Pubblicità" accreditation.

    Contract (Allegato A v1.4, Appendice A):
    given the XHTML search page filtered by the insertion identifier,
    output the single detail URL as plain text. No wrapping element, no
    extra markup — the Ministry compares this output against the ACK URL
    returned by our ricezioneRichiestaPubblicazione handler.

    Selector is attribute-based so column reordering in the search page
    cannot break accreditation: an <a> anywhere in the document marked
    with data-pvp-listing-url="1" wins.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml">

    <xsl:output method="text" encoding="UTF-8" />

    <xsl:template match="/">
        <xsl:value-of select="(//xhtml:a[@data-pvp-listing-url='1'])[1]/@href" />
    </xsl:template>
</xsl:stylesheet>
