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