<?php

namespace App\Services;

use App\Models\Insertion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PvpIngestionService
{
    public function __construct(
        private PvpXmlParser $parser,
        private InsertionPersistenceService $persistence,
    ) {}

    public function ingest(string $xmlContent): Insertion
    {
        $this->storeRawXml($xmlContent);

        $data = $this->parser->parse($xmlContent);
        $insertion = $this->persistence->persist($data, $xmlContent);

        Log::info('PVP: insertion saved', [
            'pvp_id' => $insertion->pvp_id,
            'id' => $insertion->id,
        ]);

        return $insertion;
    }

    private function storeRawXml(string $xmlContent): void
    {
        preg_match('/idInserzioneEspVendita="([^"]+)"/', $xmlContent, $matches);
        $pvpId = $matches[1] ?? 'unknown_' . time();

        Storage::disk('local')->put("aste_ricevute/asta_{$pvpId}.xml", $xmlContent);
    }
}
