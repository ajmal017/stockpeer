//
// Date to ISO
//
app.filter('dateToISO', function() {
  return function(input) {
    return new Date(input).toISOString();
  };
});