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