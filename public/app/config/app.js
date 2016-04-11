var ws;
var heartbeat = null;
var missed_heartbeats = 0;
var app = angular.module('app', [ 'ngRoute' ]);

//
// Site wide controller.
//
app.controller('SiteWideCtrl', function ($scope, $http) {
  
  $scope.tab = '';
  $scope.custom_header_notice = '';
  $scope.ws_reconnecting = false;
  $scope.messaging_activated = true;
  $scope.apple_messaging_activated = true;  
  $scope.order = {};
  $scope.logged_in_user = {};
  $scope.preview_credit_spreads = false;
  $scope.preview_credit_spreads_data = {};
  
  $scope.global_stats = {
    snp_30_rank: 0,
    snp_60_rank: 0,
    snp_90_rank: 0, 
    snp_365_rank: 0            
  }

  // --------------- Get Logged In User ----- //

  $scope.refresh_logged_in_user = function () {
    // Make API call to get the data.
    $http.get('/api/v1/me').success(function (json) {
      $scope.logged_in_user = json.data;
    });
  }
  
  $scope.refresh_logged_in_user();

  // --------------- Manage Global Stats ----- //
  
  $scope.manage_global_stats = function () {
    
    // Get 30day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/30').success(function (json) {
      $scope.global_stats.snp_30_rank = json.data.Rank;
    });

    // Get 60day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/60').success(function (json) {
      $scope.global_stats.snp_60_rank = json.data.Rank;
    });

    // Get 90day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/90').success(function (json) {
      $scope.global_stats.snp_90_rank = json.data.Rank;
    });

    // Get 365day snp 500 IV Rank.
    $http.get('/api/v1/quotes/get_snp_500_rank/365').success(function (json) {
      $scope.global_stats.snp_365_rank = json.data.Rank;
    });
    
  }
  
  // Catch Websocket event - Timmer:60seconds - just a timer that fires every 60 seconds 
  $scope.$on('Timmer:60seconds', function (event, args) { 
    $scope.manage_global_stats();    
  });  
  
  $scope.manage_global_stats();

  // --------------- Manage Custom Notice ----- //
  
  // When the socket connects and sends us a custom notice message
  $scope.$on('HeaderNotice:message', function (event, args) {        
    $scope.custom_header_notice = args.data;
  });  

  // --------------- Manage Orders ------------ //
  
  // order-preview:credit-spreads
  $scope.$on('order-preview:credit-spreads', function (event, data) {
    $scope.preview_credit_submit_btn = 'Place Order';
    $scope.order = data.order;
    $scope.preview_credit_spreads_data = data.preview;
    $scope.preview_credit_spreads = true;
    window.scrollTo(0, 0);
  });  
  
  // Cancel button.
  $scope.order_cancel = function ()
  {
    $scope.order = {};
    $scope.preview_credit_spreads_data = {};
    $scope.preview_credit_spreads = false;    
  }
  
  // Submit order.
  $scope.submit_order = function ()
  {
    // Make sure we do not double order.
    if($scope.preview_credit_submit_btn == 'Submitting.....')
    {
      return false;
    }
    
    $scope.preview_credit_submit_btn = 'Submitting.....';
    $scope.order.preview = 'false';

    $http.post('/api/v1/trades/preview_trade', { order: $scope.order }).success(function (json) {
      $scope.order = {};
      $scope.preview_credit_spreads_data = {};
      $scope.preview_credit_spreads = false;
      $scope.preview_credit_submit_btn = 'Place Order';
    });

  }

  // --------------- Ping the server every so often ------  //
  
  function server_ping()
  {
    $http.get('/api/v1/me/ping').success(function (json) {
      //console.log(json);
    }); 
  }
  
  setInterval(function () { server_ping(); }, (20 * 1000));

  
  // --------------- Start Web Sockets -------------------- //
    
  //
  // Startup the websocket
  //
  function createWebSocket () 
  {
    ws = new WebSocket('wss://' + site.ws_url + '/ws/core');
    
    // Websocket sent data to us.
    ws.onmessage = function(e) 
    { 
      var msg = JSON.parse(e.data);
      
      // Some special cases send "job" instead of "type"
      if(msg.job)
      {
        msg.type = msg.job;
        msg.data = msg.data.Payload;
      }
      
      // Is this a pong to our ping or some other return.
      if(msg.type == 'pong')
      {
        missed_heartbeats--;
      } else
      {
        $scope.$broadcast(msg.type, { data: msg.data, timestamp: msg.timestamp }); 
      }
    };
    
    // On Websocket open
    ws.onopen = function(e) 
    {
      $scope.ws_reconnecting = false;
  
      // Setup the connection heartbeat
      if(heartbeat === null) 
      {
        missed_heartbeats = 0;
        
        heartbeat = setInterval(function() {
         
          try {
            missed_heartbeats++;
            
            if(missed_heartbeats >= 5)
            {
              throw new Error('Too many missed heartbeats.');
            }
            
            ws.send(JSON.stringify({ type: 'ping' }));
            
          } catch(e) 
          {
            $scope.ws_reconnecting = true;
            clearInterval(heartbeat);
            heartbeat = null;
            console.warn("Closing connection. Reason: " + e.message);
            ws.close();
          }
          
        }, 5000);
      } else
      {
        clearInterval(heartbeat);
      }
      
      // We need to get WS API key to do anything fun.
      $http.post('/api/v1/me/get_websocket_key', {}).success(function (json) {
      
        // If failed do nothing.
        if(! json.status)
        {
          return false;
        }
      
        // Send websocket key
        ws.send(JSON.stringify({ type: 'ws-key', data: json.data.key }));  
      });      
  
    };
    
/*
    ws.onerror = function(e) {
            
      // clear heartbeat
      clearInterval(heartbeat);
      heartbeat = null;
      
      $scope.ws_reconnecting = true;
      $scope.$apply();
    }
*/
    
    // On Close
    ws.onclose = function () 
    {      
      // Kill Ping heartbeat.
      clearInterval(heartbeat);
      heartbeat = null;
      
      // Try to reconnect
      $scope.ws_reconnecting = true;
      setTimeout(function () { createWebSocket(); }, 3 * 1000);
      $scope.$apply();
    }
      
  }
  
  // Start websockets by getting a websocket key first.
  createWebSocket();
	
  // --------------- End Web Sockets -------------------- //
  
  
  // -------------- Setup Service Worker & Push Messages ----------- //
     
  // UnSubscribe to google messaging.
  $scope.messaging_unsubscribe = function ()
  {     
    navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {

      serviceWorkerRegistration.pushManager.getSubscription().then(function(pushSubscription) {
        
        // Check we have a subscription to unsubscribe
        if(! pushSubscription) 
        {
          return;
        }

        // TODO: Make a request to your server to remove
        // the users data from your data store so you
        // don't attempt to send them push messages anymore

        // We have a subcription, so call unsubscribe on it
        pushSubscription.unsubscribe().then(function(successful) {
          
          // Show activate button.
          $scope.messaging_activated = false;
          
        }).catch(function(e) {
          console.log('Unsubscription error: ', e);
        });
      
      }).catch(function(e) {
        console.log('Error thrown while unsubscribing from ' + 'push messaging.', e);
      });
  
    });
    
  }
  
  // Subscribe to google messaging.
  $scope.messaging_subscribe = function ()
  {
    // Subscribe.....
    navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
      
      serviceWorkerRegistration.pushManager.subscribe({ userVisibleOnly: true }).then(function(subscription) {

        // Build post to send to the server.
        var post = {
          UserToDeviceType: 'GCM Browser',
          UserToDeviceGcmEndPoint: subscription.endpoint
        }

        // Send information to the server to store.
        $http.post('/api/v1/usertodevice/create', post).success(function (json) {
            //console.log(json);
        });
        
        // Hide activate button.
        $scope.messaging_activated = true;
        
      }).catch(function(e) {
        
        if(Notification.permission === 'denied') 
        {
          console.log('Permission for Notifications was denied');
        } else 
        {
          console.log('Unable to subscribe to push.', e);
        }
      
      });
    
    });    
  }     
     
  // Check that service workers are supported, if so, progressively
  // enhance and add push messaging support, otherwise continue without it.
  if('serviceWorker' in navigator) 
  {
    // Register service working and do some stuff after.
    navigator.serviceWorker.register('/service-worker').then(function () {
    
      // Are Notifications supported in the service worker?
      if(! ('showNotification' in ServiceWorkerRegistration.prototype)) 
      {
        console.log('Notifications aren\'t supported.');
        return;
      }

      // Check the current Notification permission.
      // If its denied, it's a permanent block until the
      // user changes the permission
      if(Notification.permission === 'denied') 
      {
        console.log('The user has blocked notifications.');
        return;
      }

      // Check if push messaging is supported
      if(! ('PushManager' in window)) 
      {
        console.log('Push messaging isn\'t supported.');
        return;
      }
  
      // We need the service worker registration to check for a subscription
      navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
    
        // Do we already have a push message subscription?
        serviceWorkerRegistration.pushManager.getSubscription().then(function(subscription) {  

          // Do we have a subscription?
          if(! subscription) 
          {            
            $scope.messaging_activated = false;
            return;
          }
          
          // Build post to send to the server.
          var post = {
            UserToDeviceType: 'GCM Browser',
            UserToDeviceGcmEndPoint: subscription.endpoint
          }

          // Send information to the server to store.
          $http.post('/api/v1/usertodevice/create', post).success(function (json) {
            //console.log(json);
          });

        }).catch(function(err) {
          console.log('Error during getSubscription()', err);
        });
    
      });
    
    });
  } else 
  {
    console.log('Service workers aren\'t supported in this browser.');
  } 
  
  
  // -------------- End Service Worker & Push Messages ----------- //
  
  // -------------- Setup Apple Push Notifications ----------- //  
  
  var pushId = "web.cloudmanic.stockpeer";
  
  // See if there is messaging.
  if('safari' in window && 'pushNotification' in window.safari) 
  {
    var perms_data = window.safari.pushNotification.permission(pushId);
    
    // See if we should show the notification
    if(perms_data.permission == 'default')
    {
      $scope.apple_messaging_activated = false;
    }
  }
  
  // Push notification On activiation.
  $scope.apple_push_notification = function ()
  {
    if('safari' in window && 'pushNotification' in window.safari) 
    {
      var permissionData = window.safari.pushNotification.permission(pushId);
      $scope.checkRemotePermission(permissionData);
    } else 
    {
      alert("Push notifications not supported.");
    }
  }
  
  // Check remote permissions
  $scope.checkRemotePermission = function (permissionData) {
    
    if(permissionData.permission === 'default') 
    {
      // Get permissions 
      window.safari.pushNotification.requestPermission(
        site.app_url,
        pushId,
        { UsersId: site.user_id },
        $scope.checkRemotePermission
      );
    } else if(permissionData.permission === 'denied') 
    {
      $scope.apple_messaging_activated = true;
      console.dir(arguments);
    } else if(permissionData.permission === 'granted') 
    {
      $scope.apple_messaging_activated = true;
      //console.log("The user said yes, with token: "+ permissionData.deviceToken);
    }
  }

  // -------------- End Apple Push Notifications ----------- //   
});