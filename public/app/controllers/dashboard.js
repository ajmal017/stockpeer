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
      return (spread.TradeGroupsOpen * -1) - ((($scope.quotes[spread.Positions[1].SymbolsShort].ask - $scope.quotes[spread.Positions[0].SymbolsShort].bid) * 100) * spread.Positions[0].PositionsQty)       
    } else
    {
      return (spread.TradeGroupsOpen * -1) - ((($scope.quotes[spread.Positions[0].SymbolsShort].ask - $scope.quotes[spread.Positions[1].SymbolsShort].bid) * 100) * spread.Positions[1].PositionsQty)      
    }
  }
  
  // Figure out spread precent_to_close
  $scope.spread_precent_to_close = function (spread)
  {    
    if(! $scope.quotes[spread.Positions[1].SymbolsShort])
    {
      return 0;
    }
    
    return ($scope.spread_gain_loss(spread) / (spread.TradeGroupsOpen * -1)) * 100;     
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
        row.Positions[1].SymbolsShort,
        row.Positions[0].SymbolsShort
      ],
      
      quantity: [ row.Positions[1].PositionsQty, row.Positions[1].PositionsQty ]
    };
    
    // Send a request for preview for the order.
    $http.post('/api/v1/trades/preview_trade', { order: order }).success(function (json) {
      
      if(! json.status)
      {
        alert(json.errors[0].error);
        return false;
      }
      
      json.data.action = 'close';
      json.data.lots = row.Positions[1].PositionsQty;
      json.data.buy_leg = row.Positions[0].SymbolsFull 
      json.data.sell_leg = row.Positions[1].SymbolsFull;       
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