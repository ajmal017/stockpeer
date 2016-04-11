var ws;
var heartbeat = null;
var missed_heartbeats = 0;
var app = angular.module('app', [ 'ngRoute' ]);

//
// Site wide controller.
//
app.controller('SiteWideCtrl', function ($scope, $http) {
  
  $scope.tab = '';
  $scope.custom_header_notice = '';
  $scope.ws_reconnecting = false;
  $scope.messaging_activated = true;
  $scope.apple_messaging_activated = true;  
  $scope.order = {};
  $scope.logged_in_user = {};
  $scope.preview_credit_spreads = false;
  $scope.preview_credit_spreads_data = {};
  
  $scope.global_stats = {
    snp_30_rank: 0,
    snp_60_rank: 0,
    snp_90_rank: 0, 
    snp_365_rank: 0            
  }

  // --------------- Get Logged In User ----- //

  $scope.refresh_logged_in_user = function () {
    // Make API call to get the data.
    $http.get('/api/v1/me').success(function (json) {
      $scope.logged_in_user = json.data;
    });
  }
  
  $scope.refresh_logged_in_user();

  // --------------- Manage Global Stats ----- //
  
  $scope.manage_global_stats = function () {
    
    // Get 30day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/30').success(function (json) {
      $scope.global_stats.snp_30_rank = json.data.Rank;
    });

    // Get 60day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/60').success(function (json) {
      $scope.global_stats.snp_60_rank = json.data.Rank;
    });

    // Get 90day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/90').success(function (json) {
      $scope.global_stats.snp_90_rank = json.data.Rank;
    });

    // Get 365day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/365').success(function (json) {
      $scope.global_stats.snp_365_rank = json.data.Rank;
    });
    
  }
  
  // Catch Websocket event - Timmer:60seconds - just a timer that fires every 60 seconds 
  $scope.$on('Timmer:60seconds', function (event, args) { 
    $scope.manage_global_stats();    
  });  
  
  $scope.manage_global_stats();

  // --------------- Manage Custom Notice ----- //
  
  // When the socket connects and sends us a custom notice message
  $scope.$on('HeaderNotice:message', function (event, args) {        
    $scope.custom_header_notice = args.data;
  });  

  // --------------- Manage Orders ------------ //
  
  // order-preview:credit-spreads
  $scope.$on('order-preview:credit-spreads', function (event, data) {
    $scope.preview_credit_submit_btn = 'Place Order';
    $scope.order = data.order;
    $scope.preview_credit_spreads_data = data.preview;
    $scope.preview_credit_spreads = true;
    window.scrollTo(0, 0);
  });  
  
  // Cancel button.
  $scope.order_cancel = function ()
  {
    $scope.order = {};
    $scope.preview_credit_spreads_data = {};
    $scope.preview_credit_spreads = false;    
  }
  
  // Submit order.
  $scope.submit_order = function ()
  {
    // Make sure we do not double order.
    if($scope.preview_credit_submit_btn == 'Submitting.....')
    {
      return false;
    }
    
    $scope.preview_credit_submit_btn = 'Submitting.....';
    $scope.order.preview = 'false';

    $http.post('/api/v1/trades/preview_trade', { order: $scope.order }).success(function (json) {
      $scope.order = {};
      $scope.preview_credit_spreads_data = {};
      $scope.preview_credit_spreads = false;
      $scope.preview_credit_submit_btn = 'Place Order';
    });

  }

  // --------------- Ping the server every so often ------  //
  
  function server_ping()
  {
    $http.get('/api/v1/me/ping').success(function (json) {
      //console.log(json);
    }); 
  }
  
  setInterval(function () { server_ping(); }, (20 * 1000));

  
  // --------------- Start Web Sockets -------------------- //
    
  //
  // Startup the websocket
  //
  function createWebSocket () 
  {
    ws = new WebSocket('wss://' + site.ws_url + '/ws/core');
    
    // Websocket sent data to us.
    ws.onmessage = function(e) 
    { 
      var msg = JSON.parse(e.data);
      
      // Some special cases send "job" instead of "type"
      if(msg.job)
      {
        msg.type = msg.job;
        msg.data = msg.data.Payload;
      }
      
      // Is this a pong to our ping or some other return.
      if(msg.type == 'pong')
      {
        missed_heartbeats--;
      } else
      {
        $scope.$broadcast(msg.type, { data: msg.data, timestamp: msg.timestamp }); 
      }
    };
    
    // On Websocket open
    ws.onopen = function(e) 
    {
      $scope.ws_reconnecting = false;
  
      // Setup the connection heartbeat
      if(heartbeat === null) 
      {
        missed_heartbeats = 0;
        
        heartbeat = setInterval(function() {
         
          try {
            missed_heartbeats++;
            
            if(missed_heartbeats >= 5)
            {
              throw new Error('Too many missed heartbeats.');
            }
            
            ws.send(JSON.stringify({ type: 'ping' }));
            
          } catch(e) 
          {
            $scope.ws_reconnecting = true;
            clearInterval(heartbeat);
            heartbeat = null;
            console.warn("Closing connection. Reason: " + e.message);
            ws.close();
          }
          
        }, 5000);
      } else
      {
        clearInterval(heartbeat);
      }
      
      // We need to get WS API key to do anything fun.
      $http.post('/api/v1/me/get_websocket_key', {}).success(function (json) {
      
        // If failed do nothing.
        if(! json.status)
        {
          return false;
        }
      
        // Send websocket key
        ws.send(JSON.stringify({ type: 'ws-key', data: json.data.key }));  
      });      
  
    };
    
/*
    ws.onerror = function(e) {
            
      // clear heartbeat
      clearInterval(heartbeat);
      heartbeat = null;
      
      $scope.ws_reconnecting = true;
      $scope.$apply();
    }
*/
    
    // On Close
    ws.onclose = function () 
    {      
      // Kill Ping heartbeat.
      clearInterval(heartbeat);
      heartbeat = null;
      
      // Try to reconnect
      $scope.ws_reconnecting = true;
      setTimeout(function () { createWebSocket(); }, 3 * 1000);
      $scope.$apply();
    }
      
  }
  
  // Start websockets by getting a websocket key first.
  createWebSocket();
	
  // --------------- End Web Sockets -------------------- //
  
  
  // -------------- Setup Service Worker & Push Messages ----------- //
     
  // UnSubscribe to google messaging.
  $scope.messaging_unsubscribe = function ()
  {     
    navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {

      serviceWorkerRegistration.pushManager.getSubscription().then(function(pushSubscription) {
        
        // Check we have a subscription to unsubscribe
        if(! pushSubscription) 
        {
          return;
        }

        // TODO: Make a request to your server to remove
        // the users data from your data store so you
        // don't attempt to send them push messages anymore

        // We have a subcription, so call unsubscribe on it
        pushSubscription.unsubscribe().then(function(successful) {
          
          // Show activate button.
          $scope.messaging_activated = false;
          
        }).catch(function(e) {
          console.log('Unsubscription error: ', e);
        });
      
      }).catch(function(e) {
        console.log('Error thrown while unsubscribing from ' + 'push messaging.', e);
      });
  
    });
    
  }
  
  // Subscribe to google messaging.
  $scope.messaging_subscribe = function ()
  {
    // Subscribe.....
    navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
      
      serviceWorkerRegistration.pushManager.subscribe({ userVisibleOnly: true }).then(function(subscription) {

        // Build post to send to the server.
        var post = {
          UserToDeviceType: 'GCM Browser',
          UserToDeviceGcmEndPoint: subscription.endpoint
        }

        // Send information to the server to store.
        $http.post('/api/v1/usertodevice/create', post).success(function (json) {
            //console.log(json);
        });
        
        // Hide activate button.
        $scope.messaging_activated = true;
        
      }).catch(function(e) {
        
        if(Notification.permission === 'denied') 
        {
          console.log('Permission for Notifications was denied');
        } else 
        {
          console.log('Unable to subscribe to push.', e);
        }
      
      });
    
    });    
  }     
     
  // Check that service workers are supported, if so, progressively
  // enhance and add push messaging support, otherwise continue without it.
  if('serviceWorker' in navigator) 
  {
    // Register service working and do some stuff after.
    navigator.serviceWorker.register('/service-worker').then(function () {
    
      // Are Notifications supported in the service worker?
      if(! ('showNotification' in ServiceWorkerRegistration.prototype)) 
      {
        console.log('Notifications aren\'t supported.');
        return;
      }

      // Check the current Notification permission.
      // If its denied, it's a permanent block until the
      // user changes the permission
      if(Notification.permission === 'denied') 
      {
        console.log('The user has blocked notifications.');
        return;
      }

      // Check if push messaging is supported
      if(! ('PushManager' in window)) 
      {
        console.log('Push messaging isn\'t supported.');
        return;
      }
  
      // We need the service worker registration to check for a subscription
      navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
    
        // Do we already have a push message subscription?
        serviceWorkerRegistration.pushManager.getSubscription().then(function(subscription) {  

          // Do we have a subscription?
          if(! subscription) 
          {            
            $scope.messaging_activated = false;
            return;
          }
          
          // Build post to send to the server.
          var post = {
            UserToDeviceType: 'GCM Browser',
            UserToDeviceGcmEndPoint: subscription.endpoint
          }

          // Send information to the server to store.
          $http.post('/api/v1/usertodevice/create', post).success(function (json) {
            //console.log(json);
          });

        }).catch(function(err) {
          console.log('Error during getSubscription()', err);
        });
    
      });
    
    });
  } else 
  {
    console.log('Service workers aren\'t supported in this browser.');
  } 
  
  
  // -------------- End Service Worker & Push Messages ----------- //
  
  // -------------- Setup Apple Push Notifications ----------- //  
  
  var pushId = "web.cloudmanic.stockpeer";
  
  // See if there is messaging.
  if('safari' in window && 'pushNotification' in window.safari) 
  {
    var perms_data = window.safari.pushNotification.permission(pushId);
    
    // See if we should show the notification
    if(perms_data.permission == 'default')
    {
      $scope.apple_messaging_activated = false;
    }
  }
  
  // Push notification On activiation.
  $scope.apple_push_notification = function ()
  {
    if('safari' in window && 'pushNotification' in window.safari) 
    {
      var permissionData = window.safari.pushNotification.permission(pushId);
      $scope.checkRemotePermission(permissionData);
    } else 
    {
      alert("Push notifications not supported.");
    }
  }
  
  // Check remote permissions
  $scope.checkRemotePermission = function (permissionData) {
    
    if(permissionData.permission === 'default') 
    {
      // Get permissions 
      window.safari.pushNotification.requestPermission(
        site.app_url,
        pushId,
        { UsersId: site.user_id },
        $scope.checkRemotePermission
      );
    } else if(permissionData.permission === 'denied') 
    {
      $scope.apple_messaging_activated = true;
      console.dir(arguments);
    } else if(permissionData.permission === 'granted') 
    {
      $scope.apple_messaging_activated = true;
      //console.log("The user said yes, with token: "+ permissionData.deviceToken);
    }
  }

  // -------------- End Apple Push Notifications ----------- //   
});
app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
  
  $routeProvider.

    // /a/dashboard
    when('/a/dashboard', {
      templateUrl: '/app/html/dashboard/index.html',
      controller: 'DashboardCtrl'
    }).
    
    // /a/screener/credit-spreads
    when('/a/screener/credit-spreads', {
      templateUrl: '/app/html/screener/credit-spreads.html',
      controller: 'ScreenerCreditSpreadsCtrl'
    }).
    
    // /a/accounting/assets
    when('/a/accounting/assets', {
      templateUrl: '/app/html/accounting/assets.html',
      controller: 'AccountingAssetsCtrl'
    }). 
    
    // /a/accounting/shares
    when('/a/accounting/shares', {
      templateUrl: '/app/html/accounting/shares.html',
      controller: 'AccountingSharesCtrl'
    }). 
    
    // /a/accounting/shares/add
    when('/a/accounting/shares/add', {
      templateUrl: '/app/html/accounting/shares-add-edit.html',
      controller: 'AccountingSharesAddEditCtrl',
      resolve: { action: function() { return 'add'; } }  
    }). 
    
    // /a/accounting/shares/remove
    when('/a/accounting/shares/remove', {
      templateUrl: '/app/html/accounting/shares-add-edit.html',
      controller: 'AccountingSharesAddEditCtrl',
      resolve: { action: function() { return 'remove'; } }        
    }).            

    // /a/accounting/income
    when('/a/accounting/income', {
      templateUrl: '/app/html/accounting/income.html',
      controller: 'AccountingIncomeCtrl'
    }). 

    // /a/accounting/expenses
    when('/a/accounting/expenses', {
      templateUrl: '/app/html/accounting/expenses.html',
      controller: 'AccountingExpensesCtrl'
    }). 
    
    // /a/accounting/expenses/add
    when('/a/accounting/expenses/add', {
      templateUrl: '/app/html/accounting/expenses-add-edit.html',
      controller: 'AccountingExpensesAddEditCtrl',
      resolve: { action: function() { return 'add'; } }  
    }).     

    // /a/reports/income-statement
    when('/a/reports/income-statement', {
      templateUrl: '/app/html/reports/income-statement.html',
      controller: 'ReportsIncomeStatementCtrl'
    }).

    // /a/reports/orders
    when('/a/reports/orders', {
      templateUrl: '/app/html/reports/orders.html',
      controller: 'ReportsOrdersCtrl'
    }). 
    
    // /a/reports/activity
    when('/a/reports/activity', {
      templateUrl: '/app/html/reports/activity.html',
      controller: 'ReportsActivityCtrl'
    }).     
    
    // /a/reports/performance
    when('/a/reports/performance', {
      templateUrl: '/app/html/reports/performance.html',
      controller: 'ReportsPerformanceCtrl'
    }). 
    
     // /a/reports/tradierhistory
    when('/a/reports/tradier-history', {
      templateUrl: '/app/html/reports/tradierhistory.html',
      controller: 'ReportsTradierHistoryCtrl'
    }).    
    
    // /a/backtest/option-spreads
    when('/a/backtest/option-spreads', {
      templateUrl: '/app/html/backtest/option-spreads.html',
      controller: 'BacktestOptionsSpreadsCtrl'
    }).

    // /a/backtest/option-spreads/:id
    when('/a/backtest/option-spreads/:id', {
      templateUrl: '/app/html/backtest/option-spreads-view.html',
      controller: 'BacktestOptionsSpreadsViewCtrl'
    }).
    
    // /a/trades
    when('/a/trades', {
      templateUrl: '/app/html/trades/index.html',
      controller: 'TradesCtrl'
    }).
    
    // /a/trade-groups
    when('/a/trade-groups', {
      templateUrl: '/app/html/trade-groups/index.html',
      controller: 'TradeGroupsCtrl'
    }).                    

    // /a/settings
    when('/a/settings', {
      templateUrl: '/app/html/settings/index.html',
      controller: 'SettingsCtrl'
    }). 

    otherwise({ redirectTo: '/a/dashboard' });
    
  // HTML 5 Mode
  $locationProvider.html5Mode(true);
}]);
//
// Date to ISO
//
app.filter('dateToISO', function() {
  return function(input) {
    return new Date(input).toISOString();
  };
});
//
// Site wide controller.
//
app.controller('DashboardCtrl', function ($scope, $http, $location, $timeout, $filter) 
{  
  $scope.$parent.tab = 'dashboard';
  
  $scope.quotes = {}
  $scope.quote_first_run = false;
  
  $scope.chart_sym = 'spy';
  $scope.chart_range = 'today-1'; 
  
  $scope.orders = [];

  $scope.quotes = {};  
  $scope.watchlist = [];
  $scope.watchlist_timestamp = ''; 
  
  
  $scope.trade_groups_put_credit_spread = [];
  $scope.trade_groups_call_credit_spread = [];  
  $scope.positions_stocks = [];
   

  // When the socket connects (or reconnects);
  $scope.$on('Status:connected', function (event, args) {    
    $scope.chart_refresh();
  });

  // Catch Websocket event - Timmer:60seconds - just a timer that fires every 60 seconds 
  $scope.$on('Timmer:60seconds', function (event, args) { 
    
    // Refresh Timesales chart
    $scope.chart_refresh();
    
  });

  // Catch Websocket event - Quotes:get_quotes
  $scope.$on('Quotes:get_quote', function (event, args) {
    
    // Wait for the first AJAX call before accepting websockets.
    if(! $scope.quote_first_run)
    {
      return false;
    }
        
    $scope.quotes[args.data.symbol] = args.data;
    $scope.watchlist_timestamp = args.timestamp;
    $scope.$apply();
  });

  // Catch a websocket event - Positions:refresh
  // Since positions are complex the websocket just
  // tells us when the the positions change so we can
  // make an api call to the server and get the updated position.
  $scope.$on('Positions:refresh', function (event, args) {  
    $scope.get_positions_by_types();
  });
  
  // Catch Websocket event - Orders:get_open_orders
  $scope.$on('Orders:open', function (event, args) {
    $scope.orders = JSON.parse(args.data).orders.order;
    $scope.$apply();
  });
  
  // Figure out gain / loss of a spread.
  $scope.spread_gain_loss = function (spread, type)
  {    
    if(! $scope.quotes[spread.Positions[1].SymbolsShort])
    {
      return 0;
    }
    
    if(type == 'put')
    {
      
      //console.log((spread.TradeGroupsOpen * -1) - ((($scope.quotes[spread.Positions[1].SymbolsShort].ask - $scope.quotes[spread.Positions[0].SymbolsShort].bid) * 100) * spread.Positions[0].PositionsQty) );
      
      return (spread.TradeGroupsOpen * -1) - ((($scope.quotes[spread.Positions[1].SymbolsShort].ask - $scope.quotes[spread.Positions[0].SymbolsShort].bid) * 100) * spread.Positions[0].PositionsQty)       
    } else
    {
      return (spread.TradeGroupsOpen * -1) - ((($scope.quotes[spread.Positions[0].SymbolsShort].ask - $scope.quotes[spread.Positions[1].SymbolsShort].bid) * 100) * spread.Positions[1].PositionsQty)      
    }
  }
  
  // Figure out spread precent_to_close
  $scope.spread_precent_to_close = function (spread, type)
  {    
    if(! $scope.quotes[spread.Positions[1].SymbolsShort])
    {
      return 0;
    }
    
    return ($scope.spread_gain_loss(spread, type) / (spread.TradeGroupsOpen * -1)) * 100;     
  }  
  
  // Figure out percent away.
  $scope.percent_away = function (row, type)
  {
    // Find the short strike.
    var short_strike = null; 
    
    for(var i in row.Positions)
    {
      if(row.Positions[i].PositionsType != 'Option')
      {
        continue;
      }
      
      if(row.Positions[i].PositionsQty < 0)
      {
        short_strike = row.Positions[i];
      }
    }

    if(! $scope.quotes[short_strike.SymbolsShort])
    {
      return '';
    }
    
    
    if(type == 'put')
    {
      return ((parseFloat($scope.quotes[short_strike.SymbolsUnderlying].last) - parseFloat(short_strike.SymbolsStrike)) / 
                ((parseFloat($scope.quotes[short_strike.SymbolsUnderlying].last) + parseFloat(short_strike.SymbolsStrike)) / 2)) * 100;
    } else
    {
      return ((parseFloat(short_strike.SymbolsStrike) - parseFloat($scope.quotes[short_strike.SymbolsUnderlying].last)) / 
                 parseFloat($scope.quotes[short_strike.SymbolsUnderlying].last)) * 100;      
    }
  }
  
  // Get the total cost baises of the positions
  $scope.get_positions_get_total_value = function ()
  {        
    var total = 0;
    
    for(var i in $scope.positions_stocks)
    {
    
      if(! $scope.quotes[$scope.positions_stocks[i].SymbolsShort])
      {
        return 0;
      }      
      
      total = total + (parseFloat($scope.quotes[$scope.positions_stocks[i].SymbolsShort].last) * parseFloat($scope.positions_stocks[i].PositionsQty));
    }
    
    return total;
  }
  
  // Get the total value of the positions
  $scope.get_positions_get_total_cost_baises = function ()
  {
    var total = 0;
    
    for(var i in $scope.positions_stocks)
    {
      total = total +  parseFloat($scope.positions_stocks[i].PositionsCostBasis);
    }
    
    return total;
  }  
  
  // Return total credit of positions.
  $scope.total_put_spread_credit = function ()
  {
    var total = 0;
    
    for(var i in $scope.trade_groups_put_credit_spread)
    {
      if($scope.trade_groups_put_credit_spread[i].TradeGroupsType != 'Put Credit Spread')
      {
        continue;
      }
      
      total = total + ($scope.trade_groups_put_credit_spread[i].TradeGroupsOpen * -1)
    }
        
    return total;
  } 
  
  // Return total credit of positions.
  $scope.total_call_spread_credit = function ()
  {
    var total = 0;
    
    for(var i in $scope.trade_groups_call_credit_spread)
    {
      if($scope.trade_groups_call_credit_spread[i].TradeGroupsType != 'Call Credit Spread')
      {
        continue;
      }
      
      total = total + ($scope.trade_groups_call_credit_spread[i].TradeGroupsOpen * -1)
    }
        
    return total;
  }    
  
  // Return the days to expire.
  $scope.days_to_expire = function (row)
  {
    var expire_date = new Date(row.Positions[0].SymbolsExpire + ' 00:00:00');     
    return Math.round((expire_date - new Date()) / (1000 * 60 * 60 * 24));
  }
  
  // Close credit option trade
  $scope.close_credit_option_trade = function (row, debit)
  {    
    var order = {
      class: 'multileg',
      symbol: 'SPY',
      duration: 'gtc',
      type: 'debit',
      preview: 'true',
      price: debit,
      
      side: [
        'sell_to_close',
        'buy_to_close'
      ],
      
      option_symbol: [
        row.Positions[0].SymbolsShort,
        row.Positions[1].SymbolsShort
      ],
      
      quantity: [ row.Positions[0].PositionsQty, row.Positions[0].PositionsQty ]
    };
    
    // Send a request for preview for the order.
    $http.post('/api/v1/trades/preview_trade', { order: order }).success(function (json) {
      
      if(! json.status)
      {
        alert(json.errors[0].error);
        return false;
      }
      
      json.data.action = 'close';
      json.data.lots = row.Positions[0].PositionsQty;
      json.data.buy_leg = row.Positions[1].SymbolsFull 
      json.data.sell_leg = row.Positions[0].SymbolsFull;       
      $scope.$emit('order-preview:credit-spreads', { preview: json.data, order: order });
    });
  }
  
  // Clicked on the watch list.
  $scope.watchlist_click = function (sym)
  {
    $scope.chart_sym = sym;
    $scope.chart_refresh();
  }
  
  // Change the range on the chart.
  $scope.chart_refresh = function ()
  {
    // Put chart into loading state.
    var chart = $('#chart').highcharts();
    chart.showLoading('Loading data from server...');        
    
    // Get data.
    $http.get('/api/v1/quotes/timesales?preset=' + $scope.chart_range + '&symbol=' + $scope.chart_sym).success(function (json) {
      
      // Setup the data.
      var data = [];
      
      for(var i = 0; i < json.data.length; i++)
      {
        data.push({
          x: (json.data[i].timestamp * 1000) - (60 * 60 * 8 * 1000), // UTM time (have to update this with day light savings time).
          open: json.data[i].open,
          high: json.data[i].high,
          low: json.data[i].low,
          close: json.data[i].close,
          name: $scope.chart_sym.toUpperCase(),
          //color: '#00FF00'
        });
      }
      
      // Hide Loader
      var chart = $('#chart').highcharts();
      chart.showLoading('Loading data from server...');  
      chart.series[0].setData(data);
      chart.xAxis[0].setExtremes();
      chart.hideLoading();      
    });
  }
  
  // Setup stock chart at the top of the page.
  $scope.setup_chart = function ()
  {        
    // create the chart
    $('#chart').highcharts('StockChart', {
      title: { text: '' },
      credits: { enabled: false },
    
      rangeSelector: { enabled: false },
      
      yAxis: {
        startOnTick: false,
        endOnTick: false,
        minPadding: 0.1,
        maxPadding: 0.1          
      },  
      
      xAxis : {
        //events : { afterSetExtremes : afterSetExtremes },
        minRange: 3600 * 1000 // one hour
      },              
    
      series : [{
        name : 'SPY',
        type: 'candlestick',
        data: [],
        turboThreshold: 0,
        tooltip: { valueDecimals: 2 },
        dataGrouping: { enabled: false }
      }]
    
    });    
    
    // Load data.
    $timeout(function () { $scope.chart_refresh(); }, 1000);    
  }
    
  $scope.setup_chart();
  
  // Get watchlist
  $scope.get_watchlist = function ()
  {
    $http.get('/api/v1/me/get_watchlist').success(function (json) {
      $scope.watchlist = json.data;
    });
  }
  
  $scope.get_watchlist();
  
  // Send a request to API all our positions
  $scope.get_positions_by_types = function ()
  {  
    $http.get('/api/v1/tradegroups?filter=open-only&only-open-positions=true&only-put-credit-spreads=true').success(function (json) {
      $scope.trade_groups_put_credit_spread = json.data;    
    });

    $http.get('/api/v1/tradegroups?filter=open-only&only-open-positions=true&only-call-credit-spreads=true').success(function (json) {
      $scope.trade_groups_call_credit_spread = json.data;    
    });

    
    $http.get('/api/v1/positions?col_SymbolsType=Stock&col_PositionsStatus=Open').success(function (json) {
      $scope.positions_stocks = json.data;    
    });    
  }
  
  $scope.get_positions_by_types();
  
  // Get open orders.
  $http.get('/api/v1/orders/get_open').success(function (json) {
    $scope.orders = json.data;       
  });
  
  // Get quotes. We do this via API call first. Then the websocket takes over.
  $scope.get_quotes = function ()
  {
    // Get the quote data and then loop over it.
    $http.get('/api/v1/quotes/get_account_quotes').success(function (json) {
      
      for(var i = 0; i < json.data.length; i++)
      {
        $scope.quotes[json.data[i].symbol] = json.data[i];      
      }
      
      $scope.quote_first_run = true;

    });
  }
  
  $scope.get_quotes(); 
  
});
//
// Site wide controller.
//
app.controller('ScreenerCreditSpreadsCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'screener';

  $scope.quotes = [];
$scope.quote_first_run = true;  
  
  $scope.watchlist = [];
  $scope.watchlist_timestamp = '';  
  $scope.credit_spread_45 = [];
  $scope.credit_spread_45_timestamp = '';
  $scope.credit_spread_weekly = [];
  $scope.credit_spread_weekly_timestamp = '';

  // Catch Websocket event - Quotes:get_quotes
  $scope.$on('Quotes:get_quote', function (event, args) {  
  
    // Wait for the first AJAX call before accepting websockets.
    if(! $scope.quote_first_run)
    {
      return false;
    }    
      
    $scope.quotes[args.data.symbol] = args.data;
    $scope.watchlist_timestamp = args.timestamp;
    $scope.$apply();
  });
  
  // Catch Websocket event - Autotrade:get_possible_spy_put_credit_spreads_45_days_out
  $scope.$on('Autotrade:get_possible_spy_put_credit_spreads_45_days_out', function (event, args) {
    $scope.credit_spread_45 = args.data;
    $scope.credit_spread_45_timestamp = args.timestamp;
    $scope.$apply();
  });  

  // Catch Websocket event - Autotrade:get_possible_spy_put_credit_spreads_weeklies
  $scope.$on('Autotrade:get_possible_spy_put_credit_spreads_weeklies', function (event, args) {
    $scope.credit_spread_weekly = args.data;
    $scope.credit_spread_weekly_timestamp = args.timestamp;
    $scope.$apply();
  });
	
  // Place order
  $scope.open_midpoint = function (row, lots)
  {    
    var order = {
      class: 'multileg',
      symbol: 'SPY',
      duration: 'day',
      type: 'credit',
      preview: 'true',
      price: row.midpoint,
      
      side: [
        'buy_to_open',
        'sell_to_open'
      ],
      
      option_symbol: [
        row.occ_buy,
        row.occ_sell
      ],
      
      quantity: [ lots, lots ]
    };
    
    // Send a request for preview for the order.
    $http.post('/api/v1/trades/preview_trade', { order: order }).success(function (json) {
      
      if(! json.status)
      {
        alert(json.errors[0].error);
        return false;
      }
      
      json.data.lots = lots;
      json.data.action = 'open';
      json.data.buy_leg = 'SPY ' + row.buy_leg + ' ' + row.expire_df1 + ' Put'; 
      json.data.sell_leg = 'SPY ' + row.sell_leg + ' ' + row.expire_df1 + ' Put';       
      $scope.$emit('order-preview:credit-spreads', { preview: json.data, order: order });
    });
  } 
  
  // Get watchlist
  $scope.get_watchlist = function ()
  {
    $http.get('/api/v1/me/get_watchlist').success(function (json) {
      $scope.watchlist = json.data;
    });
  }
  
  $scope.get_watchlist();  
	
  // Send a request for the newest SPY 45 days away data.
  $http.get('/api/v1/autotrade/spy_percent_away').success(function (json) {
    $scope.credit_spread_45 = json.data;
  });
  
  // Send a request for the newest SPY weekly data.
  $http.get('/api/v1/autotrade/spy_weekly_percent_away').success(function (json) {
    $scope.credit_spread_weekly = json.data;
  }); 
  
  // Get quotes. We do this via API call first. Then the websocket takes over.
  $scope.get_quotes = function ()
  {
    // Get the quote data and then loop over it.
    $http.get('/api/v1/quotes/get_account_quotes').success(function (json) {
      
      for(var i = 0; i < json.data.length; i++)
      {
        $scope.quotes[json.data[i].symbol] = json.data[i];      
      }
      
      $scope.quote_first_run = true;

    });
  }
  
  $scope.get_quotes();    
});
//
// Assets
//
app.controller('AccountingAssetsCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.assets = [];
  
  // Catch Websocket event - Assets:update
  $scope.$on('Assets:update', function (event, args) {
    $scope.refresh_assets();
    $scope.$apply();
  });  
  
  // Return a total of all assets
  $scope.total = function ()
  {
    var total = 0;
    
    for(var i in $scope.assets)
    {
      total = total + parseFloat($scope.assets[i].AssetsValue);
    }
    
    return total;
  }
  
  // Save a mark
  $scope.mark_save = function (row)
  {
    row.asset_mark = false;
    
    // Send update to the API.
    $http.post('/api/v1/assets/update/' + row.AssetsId, { AssetsValue: row.AssetsValue }).success(function (json) {
      // Websocket will do that updating.   
    });
  }
  
  // Get a list of assets
  $scope.refresh_assets = function ()
  {
    $http.get('/api/v1/assets?order=AssetsName&sort=asc').success(function (json) {
      $scope.assets = json.data;
    });
  }
  
  $scope.refresh_assets(); 
});

