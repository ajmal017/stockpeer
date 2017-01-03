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
  $scope.pl_2017 = '';
  
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
    
    $http.get('/api/v1/trades/pl_by_year/2017').success(function (json) {
      $scope.pl_2017 = json.data.p_l_df;
    });              
  }
  
  $scope.refresh_trades(); 
});