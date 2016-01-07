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