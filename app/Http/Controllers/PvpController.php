<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // <--- AGGIUNGI QUESTA

class PvpController extends Controller
{
    public function receiveAsta(Request $request) {
        // 1. Recupera l'XML grezzo inviato dal Ministero
        $xmlContent = $request->getContent(); 

        if (empty($xmlContent)) {
            return response('No content', 400);
        }

        // Qui dovresti idealmente parsare l'XML per estrarre l'ID dell'asta
        // per dare un nome sensato al file o salvare nel DB.
        $this->saveXmlToFile($xmlContent);

        // 2. Rispondi al Ministero con l'XML di conferma (come richiesto dal WSDL)
        return response()->view('service.response_success', [
            'codice' => '1',
            'descrizione' => 'Ricevuto'
        ])->header('Content-Type', 'text/xml');
    }
    private function saveXmlToFile($xmlContent) {
        // Estrai l'ID dell'inserzione dall'XML (usando SimpleXML o Regex velocemente)
        preg_match('/idInserzioneEspVendita="([^"]+)"/', $xmlContent, $matches);
        $idInserzione = $matches[1] ?? 'unknown_' . time();

        $path = "aste_ricevute/asta_{$idInserzione}.xml";
        
        // Salva nel disco locale (storage/app/aste_ricevute)
        Storage::disk('local')->put($path, $xmlContent);
        
        // CONSIGLIO: Salva nel DB un record che collega l'ID asta al percorso del file
        // Asta::create(['pvp_id' => $idInserzione, 'xml_path' => $path]);
    }
}
