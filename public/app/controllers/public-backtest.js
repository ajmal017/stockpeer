//
// Backtest controller.
//
app.controller('BacktestCtrl', function ($scope, $http) 
{
  $scope.trades = [];
  $scope.started = false;
  $scope.summary = false;
  $scope.progress = 0;
  $scope.backtest_id = 0;
  $scope.backtest = {}
  
  $scope.fields = {
    BackTestsType: 'Put Credit Spreads',
    BackTestsStart: '1/1/2015',
    BackTestsEnd: '12/31/2015',
    BackTestsStartBalance: '30000.00',
    BackTestsTradeSize: 'percent-15',
    BackTestsCloseAt: 'credit-0.03',
    BackTestsMinDaysExpire: '1',
    BackTestsMaxDaysExpire: '45',
    BackTestsStopAt: 'touch-short-leg',
    BackTestsOpenAt: 'precent-away',
    BackTestOpenPercentAway: '4.00',
    BackTestsMinOpenCredit: '0.18',
    BackTestsOneTradeAtTime: 'No',  
    BackTestsTradeSelect: 'lowest-credit',
    BackTestsSpreadWidth: '2'    
  }
  
  // Back to summary
  $scope.back_to_summary = function ()
  {
    $scope.summary = true;
  }
  
  // Run another backtest.
  $scope.another_backtest = function ()
  {
    $scope.summary = false;
  }
  
  // Check to see if we have any new trades. 
  $scope.check_new_trades = function ()
  {
    $http.get('/backtests/status/' + $scope.backtest_id).success(function (json) {
      $scope.trade_index = json.index;
      
      // Are we done backtesting?
      if((json.status == 'Pending') || (json.status == 'Started'))
      {
        $scope.check_new_trades();
        
        // Update progress
        if(json.progress > 0)
        {
          $scope.progress = json.progress;
        }
        
        // Show trades in the table.
        for(var i = 0; i < json.trades.length; i++)
        {
          $scope.trades.push(json.trades[i]);
        }
      } else
      {
        $scope.progress = 95;
        
        // Get the full backtested results.
        $http.get('/backtests/get/' + $scope.backtest_id).success(function (json) {
          $scope.started = false;
          $scope.progress = 100;
          $scope.backtest = json;
        });
        
        // Get all the backtested trades.
        $http.get('/backtests/get_trades/' + $scope.backtest_id).success(function (json) {
          $scope.trades = json;
          $scope.summary = true;
        });        
      }      
    });
  }
  
  // Run the backtest.
  $scope.run_backtest = function ()
  {
    $scope.started = true;
    $scope.progress = 0;
    $scope.trades = [];
    
    // Setup the backtest.
    $http.post('/backtests/setup_backtest', $scope.fields).success(function (json) {

      $scope.backtest_id = json.Id;

      // Run the backtest.
      $http.post('/backtests/run', { BackTestsId: $scope.backtest_id }).success(function (json) {
        $scope.check_new_trades();
      });
      
    });
  
  }  

});