function expandCategory(img, cat_id)
{
    
    if(true == wp_ajax_tree_lock) return;
    el = document.getElementById('childs-'+cat_id.toString());
    
    if(el.style.display == 'block')
       {
           el.style.display = 'none';
           img.src = ajax_tree_images_url+'plus.gif';
           $.get('/index.php', {
                wp_ajax_tree_close_cat: cat_id
             }, function(data) {
//                alert(data);
              }
            );
           return;
       }
       if(el.innerHTML != '' && -1==el.innerHTML.search(/<img\ /i))
       {
           //Content already loaded
		   
           el.style.display = 'block';
           img.src = ajax_tree_images_url+'minus.gif';
           return;
       }
       el.style.display = 'block';
       wp_ajax_tree_lock = true;
   $.get('/index.php', {
        wp_ajax_tree_expand_cat: cat_id
     }, function(data) {

        
        
        el.innerHTML = data;
        img.src = ajax_tree_images_url+'minus.gif';
        wp_ajax_tree_lock = false;
      }
    );
}
function expandPage(img, pid)
{
   
   if(true == wp_ajax_tree_lock) return;
   el = document.getElementById('pchilds-'+pid.toString());
   if(el.style.display == 'block')
       {
           el.style.display = 'none';
           img.src = ajax_tree_images_url+'plus.gif';
           $.get('/index.php', {
                wp_ajax_tree_close_page: pid
             }, function(data) {
//                alert(data);
              }
            );
           return;
       }
   if(el.innerHTML != '' && -1==el.innerHTML.search(/<img\ /i))
       {
           //Content already loaded
           el.style.display = 'block';
           img.src = ajax_tree_images_url+'minus.gif';
           return;
       }
       el.style.display = 'block';
       wp_ajax_tree_lock = true;
   $.get('/index.php', {
        wp_ajax_tree_expand_page: pid
     }, function(data) {
        
        el.innerHTML = data;
        
        img.src = ajax_tree_images_url+'minus.gif';
        wp_ajax_tree_lock = false;
      }
    );
}