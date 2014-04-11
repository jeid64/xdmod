function displayAnswer(thechosenone) {

   var newboxes = document.getElementsByTagName("div");
   
   for(var x = 0; x < newboxes.length; x++) {

      if (newboxes[x].getAttribute("class") == 'answer') {
      
         var img = document.getElementById('expander-' + newboxes[x].id);
         
         if (newboxes[x].id == thechosenone) {
            newboxes[x].style.display = (newboxes[x].style.display == 'block') ? 'none': 'block';
            img.src = (newboxes[x].style.display == 'block') ? 'images/minus_icon.png' : 'images/plus_icon.png';
         }
         else {
            newboxes[x].style.display = 'none';
            img.src = 'images/plus_icon.png';
         }
      
      }

   }//for

}//displayAnswer