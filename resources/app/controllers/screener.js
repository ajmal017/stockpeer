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