//
// Shares
//
app.controller('AccountingSharesCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.shares = [];
  
  // Get a list of shares
  $scope.refresh_shares = function ()
  {
    $http.get('/api/v1/shares?order=SharesDate&sort=desc').success(function (json) {
      $scope.shares = json.data;
    });
  }
  
  $scope.refresh_shares(); 
});

//
// Shares - Add / Edit.
//
app.controller('AccountingSharesAddEditCtrl', function ($scope, $http, $location, action) 
{
  $scope.$parent.tab = 'accounting'; 
 
  $scope.action = action;
  
  if($scope.action == 'add')
  {
    $scope.submit_btn = 'Add Shares';
  } else
  {
    $scope.submit_btn = 'Remove Shares';    
  }
  
  $scope.fields = {
    SharesDate: new Date(),
    SharesPrice: '',
    SharesNote: ''
  }
  
  // Submit the request.
  $scope.submit = function ()
  {    
    var error = false;
    
    if($scope.fields.SharesPrice)
    {
      $scope.fields.SharesPrice_error = '';
    } else
    {
      error = true;
      $scope.fields.SharesPrice_error = 'An amount is required.';      
    }
    
    if(error)
    {
      return false;
    }

    // Make negative if shares are to be removed.
    if($scope.action == 'remove')
    {
      $scope.fields.SharesPrice = $scope.fields.SharesPrice * -1;
    }
    
    // Send request to server
    $http.post('/api/v1/shares/create', $scope.fields).success(function (json) {
      $location.path('/a/accounting/shares');
    });
    
  }

});

