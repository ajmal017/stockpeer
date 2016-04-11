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
  ], 'public/js/app.js', 'public/');

  mix.scripts([ 
    'app/bower/jquery/dist/jquery.min.js',
    'app/bower/angular/angular.min.js',
    'app/bower/angular-route/angular-route.min.js',
    'app/bower/bootstrap/dist/js/bootstrap.min.js',
    'app/bower/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
    'app/bower/moment/min/moment.min.js',
    'app/vendor/Highstock-2.1.8/js/highstock.js'
  ], 'public/js/libs.js', 'public/');
    
  mix.styles([
    'app/bower/bootstrap/dist/css/bootstrap.min.css',
    'app/bower/bootstrap-datepicker/dist/css/bootstrap-datepicker3.standalone.css',
    'assets/css/socialicons.css',
    'app/css/app.css'
  ], 'public/css/app.css', 'public/');
    
  mix.version([ 'css/app.css', 'js/app.js', 'js/libs.js' ]);
  
  mix.copy('public/assets/img', 'public/build/img');  
    
});
