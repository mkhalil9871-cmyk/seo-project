<?php

namespace App\Providers;

use App\Services\Fetcher\PageFetcherInterface;
use App\Services\Fetcher\StaticHtmlFetcher;
use Illuminate\Support\ServiceProvider;

class CrawlerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Today: static HTML only. To add JS-rendering later, write e.g.
        // App\Services\Fetcher\HeadlessBrowserFetcher implementing the same
        // interface (calling out to a small VPS-hosted rendering microservice),
        // and change ONLY this one binding. CrawlerService, HtmlParser, and
        // everything downstream is unaffected.
        $this->app->bind(PageFetcherInterface::class, StaticHtmlFetcher::class);
    }
}
