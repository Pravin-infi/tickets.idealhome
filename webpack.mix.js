const mix = require('laravel-mix');

require('laravel-mix-polyfill');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/vendor/eventmie-pro/events_show/index.js', 'public/js/events_show.js')
    .js('resources/js/vendor/eventmie-pro/bookings_organiser/index.js', 'public/js/bookings_organiser.js')
    .js('resources/js/vendor/eventmie-pro/bookings_customer/index.js', 'public/js/bookings_customer.js')
    .js('resources/js/vendor/eventmie-pro/events_manage/index.js', 'public/js/events_manage.js')
    .js('resources/js/vendor/eventmie-pro/myevents/index.js', 'public/js/myevents.js')
    .js('resources/js/organiser/index.js', 'public/js/organiser.js')
    .js('resources/js/vendor/eventmie-pro/events_listing/index.js', 'public/js/events_listing.js')
    .js('resources/js/vendor/eventmie-pro/welcome/index.js', 'public/js/welcome.js')
    .js('resources/js/myguests/index.js', 'public/js/myguests.js')
    .js('resources/js/sub_organizers/index.js', 'public/js/sub_organizers.js')
    .js('resources/js/vendor/eventmie-pro/ticket_scan/index.js', 'public/js/ticket_scan.js')
    .js('resources/js/manage_reviews/index.js', 'public/js/manage_reviews.js')
    .js('resources/js/vendor/eventmie-pro/profile/index.js', 'public/js/profile.js')
    .js('resources/js/event_dashboard/index.js', 'public/js/event_dashboard.js')
    .js('resources/js/api/seats/index.js', 'public/js/seats.js')
    .js('resources/js/vendor/eventmie-pro/event_earning/index.js', 'public/js/event_earning.js')
    .js('resources/js/search.js', 'public/js/search.js')
    
  
    // use vue 2
    .vue({ version: 2 })
    .webpackConfig({
        optimization: {
            providedExports: false,
            sideEffects: false,
            usedExports: false
        },
        //CUSTOM
        resolve: {
            fallback: {
                "crypto": false,
                "crypto-browserify": require.resolve('crypto-browserify'), //if you want to use this module also don't forget npm i crypto-browserify 
            } 
        }
        //CUSTOM
    })
    .override((config) => {
        delete config.watchOptions;
    })
    .version();