//
// Income
//
app.controller('AccountingIncomeCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.income = [];
  
  // Get a list of income
  $scope.refresh = function ()
  {
    $http.get('/api/v1/income?order=IncomeDate&sort=desc').success(function (json) {
      $scope.income = json.data;
    });
  }
  
  $scope.refresh(); 
});

//
// Expenses
//
app.controller('AccountingExpensesCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'accounting'; 
  
  $scope.expenses = [];
  
  // Get a list of income
  $scope.refresh = function ()
  {
    $http.get('/api/v1/expenses?order=ExpensesDate&sort=desc').success(function (json) {
      $scope.expenses = json.data;
    });
  }
  
  $scope.refresh(); 
});

//
// Expenses - Add / Edit.
//
app.controller('AccountingExpensesAddEditCtrl', function ($scope, $http, $location, action) 
{
  $scope.$parent.tab = 'accounting'; 
 
  $scope.action = action;
  $scope.vendors = [];
  $scope.categories = [];
  $scope.submit_btn = 'Add Expense';
  
  $scope.fields = {
    ExpensesDate: new Date(),
    ExpensesVendor: '',
    ExpensesCategory: '',
    ExpensesAmount: '',
    ExpensesNote: ''
  }
  
  // Get categories and vendors
  $scope.get_categories_vendors = function () 
  {
    // Get categories
    $http.get('/api/v1/expenses/get_categories').success(function (json) {
      $scope.categories = json.data;
      $scope.fields.ExpensesCategory = $scope.categories[0];
    });
    
    // Get Vendors
    $http.get('/api/v1/expenses/get_vendors').success(function (json) {
      $scope.vendors = json.data;
      $scope.fields.ExpensesVendor = $scope.vendors[0];
    });    
  }
  
  // Submit the request.
  $scope.submit = function ()
  {    
    var error = false;
    
    if($scope.fields.ExpensesAmount)
    {
      $scope.fields.ExpensesAmount_error = '';
    } else
    {
      error = true;
      $scope.fields.ExpensesAmount_error = 'An amount is required.';      
    }
    
    if(error)
    {
      return false;
    }
    
    console.log($scope.fields);
    
    
    // Send request to server
    $http.post('/api/v1/expenses/create', $scope.fields).success(function (json) {
      $location.path('/a/accounting/expenses');
    });
    
  }
  
  // Load data.
  $scope.get_categories_vendors();
});
//
// Income Statement
//
app.controller('ReportsIncomeStatementCtrl', function ($scope, $http, $routeParams, $location) 
{  
  $scope.statement = [];
  $scope.date_start = $routeParams.start;
  $scope.date_end = $routeParams.end;
  
  // Date change.
  $scope.date_change = function ()
  {
    $location.path('/a/reports/income-statement').search({ start: $scope.date_start, end: $scope.date_end });
  }
  
  $http.get('/api/v1/reports/income_statement?start=' + $routeParams.start + '&end=' + $routeParams.end).success(function (json) {
    $scope.statement = json.data;
  });
});

