var checkFormValidity, domForm, domFormRoles, domFormWell, domNote, domSubmit, selectedRoles, selectedWell, transformBool;

domForm = document.getElementById('mb-form');
domFormRoles = document.getElementById('mb-form-roles');
domFormWell = document.getElementById('mb-form-well');

domNote = jQuery('#mb-note');
domSubmit = jQuery('#mb-submit');

selectedRoles = [];
selectedWell = '';

checkFormValidity = function() {
  if (selectedWell == null) {
    domSubmit.prop('disabled', true);
    domNote.hide();
    return false;
  }
  domSubmit.prop('disabled', false);
  domNote.show();
  return true;
};

transformBool = function(boolish) {
  var selected;
  if (boolish == null) {
    return true;
  }
  if (boolish === "true") {
    selected = true;
  }
  return !selected;
};

domSubmit.on('click', function() {
  if (!checkFormValidity()) {
    return false;
  }
  domFormRoles.value = selectedRoles.join();
  domFormWell.value = selectedWell;
  domForm.submit();
});

jQuery('.mb-role').each(function(_) {
  return jQuery(this).on('click', function() {
    var ele, i, id, selected;
    ele = jQuery(this);
    selected = transformBool(ele.attr('data-selected'));
    if (selected) {
      ele.addClass('selected');
    } else {
      ele.removeClass('selected');
    }
    ele.attr('data-selected', selected);
    id = ele.attr('id');
    if (selected) {
      return selectedRoles.push(id);
    } else {
      i = selectedRoles.indexOf(id);
      if (i !== -1) {
        return selectedRoles.splice(i, 1);
      }
    }
  });
});

jQuery('.mb-well').each(function(_) {
  jQuery(this).on('click', function() {
    var ele;
    ele = jQuery(this);
    
    // 'disable' all well elements
    jQuery('.mb-well').each(function(_) {
      var ele2;
      ele2 = jQuery(this);
      ele2.attr('data-selected', false);
      ele2.removeClass('selected');
    });
    
    // 'enable' this element
    selectedWell = ele.attr('id');
    ele.attr('data-selected', true);
    ele.addClass('selected');
    checkFormValidity();
  });
});
