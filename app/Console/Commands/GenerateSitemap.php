<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Spatie\Crawler\Crawler;

use Carbon\Carbon;

/* Sitemap Modules */
use Classiebit\Eventmie\Models\Post;
use App\Models\Event;
use App\Models\Venue;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically Generate an XML Sitemap';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 1. Automatically generate sitemap for non-javascript URLs
        $sitemap    = SitemapGenerator::create(url('/'))->getSitemap();

        // 2. Posts
        $sitemap    = $this->posts($sitemap);
        
        // 3. Events
        $sitemap    = $this->events($sitemap);
        
        // 4. Venues
        $sitemap    = $this->venues($sitemap);

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }

    protected function posts($sitemap) 
    {
        Post::where('status', 'PUBLISHED')->get()->each(function (Post $post) use ($sitemap) {
            $sitemap->add(
                Url::create(route('eventmie.post_view', $post->slug))
                    ->setPriority(0.4)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            );
        });
        
        return $sitemap;
    }
    
    protected function events($sitemap) 
    {
        Event::where(['status'=>1, 'publish'=>1])->get()->each(function (Event $event) use ($sitemap) {
            $sitemap->add(
                Url::create(route('eventmie.events_show',$event->slug))
                    ->setPriority(0.1)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            );
        });
        
        return $sitemap;
    }
    
    protected function venues($sitemap) 
    {
        Venue::where(['status'=>1])->get()->each(function (Venue $venue) use ($sitemap) {
            $sitemap->add(
                Url::create(route('eventmie.venues.show', $venue->slug))
                    ->setPriority(0.1)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            );
        });
        
        return $sitemap;
    }


}
