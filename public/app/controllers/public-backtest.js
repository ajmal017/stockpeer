//
// Backtest controller.
//
app.controller('BacktestCtrl', function ($scope) 
{
  $scope.trades = [];
  $scope.started = false;
  $scope.progress = 0;
  $scope.backtest_id = 0;
  
  $scope.fields = {
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
    BackTestsTradeSelect: 'lowest-credit',
    BackTestsSpreadWidth: '2'    
  }
  
  // Run the backtest.
  $scope.run_backtest = function ()
  {
    alert('asdf');

/*
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
*/
  
  }  

});