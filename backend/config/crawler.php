<?php

return [
    'user_agent' => env('CRAWLER_USER_AGENT', 'SEOCrawlerBot/1.0 (+https://yourdomain.com/bot-info)'),

    // Hard cap on bytes read per page — protects shared hosting memory_limit.
    'max_page_bytes' => env('CRAWLER_MAX_PAGE_BYTES', 3_000_000),

    'max_retry_attempts' => env('CRAWLER_MAX_RETRY_ATTEMPTS', 3),

    // How many URLs crawler:process handles per audit, per cron tick.
    // Keep this low on cheap shared hosting (5-10). Higher-tier hosting can go to 20-30.
    'default_batch_size' => env('CRAWLER_BATCH_SIZE', 10),

    // How many of those URLs are fetched over the network AT THE SAME TIME (Guzzle Pool).
    // This is what actually makes a 10-page batch take ~1-2 round trips instead of 10.
    // Safe range on cheap shared hosting: 3-5 (each concurrent request holds a small amount
    // of memory for its response body, capped by max_page_bytes above). On a VPS or
    // better hosting you can push this to 10-15.
    'fetch_concurrency' => env('CRAWLER_FETCH_CONCURRENCY', 5),
];