//
// Orders
//
app.controller('ReportsOrdersCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.orders = [];
  
  // Catch Websocket event - Orders:insert
  $scope.$on('Orders:insert', function (event, args) {    
    $scope.refresh_orders();
    $scope.$apply();
  });    

  // Catch Websocket event - Orders:update
  $scope.$on('Orders:update', function (event, args) {
    $scope.refresh_orders();
    $scope.$apply();
  });
    
  // Get a list of orders
  $scope.refresh_orders = function ()
  {
    $http.get('/api/v1/orders?order=OrdersEntered&sort=desc').success(function (json) {
      $scope.orders = json.data;
    });
  }
  
  $scope.refresh_orders(); 
});

//
// Activity
//
app.controller('ReportsActivityCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.activity = [];
  
  // Catch Websocket event - Activity:insert
  $scope.$on('Activity:insert', function (event, args) {    
    $scope.refresh();
    $scope.$apply();
  });    

  // Catch Websocket event - Activity:update
  $scope.$on('Activity:update', function (event, args) {
    $scope.refresh();
    $scope.$apply();
  });
    
  // Get a list of Activity
  $scope.refresh = function ()
  {
    $http.get('/api/v1/activity?order=ActivityId&sort=desc').success(function (json) {
      $scope.activity = json.data;
    });
  }
  
  $scope.refresh(); 
});

