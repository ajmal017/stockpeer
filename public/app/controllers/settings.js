//
// Settings
//
app.controller('SettingsCtrl', function ($scope, $http, $routeParams, $location) 
{  
  $scope.rate_limit_updated = '';
  
  $scope.rate_limits = {
    allowed: 0,
    available: 0,
    expires: 0,
    used: 0   
  };
  
  // When we get an update on the Tradier API limits
  $scope.$on('Quotes:rate_limit', function (event, args) {    
    $scope.rate_limits = args.data;
    $scope.rate_limit_updated = args.timestamp;
    $scope.$apply();
  });
});