'use strict';

var UsersId = '{UsersId}';

//
// On Message from the app.
//
self.addEventListener('message', function(event) {
  console.log('Handling message event:', event);
});

//
// On Push Notification
//
self.addEventListener('push', function(event) {
  
  event.waitUntil(
 
    // We have to ping the server to find out what we missed.
    // Yes. This is sort of hacky..... 
    // http://stackoverflow.com/questions/30335749/sending-data-payload-to-the-google-chrome-push-notification-with-javascript
    fetch('/api/v1/activity/gcm_fetch?UsersId=' + UsersId).then(function(response) {  
      if(response.status !== 200) 
      {  
        console.log('Looks like there was a problem. Status Code: ' + response.status);  
        throw new Error();  
      }  
  
      // Examine the text in the response  
      return response.json().then(function(json) {
          
        if(json.error || (! json.notification)) 
        {  
          console.error('The API returned an error.', json.error);  
          throw new Error();  
        }  
          
        var title = json.notification.title;  
        var message = json.notification.message;  
        var icon = json.notification.icon;  
        var tag = json.notification.tag;
        var url = json.notification.url;

        return self.registration.showNotification(title, {  
          body: message,  
          icon: icon,  
          tag: tag  
        });
          
      });   
  
    }).catch(function(err) {  

      console.error('Unable to retrieve data', err);
  
      var title = 'An error occurred';
      var message = 'We were unable to get the information for this push message';  
      var icon = 'http://placehold.it/192x192';  
      var tag = 'notification-error';  
        
      return self.registration.showNotification(title, { body: message, icon: icon, tag: notificationTag });  
      
    })
  
  );
  
});


//
// When a user clicks on a notification
//
self.addEventListener('notificationclick', function(event) {
  
  console.log('On notification click: ', event.notification.tag);
  
  // Android doesn’t close the notification when you click on it
  // See: http://crbug.com/463146
  event.notification.close();

  // This looks to see if the current is already open and
  // focuses if it is
  event.waitUntil(clients.matchAll({
    type: "window"
  }).then(function(clientList) {
    for (var i = 0; i < clientList.length; i++) 
    {
      var client = clientList[i];
      if (client.url == '/a/dashboard' && 'focus' in client)
        return client.focus();
    }
    
    if(clients.openWindow)
    {
      return clients.openWindow('/a/dashboard');
    }
  }));

});