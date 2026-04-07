<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PVP Callback URL
    |--------------------------------------------------------------------------
    |
    | URL dell'endpoint del Ministero per notificare l'avvenuta pubblicazione
    | dell'annuncio (accettazioneAvvenutaPubblicazione).
    | In test: https://model-office-pvp.giustizia.it/...
    | In produzione: fornito dal Ministero dopo la registrazione sul portale PVP.
    |
    */
    'callback_url' => env('PVP_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Client Certificate Path
    |--------------------------------------------------------------------------
    |
    | Path assoluto al certificato client TLS (formato PEM) rilasciato da una
    | CA accreditata AgID (Aruba, InfoCert, Namirial, Actalis).
    | Usato per la mutua autenticazione nelle chiamate callback verso il Ministero.
    | Esempio: /etc/ssl/pvp/client.pem
    |
    */
    'client_cert_path' => env('PVP_CLIENT_CERT_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Client Private Key Path
    |--------------------------------------------------------------------------
    |
    | Path assoluto alla chiave privata associata al certificato client (formato PEM).
    | NON deve essere accessibile pubblicamente.
    | Esempio: /etc/ssl/pvp/client-key.pem
    |
    */
    'client_key_path' => env('PVP_CLIENT_KEY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Ministry Certificate Path
    |--------------------------------------------------------------------------
    |
    | Path assoluto al certificato pubblico del Ministero (formato PEM/CER).
    | Usato per validare la firma WS-Security nelle richieste SOAP in arrivo.
    | In test: storage/documenti/model-office-pvp.giustizia.it.cer
    | In produzione: certificato ufficiale del PVP.
    |
    */
    'ministry_cert_path' => env('PVP_MINISTRY_CERT_PATH'),

];
