<!DOCTYPE html>
<html lang="en" ng-app="app">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <base href="/a" />
    <title>Stockpeer.com</title>
    
    <link href="/app/bower/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/app/bower/bootstrap-datepicker/dist/css/bootstrap-datepicker3.standalone.css" rel="stylesheet" />    
    <link href="/assets/css/socialicons.css" rel="stylesheet" />
    <link href="/app/css/style.css" rel="stylesheet" />
    
    <link rel="manifest" href="/manifest.json">
    
    <script>
      var site = {
      	env: '<?=App::environment()?>',
      	ws_url: '<?=env('APP_WS_URL')?>',
        app_url: '<?=env('APP_URL')?>',
        user_id: '<?=Auth::user()->UsersId?>',
      	user_hash: '<?=Crypt::encrypt(Auth::user()->UsersId)?>'
      }
    </script>    
    
    <script src="/app/bower/jquery/dist/jquery.min.js"></script> 
    <script src="/app/bower/angular/angular.min.js"></script> 
    <script src="/app/bower/angular-route/angular-route.min.js"></script>        
    <script src="/app/bower/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/app/bower/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/app/bower/moment/min/moment.min.js"></script>
    <script src="/app/vendor/Highstock-2.1.8/js/highstock.js"></script>     
    
    <script src="<?=elixir('app/css/app.js')?>"></script>
    
<?php
/*
    <script src="/app/config/app.js"></script>
    <script src="/app/config/routes.js"></script>    

    <script src="/app/filters/date.js"></script>	

    <script src="/app/controllers/dashboard.js"></script>	
    <script src="/app/controllers/screener.js"></script>
    <script src="/app/controllers/accounting.js"></script>  
    <script src="/app/controllers/reports.js"></script>  
    <script src="/app/controllers/backtest.js"></script> 
    <script src="/app/controllers/trades.js"></script>
    <script src="/app/controllers/trade-groups.js"></script> 
    <script src="/app/controllers/settings.js"></script> 
*/
?>                     	      
  </head>
  
  <body>
  
    <div class="container" ng-controller="SiteWideCtrl">
      <header class="clearfix row">
          
        <ul class="nav nav-pills pull-left">
          <li role="presentation" ng-class="{ active: (tab == 'dashboard') }"><a href="/a/dashboard">Dashboard</a></li>
          
          <li role="presentation" ng-class="{ active: (tab == 'screener') }"><a href="/a/screener/credit-spreads">Screener</a></li>
          
          <li role="presentation" ng-class="{ active: (tab == 'backtest') }"><a href="/a/backtest/option-spreads">Backtest</a></li>             

          <li role="presentation" ng-class="{ active: (tab == 'trades') }"><a href="/a/trade-groups">Trades</a></li>         
  
          <li role="presentation" class="dropdown" ng-class="{ active: (tab == 'reports') }">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
              Reports <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="/a/reports/orders">Orders</a></li>
              <li><a href="/a/reports/activity">Activity</a></li>               
              <li><a href="/a/reports/performance">Performance</a></li>               
              <li><a href="/a/reports/tradier-history">Tradier History</a></li>   
              <li><a href="/a/reports/income-statement?start=<?=date('1/1/Y')?>&end=<?=date('12/31/Y')?>">Income Statement</a></li>                                   
            </ul>
          </li>
          
          <li role="presentation" class="dropdown" ng-class="{ active: (tab == 'accounting') }">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
              Accounting <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="/a/accounting/assets">Assets</a></li>
              <li><a href="/a/accounting/shares">Shares</a></li>
              <li><a href="/a/accounting/income">Income</a></li>                                      
              <li><a href="/a/accounting/expenses">Expenses</a></li>
            </ul>
          </li>
          
          <li role="presentation" ng-class="{ active: (tab == 'settings') }"><a href="/a/settings">Settings</a></li>           
        </ul>  
        
         <a href="/a/dashboard" class="logo pull-right hidden-xs hidden-sm">
  			  <img id="logo" src="/assets/img/wwlogo@2x.png" alt="Stockpeer" />
        </a>             
      </header>
      
      <hr>
      
      <div class="row main-content">
      	
      	<div class="alert alert-info" role="alert" ng-show="custom_header_notice" ng-bind="custom_header_notice"></div>
      	
      	<div class="alert alert-warning" role="alert" ng-show="ws_reconnecting">Reconnecting to the server...</div>
      	
      	<div class="alert alert-warning" role="alert" ng-hide="messaging_activated">Push Messaging & Notifications???? <a href="" ng-click="messaging_subscribe()">Click Here To Activate</a></div>

      	<div class="alert alert-warning" role="alert" ng-hide="apple_messaging_activated">Push Messaging & Notifications???? <a href="" ng-click="apple_push_notification()">Click Here To Activate</a></div>

      	<?php /* <div class="alert alert-warning" role="alert" ng-show="messaging_activated">Push Messaging & Notifications???? <a href="" ng-click="messaging_unsubscribe()">Click Here To Turn Off</a></div> */ ?>
      	      	
        <?=view('template.part-orders-credit-spreads')?>
      	
      	<div ng-view></div>
      </div>
      
      
      <footer class="row">
        <div class="row well">	
          <div class="copyright span8 pull-left">
          	Sponsored by <a href="http://cloudmanic.com/?utm_campaign=stockpeer.com" class="red-link">Cloudmanic Labs</a>
          </div>
          		
          <div class="pull-right">
          	<a href="<?=URL::to('blog/rss')?>"><i class="smicon-rss"></i></a>
          	<a href="http://twitter.com/stockpeer"><i class="smicon-twitter"></i></a>
          	<a href="https://www.facebook.com/stockpeer"><i class="smicon-facebook"></i></a>
          	<a href="https://google.com/+Stockpeer"><i class="smicon-google"></i></a>
          </div>
        </div>
      </footer> 
    </div>     
    
  </body>
</html>