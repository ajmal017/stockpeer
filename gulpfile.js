var elixir = require('laravel-elixir');

elixir(function(mix) {
    
  mix.scripts([ 
    'app/config/app.js',
    'app/config/routes.js',
    'app/filters/date.js',
    'app/controllers/dashboard.js',
    'app/controllers/screener.js',
    'app/controllers/accounting.js',
    'app/controllers/reports.js',
    'app/controllers/backtest.js',
    'app/controllers/trades.js',
    'app/controllers/trade-groups.js',
    'app/controllers/settings.js'
  ], 'public/build/app.js', 'resources/');
    
  mix.styles([
    'app/css/app.css'
  ], 'public/build/app.css', 'resources/');
  
  mix.version([ 'public/build/app.css', 'public/build/app.js' ]);
    
});
