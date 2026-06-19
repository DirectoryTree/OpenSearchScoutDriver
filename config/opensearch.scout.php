<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Refresh Documents
    |--------------------------------------------------------------------------
    |
    | This option determines whether indexed and deleted documents should be
    | made immediately available to search requests. This is useful while
    | testing, but production applications usually leave it disabled.
    |
    */

    'refresh_documents' => env('OPENSEARCH_SCOUT_REFRESH_DOCUMENTS', false),
];