//
// Performance
//
app.controller('ReportsPerformanceCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.marks = [];
  
  // Catch Websocket event - Assets:update
  $scope.$on('Assets:update', function (event, args) {
    $scope.refresh_marks();
    $scope.$apply();
  });    
    
  // Get a list of marks
  $scope.refresh_marks = function ()
  {
    $http.get('/api/v1/marks?order=MarksDate&sort=desc').success(function (json) {
      $scope.marks = json.data;
    });
  }
  
  $scope.refresh_marks(); 
});

//
// Tradier History
//
app.controller('ReportsTradierHistoryCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'reports'; 
  
  $scope.activity = [];
    
  // Get a list of Activity
  $scope.refresh = function ()
  {
    $http.get('/api/v1/tradierhistory?order=TradierHistoryDate&sort=desc').success(function (json) {
      $scope.history = json.data;
    });
  }
  
  $scope.refresh(); 
});
//
// Option Spreads
//
app.controller('BacktestOptionsSpreadsCtrl', function ($scope, $http, $location) 
{
  $scope.$parent.tab = 'backtest'; 

  $scope.trades = [];
  $scope.started = false;
  $scope.progress = 0;
  $scope.backtest_id = 0;
  
  $scope.fields = {
    BackTestsName: '',
    BackTestsType: 'Put Credit Spreads',
    BackTestsStart: '1/1/2015',
    BackTestsEnd: '12/31/2015',
    BackTestsStartBalance: '30000.00',
    BackTestsTradeSize: 'percent-10',
    BackTestsCloseAt: 'credit-0.03',
    BackTestsMinDaysExpire: '1',
    BackTestsMaxDaysExpire: '45',
    BackTestsStopAt: 'touch-short-leg',
    BackTestsOpenAt: 'precent-away',
    BackTestOpenPercentAway: '4.00',
    BackTestsMinOpenCredit: '0.18',
    BackTestsOneTradeAtTime: 'No',  
    BackTestsTradeSelect: 'lowest-credit'    
  }
  
  // Catch Websocket event - Backtesting:order
  $scope.$on('Backtesting:order', function (event, args) {
    $scope.started = true;    
    $scope.trades.push(args.data);
    $scope.$apply();
  });  

  // Catch Websocket event - Backtesting:done
  $scope.$on('Backtesting:done', function (event, args) {
    $location.path('/a/backtest/option-spreads/' + $scope.backtest_id);
    $scope.$apply();
  }); 
  
  // Catch Websocket event - Backtesting:progress
  $scope.$on('Backtesting:progress', function (event, args) {
    $scope.progress = args.data.progress;
    $scope.$apply();
  }); 
  
  // Run the backtest.
  $scope.run_backtest = function ()
  {
    $scope.started = true;
    $scope.progress = 0;
    $scope.trades = [];
    
    // Setup the backtest.
    $http.post('/api/v1/backtests/setup_backtest', $scope.fields).success(function (json) {

      $scope.backtest_id = json.data.Id;

      // Run the backtest.
      $http.post('/api/v1/backtests/run', { BackTestsId: $scope.backtest_id }).success(function (json) {
        
        // Do nothing....

      });
      
    });
  }    
});

