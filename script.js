var selectedRoles = [];
var selectedWell = null;
var valid = false;

// activate the form submission if valid selections
function checkValid() {
  if (selectedWell == null) return;
  jQuery('#submit').prop("disabled", false);

  valid = true;
  var dis = jQuery('.mbdisabled');
  if (dis != null && dis != undefined) dis.remove();
}

function transformBool(selected) {
  if (selected == undefined) {
    // First click should select
    selected = true;
  } else {
    // Otherwise, treat it like a bool
    if (selected == "true") selected = true;
    if (selected == "false") selected = false;

    // Invert the value
    selected = !selected;
  }

  return selected;
}

jQuery("#submit").on("click", function() {
  checkValid();
  if (!valid) return;

  document.getElementById('fWell').value = selectedWell;
  document.getElementById('fGroup').value = selectedRoles.join();
  document.getElementById('fSubmit').submit();
});

jQuery("#mbSelectAll").on("click", function () {
  jQuery(".rolebox").each(function (i) {
    var ele = jQuery(this);
    var selected = transformBool(ele.attr('data-selected'));
    if (!selected) transformBool(selected);

    var id = ele.attr('id');
    var index = selectedRoles.indexOf(id);
    if (index == -1) {
      selectedRoles.push(id);
      ele.addClass("selected");
    }

    ele.attr('data-selected', selected);
  });
});

jQuery(".rolebox").each(function (i) {
  jQuery(this).on("click", function() {
    var ele = jQuery(this);
    var selected = transformBool(ele.attr('data-selected'));

    // Now update visually based on the state
    if (selected) ele.addClass("selected");
    else ele.removeClass("selected");

    // Set back the selected state
    ele.attr('data-selected', selected);

    var id = ele.attr('id');
    if (selected) selectedRoles.push(id);
    else {
      var index = selectedRoles.indexOf(id);
      if (index !== -1)
        selectedRoles.splice(index, 1);
    }
  });
});

jQuery(".well").each(function (i) {
  jQuery(this).on("click", function() {
    // Clear all other selected states
    var that = this;
    jQuery(".well").each(function(i) {
      var ele2 = jQuery(this);
      ele2.attr('data-selected', false);
      ele2.removeClass("selected");
    });

    var ele = jQuery(this);
    var selected = transformBool(ele.attr('data-selected'));

    // Now update visually based on the state
    if (selected) ele.addClass("selected");
    else ele.removeClass("selected");

    // Set back the selected state
    ele.attr('data-selected', selected);

    // Keep track of the selection
    if (selected) {
      selectedWell = ele.attr('id');
      document.getElementById('wellName').innerHTML = selectedWell;
    }

    checkValid();
  });
});
