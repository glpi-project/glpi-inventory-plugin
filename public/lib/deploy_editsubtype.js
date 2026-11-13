/*
 * Edit a subtype element.
 */
function edit_subtype(subtype, packages_id, _rand,el) {

   //remove all border to previous selected item (remove classes)
   //Ext.select('#table_' + subtype + '_'+ _rand +' tr').removeClass('selected');
   jQuery('#table_' + subtype + '_'+ _rand +' tr').removeClass('selected');

   var params = {
      'subtype'   : subtype,
      'packages_id' : packages_id,
      'rand'      : _rand,
      'mode'      : 'create'
   }

   var row = null;
   if (el) {
      // get parent row of the selected element
      row = jQuery(el).parents('tr:first')
   }

   if (row) {
      //add border to selected index (add class)
      row.addClass('selected');
      params['index'] = row.index();
      // change mode to edit
      params['mode'] = 'edit';
   }

   //scroll to edit form
   document.getElementById('th_title_' + subtype + '_' + _rand).scrollIntoView();

   //show and load form
   element = '#' + subtype + 's_block' + _rand;
   jQuery(element).css('display','block');
   jQuery(element).load(
      '../ajax/deploypackage_form.php',
      params
   );
}

/*
 * Create a new subtype element.
 * This method just override *edit_subtype* with a null element.
 */
function new_subtype(subtype, packages_id, _rand) {
   edit_subtype(subtype, packages_id, _rand, null);
}

$(document).on('click', '[data-glpiinventory-edit-subtype]', function () {
   edit_subtype($(this).attr('data-glpiinventory-edit-subtype'),
                $(this).attr('data-glpiinventory-packages-id'),
                $(this).attr('data-glpiinventory-rand'),
                this);
});

$(document).on('click', '[data-glpiinventory-new-subtype]', function () {
   new_subtype($(this).attr('data-glpiinventory-new-subtype'),
               $(this).attr('data-glpiinventory-packages-id'),
               $(this).attr('data-glpiinventory-rand'));
});
