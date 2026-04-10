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
        $data = $this->parser->parse($xmlContent);
        $pvpId = $data['insertion']['pvp_id'];

        $this->storeRawXml($xmlContent, $pvpId);

        $insertion = $this->persistence->persist($data, $xmlContent);

        Log::info('PVP: insertion saved', [
            'pvp_id' => $insertion->pvp_id,
            'id' => $insertion->id,
        ]);

        return $insertion;
    }

    private function storeRawXml(string $xmlContent, string $pvpId): void
    {
        Storage::disk('local')->put("aste_ricevute/asta_{$pvpId}.xml", $xmlContent);
    }
}
