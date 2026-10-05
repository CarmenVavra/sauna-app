<?php

return [

    /*
    | Schnellzugang „Als Hauswart“ / „Als Maria Huber“ auf der Login-Seite.
    | Nur für lokale Demos – in Produktion immer false.
    */
    'demo_logins' => (bool) env('SAUNA_DEMO_LOGINS', false),

    /*
    | Grundwerte, die beim ersten Start in die Tabelle „settings“ geschrieben
    | werden. Danach pflegt der Hauswart sie unter „Münzen“.
    */
    'defaults' => [
        'coin_price' => 5.00,
        'minutes_per_coin' => 60,
        'coins_total' => 400,
        'mon_thu_from' => '08:00',
        'mon_thu_until' => '18:00',
        'fri_from' => '08:00',
        'fri_until' => '12:00',
    ],

    /*
    | Standardöle, mit denen jede neue Partei ihre Liste „Meine Öle“ beginnt.
    */
    'default_oils' => [
        'Eukalyptus', 'Pfefferminze', 'Zirbe', 'Latschenkiefer', 'Lavendel',
        'Orange', 'Zitrone', 'Fichtennadel', 'Birke',
    ],

];
