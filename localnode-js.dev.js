


function select_localnode(text, nodeid){
  jQuery(document).ready(function($) {
    //alert(text+' :: '+$('input#edd_localnode'))
    $('input#edd_localnode').val(text);
    $('input#edd_localnode_id').val(nodeid);
  });
}


jQuery(document).ready(function($) {
  $( "#localnode-menu" ).find('li').removeClass('pagenav page_item page_item_has_children');
  $( "#localnode-menu" ).find('ul').removeClass('children');
  $( "#localnode-menu a" ).each(function(){
    var clasarr = $(this).closest('li').attr('class').split(' ');
    for(var clas in clasarr){
      if(clasarr[clas].indexOf('page-item') > -1){
        var nodeid = clasarr[clas].split('-')[2];
      }
    }
    if(!nodeid) nodeid = '0';//$(this).closest('li').attr('class').split(' ')[0].split('-')[2];
    $(this).replaceWith('<a href="javascript:select_localnode(\''+$(this).text()+'\','+nodeid+');">'+$(this).text()+'</a>');
  });

  //// admin menu
  $( '#edd-customer-details #localnode-menu').css('padding-left','20px').css('float','right').find('ul').css('padding-left','20px');
  ////

  if($.menu){
    alert('jQuery MENU!')
    $( "#localnode-menu" ).menu();
  } else {
    //alert($.menu);
  }
  //select_localnode($('input#edd-fairsaving'));

});
