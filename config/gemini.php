<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Gemini (receipt + category inference)
    |--------------------------------------------------------------------------
    |
    | Used when POST /api/v1/expenses includes a receipt image (parse lines) or
    | when manual entry omits category_id (infer category from item text).
    |
    */

    'api_key' => env('GEMINI_AI_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),

    /*
    |--------------------------------------------------------------------------
    | Receipt image payload (before Gemini)
    |--------------------------------------------------------------------------
    |
    | Images are resized (max width, aspect ratio preserved) and JPEG-compressed
    | in memory before base64 encoding to reduce token usage.
    |
    */

    'image_max_width' => (int) env('GEMINI_IMAGE_MAX_WIDTH', 800),

    'image_jpeg_quality' => (int) env('GEMINI_IMAGE_JPEG_QUALITY', 85),

];