//
// Option Spreads - View
//
app.controller('BacktestOptionsSpreadsViewCtrl', function ($scope, $http, $routeParams) 
{
  $scope.fields = {}
  
  // Get the backtest
  $http.get('/api/v1/backtests/' + $routeParams.id).success(function (json) {
    $scope.fields = json.data;
    $scope.trades = json.data.Trades;
  });
});
//
// Trades
//
app.controller('TradesCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'trades'; 
  
  $scope.trades = [];
  $scope.pl_2014 = '';
  $scope.pl_2015 = '';  
  $scope.pl_2016 = ''; 
  
  // Catch Websocket event - Trades:insert
  $scope.$on('Trades:insert', function (event, args) {
    $scope.refresh_trades();
    $scope.$apply();
  });
  
  // Catch Websocket event - Trades:update
  $scope.$on('Trades:update', function (event, args) {
    $scope.refresh_trades();
    $scope.$apply();
  });  
  
  // Blog trade.
  $scope.blog_trade = function (row)
  {
    // TradesId
    $http.post('/api/v1/blogtrades/insert_by_tradeid', { TradesId: row.TradesId }).success(function (json) {
      alert('Success!!');
    });
  }
  
  // Close trade at $0.03
  $scope.close_at_3 = function (row)
  {
    // Build object to post to the API.
    var post = {
      TradesNote: 'Closed @ 0.03',
      TradesEndCommission: row.TradesStartCommission,
      TradesDateEnd: new Date().toISOString().slice(0, 19).replace('T', ' '),
      TradesEndPrice: (row.TradesShares * row.TradesSpreadWidth1) - (3 * row.TradesShares),
      TradesStatus: 'Closed'
    }
    
    // Send request to the server.
    $http.post('/api/v1/trades/update/' + row.TradesId, post).success(function (json) {
      $scope.refresh_trades();
      $scope.preview_credit_spreads = true;
    });
  }  
  
  // Close trades that expire.
  $scope.close_exired = function (row)
  {
    // Build object to post to the API.
    var post = {
      TradesNote: 'Expired worthless',
      TradesEndCommission: 0.00,
      TradesDateEnd: new Date().toISOString().slice(0, 19).replace('T', ' '),
      TradesEndPrice: row.TradesShares * row.TradesSpreadWidth1,
      TradesStatus: 'Closed'
    }
    
    // Send request to the server.
    $http.post('/api/v1/trades/update/' + row.TradesId, post).success(function (json) {
      $scope.refresh_trades();
    });
  }
  
  // Get a list of trades
  $scope.refresh_trades = function ()
  {
    $http.get('/api/v1/trades?order=TradesId&sort=desc').success(function (json) {
      $scope.trades = json.data;
    });
    
    $http.get('/api/v1/trades/pl_by_year/2014').success(function (json) {
      $scope.pl_2014 = json.data.p_l_df;
    });
    
    $http.get('/api/v1/trades/pl_by_year/2015').success(function (json) {
      $scope.pl_2015 = json.data.p_l_df;
    }); 
 
    $http.get('/api/v1/trades/pl_by_year/2016').success(function (json) {
      $scope.pl_2016 = json.data.p_l_df;
    });          
  }
  
  $scope.refresh_trades(); 
});
//
// TradeGroupsCtrl
//
app.controller('TradeGroupsCtrl', function ($scope, $http) 
{
  $scope.$parent.tab = 'trades';
  
  $scope.filter = 'show-all';
  $scope.trade_groups = [];
  $scope.pl_2014 = '';
  $scope.pl_2015 = ''; 
  $scope.pl_2016 = '';      
  
  // Get a list of trades
  $scope.refresh = function ()
  {
    $http.get('/api/v1/tradegroups?order=TradeGroupsId&sort=desc&filter=' + $scope.filter).success(function (json) {
      $scope.trade_groups = json.data;
    });
    
    $http.get('/api/v1/trades/pl_by_year/2014').success(function (json) {
      $scope.pl_2014 = json.data.p_l_df;
    });
    
    $http.get('/api/v1/trades/pl_by_year/2015').success(function (json) {
      $scope.pl_2015 = json.data.p_l_df;
    });    
    
    $http.get('/api/v1/trades/pl_by_year/2016').success(function (json) {
      $scope.pl_2016 = json.data.p_l_df;
    });       
  }
  
  $scope.refresh();  
  
});
//
// Settings
//
app.controller('SettingsCtrl', function ($scope, $http, $routeParams, $location) 
{  
  $scope.user = {}
  
  // Save user settings.
  $scope.save_configs = function ()
  {
    $http.post('/api/v1/me/update_settings', $scope.user).success(function (json) {
      $scope.refresh_logged_in_user();
      alert('Successfully Updated.');
    });
  }
  
  // Get user settings.
  $scope.get_me = function ()
  {
    $http.get('/api/v1/me').success(function (json) {
      $scope.user = json.data;
    });
  }
  
  // Load page data.
  $scope.get_me();
});

/* End File */
//# sourceMappingURL=app.js.map
