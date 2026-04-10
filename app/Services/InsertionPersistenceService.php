<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\CadastralRecord;
use App\Models\Event;
use App\Models\Insertion;
use App\Models\Lot;
use App\Models\Procedure;
use App\Models\SaleData;
use App\Models\Site;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

/**
 * Persist the parsed ministry payload into the normalized relational schema.
 *
 * Sits between PvpXmlParser (flattens XML to array sections) and the Eloquent
 * models. Owns the transactional boundary and the idempotency contract:
 * re-ingesting the same `pvp_id` updates the insertion row and rebuilds its
 * subtree, so ministry retries cannot produce duplicates or partial state.
 */
class InsertionPersistenceService
{
    /**
     * Persist a parsed PVP payload and all related entities in one transaction.
     *
     * Uses `updateOrCreate` on `pvp_id` for idempotency: the Ministry retries
     * failed inserzioneEspVendita calls until it gets a success response, so
     * re-receiving the same `pvp_id` must not create duplicate records. The
     * old procedure/lot/sale_data/sites/events rows for that insertion are
     * recreated on each ingestion — simpler than diff-and-patch and the
     * Ministry XML is always authoritative.
     *
     * Transactional: if any child persistence fails the whole insertion
     * rolls back, so we never end up with an insertion that has a partial
     * subtree (e.g. lot but no assets).
     */
    public function persist(array $data, string $xmlContent): Insertion
    {
        return DB::transaction(function () use ($data, $xmlContent) {
            $insertion = $this->createInsertion($data['insertion'], $xmlContent);

            if ($data['procedure']) {
                $this->createProcedure($data['procedure'], $insertion);
            }

            if ($data['lot']) {
                $this->createLot($data['lot'], $insertion);
            }

            if ($data['sale_data']) {
                $this->createSaleData($data['sale_data'], $insertion);
            }

            foreach ($data['sites'] as $siteData) {
                $this->createSite($siteData, $insertion);
            }

            foreach ($data['events'] as $eventData) {
                $this->createEvent($eventData, $insertion);
            }

            return $insertion;
        });
    }

    private function createInsertion(array $data, string $xmlContent): Insertion
    {
        $xmlPath = "aste_ricevute/asta_{$data['pvp_id']}.xml";

        return Insertion::updateOrCreate(
            ['pvp_id' => $data['pvp_id']],
            [
                'message_id' => $data['message_id'],
                'type' => $data['type'],
                'advertising_days' => $data['advertising_days'],
                'publication_date' => $data['publication_date'],
                'xml_path' => $xmlPath,
                'xml_raw' => $xmlContent,
                'status' => 'received',
            ]
        );
    }

    private function createProcedure(array $data, Insertion $insertion): void
    {
        $subjects = $data['subjects'] ?? [];
        unset($data['subjects']);

        $procedure = Procedure::create([
            'insertion_id' => $insertion->id,
            ...$data,
        ]);

        foreach ($subjects as $subjectData) {
            Subject::create([
                'procedure_id' => $procedure->id,
                ...$subjectData,
            ]);
        }
    }

    private function createLot(array $data, Insertion $insertion): void
    {
        $assets = $data['assets'] ?? [];
        unset($data['assets']);

        $lot = Lot::create([
            'insertion_id' => $insertion->id,
            ...$data,
        ]);

        foreach ($assets as $assetData) {
            $this->createAsset($assetData, $lot);
        }
    }

    private function createAsset(array $data, Lot $lot): void
    {
        $cadastralRecords = $data['cadastral_records'] ?? [];
        unset($data['cadastral_records']);

        $asset = Asset::create([
            'lot_id' => $lot->id,
            ...$data,
        ]);

        foreach ($cadastralRecords as $recordData) {
            CadastralRecord::create([
                'asset_id' => $asset->id,
                ...$recordData,
            ]);
        }
    }

    private function createSaleData(array $data, Insertion $insertion): void
    {
        SaleData::create([
            'insertion_id' => $insertion->id,
            ...$data,
        ]);
    }

    private function createSite(array $data, Insertion $insertion): void
    {
        Site::create([
            'insertion_id' => $insertion->id,
            ...$data,
        ]);
    }

    private function createEvent(array $data, Insertion $insertion): void
    {
        Event::create([
            'insertion_id' => $insertion->id,
            ...$data,
        ]);
    }
}
