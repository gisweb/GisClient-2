GisClient 2.1

Installazione:

Creare una directory virtuale per i file temporanei di Mapserver (cfr documentazione Mapserver)
Si consiglia di creare una directory virtuale "gisclient" che punta a gisclient-2.1/public  
Se si cambia il nome della virtual directory occorre aggiornare BaseUrl in gisclient-2.1/public/jslib/GisClient.js

Aprire la url http://<mioserver>/gisclient/admin/setup

Il form di installazione setta i parametri di configurazione e scrive i file nella cartella config

Se si vuole utilizzare il progetto demo scaricare il dump sql del database e creare il database gisclient_demo
Dal menu dell'Author cliccare su progetti -> nuovo e selezionare importa. Nella cartella import/export selezionare il file "progetto_demo"

Per poter visualizzare le mappe accedere alla pagina da "Mappe Online" e creare il mapset.

