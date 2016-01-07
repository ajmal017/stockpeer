$(function() {

   $("#colorswitcher #nytimes").click(function(){
      $("link#colorcss").attr("href", "css/colors/color-nytimes.css");
      return false;
   });

   $("#colorswitcher #classic").click(function(){
      $("link#colorcss").attr("href", "css/colors/color-classic.css");
      return false;
   });

   $("#colorswitcher #red").click(function(){
      $("link#colorcss").attr("href", "css/colors/color-red.css");
      return false;
   });


   $("#colorswitcher #green").click(function(){
      $("link#colorcss").attr("href", "css/colors/color-green.css");
      return false;
   });


   $("#colorswitcher #blue").click(function(){
      $("link#colorcss").attr("href", "css/colors/color-blue.css");
      return false;
   });


   $("#colorswitcher #yellow").click(function(){
      $("link#colorcss").attr("href", "css/colors/color-yellow.css");
      return false;
   });



});