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

jQuery('#mb-select-all').on('click', function() {
  selectedRoles = [];
  jQuery('.mb-role').each(function(_) {
    var ele, selected;
    ele = jQuery(this);
    selected = ele.attr('data-selected');
    if (!selected) {
      ele.addClass('selected');
      ele.attr('data-selected', true);
      selectedRoles.push(ele.attr('id'));
    }
  });
  checkFormValidity();
});

domSubmit.on('click', function() {
  if (!checkFormValidity()) {
    return false;
  }
  domFormRoles.value = selectedRoles.join();
  domFormWell.value = selectedWell;
  domForm.submit();
});

jQuery('.mb-role').each(function(_) {
  jQuery(this).on('click', function() {
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
      selectedRoles.push(id);
    } else {
      i = selectedRoles.indexOf(id);
      if (i !== -1) {
        selectedRoles.splice(i, 1);
      }
    }
    checkFormValidity();
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
