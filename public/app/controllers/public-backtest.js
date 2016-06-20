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
  $scope.account_balance_chart = null;
  $scope.backtest_summary_tab = 'performance';
 
  
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
  
  // Backtest performance clicks.
  $scope.backtest_summary_click = function (tab)
  {
    $scope.backtest_summary_tab = tab;
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
          $scope.account_balance_chart.series[0].addPoint([ new Date(json.trades[i].BackTestTradesClose).getTime(), parseFloat(json.trades[i].BackTestTradesBalance) ], false);
        }
        
        // Redraw chart
        $scope.account_balance_chart.redraw();
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
          
          // Show trades in the table.
          for(var i = 0; i < json.length; i++)
          {          
            $scope.account_balance_chart.series[0].addPoint([ new Date(json[i].BackTestTradesClose).getTime(), parseFloat(json[i].BackTestTradesBalance) ], false);
          }
          
          // Redraw chart
          $scope.account_balance_chart.redraw();          
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
    $scope.set_backetst_charts();
    
    // Setup the backtest.
    $http.post('/backtests/setup_backtest', $scope.fields).success(function (json) {

      $scope.backtest_id = json.Id;

      // Run the backtest.
      $http.post('/backtests/run', { BackTestsId: $scope.backtest_id }).success(function (json) {
        $scope.check_new_trades();
      });
      
    }); 
  }  
  
  
  // Setup account balance chart.
  $scope.set_backetst_charts = function ()
  {
    $scope.account_balance_chart = new Highcharts.Chart({
        
      chart: { renderTo: 'account_balance_chart' },
        
      title: { text: 'Account Performance', x: -20 },
          
      xAxis: { type: 'datetime' },
          
      yAxis: { title: { text: '' } },
      
      credits: {  enabled: false },
      
      tooltip: {
        headerFormat: '<b>{series.name}</b><br>',
        pointFormat: '{point.x:%m/%d/%Y} : {point.y:,.1f}'
      },      
          
      series: [{ name: 'Account Balance', data: [] }]
    
    });
  }
  

});