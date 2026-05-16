<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Gemini (receipt interpretation)
    |--------------------------------------------------------------------------
    |
    | Used when POST /api/v1/expenses includes a receipt image: the file is not
    | stored; it is sent to Gemini once to infer item and price.
    |
    */

    'api_key' => env('GEMINI_AI_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),

];
